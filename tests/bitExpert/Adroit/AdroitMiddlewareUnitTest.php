<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit;

use bitExpert\Adroit\Action\Action;
use bitExpert\Adroit\Action\Resolver\ActionResolver;
use bitExpert\Adroit\Domain\DomainPayload;
use bitExpert\Adroit\Responder\Resolver\ResponderResolver;
use bitExpert\Adroit\Responder\Responder;
use bitExpert\Pathfinder\Router;
use bitExpert\Pathfinder\RoutingResult;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;

/**
 * Unit test for {@link \bitExpert\Adroit\AdroitMiddleware}.
 *
 * @covers \bitExpert\Adroit\AdroitMiddleware
 */
class AdroitMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var Router|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;
    /**
     * @var Action|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $action;
    /**
     * @var ActionResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionResolver;
    /**
     * @var Responder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responder;
    /**
     * @var ResponderResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responderResolver;
    /**
     * @var AdroitMiddleware|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $middleware;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $this->request = new ServerRequest();
        $this->router = $this->getMock(Router::class);
        $this->action = $this->getMock(Action::class);
        $this->actionResolver = $this->getMock(ActionResolver::class);
        $this->responder = $this->getMock(Responder::class);
        $this->responderResolver = $this->getMock(ResponderResolver::class);
        $this->middleware = new AdroitMiddleware([$this->actionResolver], [$this->responderResolver], $this->router);
    }

    /**
     * @test
     */
    public function whenActionReturnsResponseImmediatelyReturnIt()
    {
        $returnValue = $this->buildResponse('OK', 200);
        $this->action->expects($this->once())
            ->method('prepareAndExecute')
            ->will($this->returnValue($returnValue));

        $this->middleware = $this->getMock(
            AdroitMiddleware::class,
            ['resolveRoutingResult', 'resolveAction'],
            [],
            '',
            false
        );
        $this->middleware->expects($this->once())
            ->method('resolveRoutingResult')
            ->will($this->returnValue(RoutingResult::forSuccess('myaction')));

        $this->middleware->expects($this->once())
            ->method('resolveAction')
            ->will($this->returnValue($this->action));

        $this->injectLogger($this->middleware);
        $response = $this->middleware->__invoke($this->request, new Response());
        $this->assertSame($returnValue, $response);
    }

    /**
     * @test
     */
    public function whenActionReturnsDomainPayloadLookForMatchingResponderAndExecute()
    {
        $returnValue = $this->buildResponse('<html>', 200);
        $this->responder->expects($this->once())
            ->method('buildResponse')
            ->will($this->returnValue($returnValue));

        $domainPayload = new DomainPayload('test');
        $this->action->expects($this->once())
            ->method('prepareAndExecute')
            ->will($this->returnValue($domainPayload));

        $this->middleware = $this->getMock(
            AdroitMiddleware::class,
            ['resolveRoutingResult', 'resolveAction', 'resolveResponder'],
            [],
            '',
            false
        );
        $this->middleware->expects($this->once())
            ->method('resolveRoutingResult')
            ->will($this->returnValue(RoutingResult::forSuccess('myaction')));
        $this->middleware->expects($this->once())
            ->method('resolveAction')
            ->will($this->returnValue($this->action));
        $this->middleware->expects($this->once())
            ->method('resolveResponder')
            ->will($this->returnValue($this->responder));
        $this->injectLogger($this->middleware);

        $response = $this->middleware->__invoke($this->request, new Response());
        $this->assertSame($returnValue, $response);
    }

    /**
     * @test
     */
    public function whenActionReturnsNotExpectedType500ResponseGetsReturned()
    {
        $mar = 'test';
        $this->action->expects($this->once())
            ->method('prepareAndExecute')
            ->will($this->returnValue($mar));

        $this->middleware = $this->getMock(
            AdroitMiddleware::class,
            ['resolveRoutingResult', 'resolveAction'],
            [],
            '',
            false
        );
        $this->middleware->expects($this->once())
            ->method('resolveRoutingResult')
            ->will($this->returnValue(RoutingResult::forSuccess('myaction')));
        $this->middleware->expects($this->once())
            ->method('resolveAction')
            ->will($this->returnValue($this->action));
        $this->injectLogger($this->middleware);

        $response = $this->middleware->__invoke($this->request, new Response());
        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function whenRoutingResultDoesNotContainTarget404ResponseGetsReturned()
    {
        $this->middleware = $this->getMock(
            AdroitMiddleware::class,
            ['resolveRoutingResult', 'resolveAction'],
            [],
            '',
            false
        );
        $this->middleware->expects($this->once())
            ->method('resolveRoutingResult')
            ->will($this->returnValue(RoutingResult::forFailure()));

        $this->injectLogger($this->middleware);

        $response = $this->middleware->__invoke($this->request, new Response());
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function actionResolverIgnoresRevolversThatDoNotMatchTheNeededInterface()
    {
        $this->router->expects($this->once())
            ->method('match')
            ->will($this->returnValue(RoutingResult::forSuccess('myaction')));

        $this->middleware = new AdroitMiddleware(
            [new \stdClass, new \stdClass],
            [$this->responderResolver],
            $this->router
        );

        $this->middleware->__invoke($this->request, new Response());
    }

    /**
     * @test
     */
    public function actionResolverReturnsFirstMatch()
    {
        $returnValue = $this->buildResponse('OK', 200);
        $this->action->expects($this->once())
            ->method('prepareAndExecute')
            ->will($this->returnValue($returnValue));
        $this->router->expects($this->once())
            ->method('match')
            ->will($this->returnValue(RoutingResult::forSuccess('myaction')));
        $this->actionResolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($this->action));
        $actionResolver2 = $this->getMock(ActionResolver::class);
        $actionResolver2->expects($this->never())
            ->method('resolve');

        $this->middleware = new AdroitMiddleware(
            [$this->actionResolver, $actionResolver2],
            [$this->responderResolver],
            $this->router
        );
        $response = $this->middleware->__invoke($this->request, new Response());
        $this->assertSame($returnValue, $response);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function responderResolverIgnoresRevolversThatDoNotMatchTheNeededInterface()
    {
        $domainPayload = new DomainPayload('test');
        $this->action->expects($this->once())
            ->method('prepareAndExecute')
            ->will($this->returnValue($domainPayload));
        $this->router->expects($this->once())
            ->method('match')
            ->will($this->returnValue(RoutingResult::forSuccess('myaction')));

        $this->middleware = $this->getMock(
            AdroitMiddleware::class,
            ['resolveAction'],
            [[], [new \stdClass()], $this->router]
        );
        $this->middleware->expects($this->once())
            ->method('resolveAction')
            ->will($this->returnValue($this->action));

        $this->middleware->__invoke($this->request, new Response());
    }

    /**
     * @test
     */
    public function responderResolverReturnsFirstMatch()
    {
        $domainPayload = new DomainPayload('test');
        $this->action->expects($this->once())
            ->method('prepareAndExecute')
            ->will($this->returnValue($domainPayload));
        $this->router->expects($this->once())
            ->method('match')
            ->will($this->returnValue(RoutingResult::forSuccess('myaction')));

        $returnValue = $this->buildResponse('<html>', 200);
        $this->responder->expects($this->once())
            ->method('buildResponse')
            ->will($this->returnValue($returnValue));
        $this->responderResolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($this->responder));
        $responderResolver2 = $this->getMock(ResponderResolver::class);
        $responderResolver2->expects($this->never())
            ->method('resolve');

        $this->middleware = $this->getMock(
            AdroitMiddleware::class,
            ['resolveAction'],
            [[], [$this->responderResolver, $responderResolver2], $this->router]
        );
        $this->middleware->expects($this->once())
            ->method('resolveAction')
            ->will($this->returnValue($this->action));

        $response = $this->middleware->__invoke($this->request, new Response());
        $this->assertSame($returnValue, $response);
    }

    /**
     * @test
     */
    public function mergesMatchedParamsOfRoute()
    {
        $testCase = $this;
        $queryParams = [
            'queryParam1' => 'queryValue1',
            'queryParam2' => 'queryValue2',
            'queryParam3' => 'queryValue3'
        ];

        $routeParams = [
            'routeParam1' => 'value1',
            'routeParam2' => 'value2'
        ];

        $mergedParams = array_merge($queryParams, $routeParams);

        $this->request = $this->request->withQueryParams($queryParams);

        $this->router->expects($this->once())
            ->method('match')
            ->will($this->returnValue(RoutingResult::forSuccess('myaction', $routeParams)));

        $this->action->expects($this->once())
            ->method('prepareAndExecute')
            ->will($this->returnCallback(function (ServerRequestInterface $request) use ($testCase, $mergedParams) {
                $queryParams = $request->getQueryParams();
                $this->assertArraySubset($mergedParams, $queryParams);
            }));


        $this->middleware = $this->getMock(
            AdroitMiddleware::class,
            ['resolveAction'],
            [[], [new \stdClass()], $this->router]
        );

        $this->middleware->expects($this->once())
            ->method('resolveAction')
            ->will($this->returnValue($this->action));


        $this->middleware->__invoke($this->request, new Response());
    }

    /**
     * @test
     */
    public function givesMatchedParamsOfRoutePriority()
    {
        $testCase = $this;
        $routeParams = [
            'sharedParam1' => 'routeValue1',
            'sharedParam2' => 'routeValue2'
        ];

        $queryParams =[
            'sharedParam1' => 'requestValue1',
            'sharedParam2' => 'requestValue2'
        ];

        $this->router->expects($this->once())
            ->method('match')
            ->will($this->returnValue(RoutingResult::forSuccess('myaction', $routeParams)));

        $this->action->expects($this->once())
            ->method('prepareAndExecute')
            ->will($this->returnCallback(function (ServerRequestInterface $request) use ($testCase, $routeParams) {
                $queryParams = $request->getQueryParams();
                $this->assertArraySubset($routeParams, $queryParams);
            }));


        $this->middleware = $this->getMock(
            AdroitMiddleware::class,
            ['resolveAction'],
            [[], [new \stdClass()], $this->router]
        );

        $this->middleware->expects($this->once())
            ->method('resolveAction')
            ->will($this->returnValue($this->action));

        $this->request = $this->request->withQueryParams($queryParams);

        $this->middleware->__invoke($this->request, new Response());
    }

    /**
     * Helper method to inject a {@link \Psr\Log\NullLogger} instance.
     *
     * @param $httpKernel
     */
    protected function injectLogger($httpKernel)
    {
        $reflectionClass = new \ReflectionClass(get_class($httpKernel));
        $reflectionProperty = $reflectionClass->getProperty('logger');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($httpKernel, new NullLogger());
    }

    /**
     * Helper method to create a response object.
     *
     * @param $content
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    protected function buildResponse($content, $statusCode = 200, array $headers = [])
    {
        $body = new Stream('php://memory', 'w+');
        $body->write($content);

        return new Response($body, $statusCode, $headers);
    }
}
