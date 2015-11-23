<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Router;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;
use bitExpert\Adroit\Router\Matcher\Matcher;

/**
 * Unit test for {@link \bitExpert\Adroit\Router\RegexRouter}.
 *
 * @covers \bitExpert\Adroit\Router\RegexRouter
 */
class RegExRouterUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RegexRouter
     */
    protected $router;
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $matcherMockBuilder = $this->getMockBuilder(Matcher::class)->setMethods(['match']);

        $notMatchingMatcher = $matcherMockBuilder->getMock();
        $notMatchingMatcher->expects($this->any())
            ->method('match')
            ->will($this->returnValue(false));

        $matchingMatcher = $matcherMockBuilder->getMock();
        $matchingMatcher->expects($this->any())
            ->method('match')
            ->will($this->returnValue(true));

        $this->request = new ServerRequest();
        $this->router = new RegExRouter('http://localhost');
        $this->router->setRoutes(
            [
                Route::get('/users')->to('my.GetActionToken'),
                Route::post('/users')->to('my.PostActionToken'),
                Route::get('/user/[:userId]')->to('my.GetActionTokenWithParam'),
                Route::get('/companies')->to('my.OtherGetActionToken'),
                Route::get('/offer/[:offerId]')
                    ->to('my.GetActionTokenWithMatchedParam')
                    ->ifMatches('offerId', $matchingMatcher),
                Route::get('/company/[:companyId]')
                    ->to('my.GetActionTokenWithUnmatchedParam')
                    ->ifMatches('companyId', $notMatchingMatcher),
            ]
        );
    }

    /**
     * @test
     */
    public function noMatchingMethodWillReturnNullWhenNoDefaultActionTokenWasSet()
    {
        $this->request = new ServerRequest([], [], '/users', 'HEAD');
        $this->request = $this->router->resolveActionToken($this->request);

        $this->assertNull($this->request->getAttribute(Router::ACTIONTOKEN_ATTR));
    }

    /**
     * @test
     */
    public function noMatchingMethodWillReturnDefaultActionToken()
    {
        $this->request = new ServerRequest([], [], '/users', 'HEAD');

        $this->router->setDefaultActionToken('default.actionToken');
        $this->request = $this->router->resolveActionToken($this->request);

        $this->assertSame('default.actionToken', $this->request->getAttribute(Router::ACTIONTOKEN_ATTR));
    }

    /**
     * @test
     */
    public function noMatchingRouteWillReturnDefaultActionToken()
    {
        $this->router->setDefaultActionToken('default.actionToken');
        $this->request = $this->router->resolveActionToken($this->request);

        $this->assertSame('default.actionToken', $this->request->getAttribute(Router::ACTIONTOKEN_ATTR));
    }

    /**
     * @test
     */
    public function noMatchingRouteWillReturnNullWhenNoDefaultActionTokenWasSet()
    {
        $this->request = $this->router->resolveActionToken($this->request);

        $this->assertNull($this->request->getAttribute(Router::ACTIONTOKEN_ATTR));
    }

    /**
     * @test
     */
    public function queryStringWillBeIgnoredWhenMatchingRoute()
    {
        $this->request = new ServerRequest([], [], '/users?sessid=ABDC', 'GET');
        $this->request = $this->router->resolveActionToken($this->request);

        $this->assertSame('my.GetActionToken', $this->request->getAttribute(Router::ACTIONTOKEN_ATTR));
    }

    /**
     * @test
     */
    public function matchingRouteWithoutParamsReturnsActionToken()
    {
        $this->request = new ServerRequest([], [], '/users', 'GET');
        $this->request = $this->router->resolveActionToken($this->request);

        $this->assertSame('my.GetActionToken', $this->request->getAttribute(Router::ACTIONTOKEN_ATTR));
    }

    /**
     * @test
     */
    public function matchingRouteWithParamsReturnsActionTokenAndSetsParamsInRequest()
    {
        $this->request = new ServerRequest([], [], '/user/123', 'GET');
        $this->request = $this->router->resolveActionToken($this->request);
        $queryParams = $this->request->getQueryParams();

        $this->assertSame('my.GetActionTokenWithParam', $this->request->getAttribute(Router::ACTIONTOKEN_ATTR));
        $this->assertTrue(isset($queryParams['userId']));
        $this->assertSame('123', $queryParams['userId']);
    }

    /**
     * @test
     */
    public function doesNotUseRouteIfMatcherDoesNotMatch()
    {
        $this->request = new ServerRequest([], [], '/company/abc', 'GET');
        $this->request = $this->router->resolveActionToken($this->request);
        $this->assertNull($this->request->getAttribute(Router::ACTIONTOKEN_ATTR));
    }

    /**
     * @test
     */
    public function usesRouteIfMatcherDoesMatch()
    {
        $this->request = new ServerRequest([], [], '/offer/123', 'GET');
        $this->request = $this->router->resolveActionToken($this->request);
        $this->assertEquals('my.GetActionTokenWithMatchedParam', $this->request->getAttribute(Router::ACTIONTOKEN_ATTR));
    }

    /**
     * @test
     */
    public function passingRegexRouterAsConfig()
    {
        $router = new RegexRouter('http://localhost');
        $router->setRoutes([new Route('GET', '/admin', 'my.AdminActionToken')]);

        $this->request = new ServerRequest([], [], '/admin', 'GET');
        $this->router->setRoutes([$router]);
        $this->request = $this->router->resolveActionToken($this->request);

        $this->assertSame('my.AdminActionToken', $this->request->getAttribute(Router::ACTIONTOKEN_ATTR));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function callingCreateLinkWithoutActionTokenWillThrowException()
    {
        $this->router->createLink('');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function throwsAnExceptionWhenMatchingRouteCouldNotBeFound()
    {
        $this->router->createLink('nonexistent.actionToken');
    }

    /**
     * @test
     */
    public function returnsActionTokenWhenMatchingRouteIsFound()
    {
        $url = $this->router->createLink('my.GetActionToken');

        $this->assertSame('/users', $url);
    }

    /**
     * @test
     */
    public function paramsAreIgnoredForRoutesWithoutAnyParams()
    {
        $url = $this->router->createLink('my.GetActionToken', ['sampleId' => 456]);

        $this->assertSame('/users', $url);
    }

    /**
     * @test
     */
    public function routeParamPlaceholdersWillBeReplaced()
    {
        $url = $this->router->createLink('my.GetActionTokenWithParam', ['userId' => 123]);

        $this->assertSame('/user/123', $url);
    }

    /**
     * @test
     */
    public function paramsNotFoundInRouteWillBeIgnoredWhenLinkIsAssembled()
    {
        $url = $this->router->createLink('my.GetActionTokenWithParam', ['userId' => 123, 'sampleId' => 123]);

        $this->assertSame('/user/123', $url);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function willThrowAnExceptionWhenNotAllParamReplacementsAreProvided()
    {
        $this->router->createLink('my.GetActionTokenWithParam', ['sampleId' => 123]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function willThrowAnExceptionWhenProvidingNotMatchingParams()
    {
        $this->router->createLink('my.GetActionTokenWithUnmatchedParam', ['companyId' => 'abc']);
    }
}
