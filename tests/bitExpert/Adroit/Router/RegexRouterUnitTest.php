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

/**
 * Unit test for {@link \bitExpert\Adroit\Router\RegexRouter}.
 *
 * @covers \bitExpert\Adroit\Router\RegexRouter
 */
class RegexRouterUnitTest extends \PHPUnit_Framework_TestCase
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

        $this->request = new ServerRequest();
        $this->router = new RegexRouter('http://localhost');
        $this->router->setRoutes(
            [
                new Route('GET', '/users', 'my.GetActionToken'),
                new Route('POST', '/users', 'my.PostActionToken'),
                new Route('GET', '/user/[:userId]', 'my.GetActionTokenWithParam'),
                new Route('GET', '/companies', 'my.OtherGetActionToken'),
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
     */
    public function returnsNullWhenMatchingRouteCouldNotBeFound()
    {
        $url = $this->router->createLink('nonexistent.actionToken');

        $this->assertNull($url);
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
     */
    public function willReturnNullWhenNotAllParamReplacementsAreProvided()
    {
        $url = $this->router->createLink('my.GetActionTokenWithParam', ['sampleId' => 123]);

        $this->assertNull($url);
    }
}
