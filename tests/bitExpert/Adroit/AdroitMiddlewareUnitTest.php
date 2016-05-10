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
use bitExpert\Adroit\Domain\Payload;
use bitExpert\Adroit\Responder\Responder;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use bitExpert\Adroit\Action\Resolver\ActionResolverMiddleware;
use bitExpert\Adroit\Action\Executor\ActionExecutorMiddleware;
use bitExpert\Adroit\Responder\Resolver\ResponderResolverMiddleware;
use bitExpert\Adroit\Responder\Executor\ResponderExecutorMiddleware;
use bitExpert\Adroit\Responder\ResponderMiddleware;

/**
 * Unit test for {@link \bitExpert\Adroit\AdroitMiddleware}.
 */
class AdroitMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
{
    protected static $routingResultAttribute = 'routing';
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var ResponseInterface
     */
    protected $response;
    /**
     * @var ActionResolverMiddleware
     */
    protected $actionResolverMiddleware;
    /**
     * @var ActionExecutorMiddleware
     */
    protected $actionExecutorMiddleware;
    /**
     * @var ResponderResolverMiddleware
     */
    protected $responderResolverMiddleware;
    /**
     * @var ResponderExecutorMiddleware
     */
    protected $responderExecutorMiddleware;
    /**
     * @var AdroitMiddleware
     */
    protected $middleware;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->actionResolverMiddleware = $this->getMockBuilder(ActionResolverMiddleware::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionExecutorMiddleware = $this->getMockBuilder(ActionExecutorMiddleware::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responderResolverMiddleware = $this->getMockBuilder(ResponderResolverMiddleware::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->responderExecutorMiddleware = $this->getMockBuilder(ResponderExecutorMiddleware::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new ServerRequest([], [], '/', 'GET');
        $this->response = new Response();
    }

    protected function getMockedAdroitMiddleware($mockPipe = false)
    {
        if ($mockPipe) {
            $methods = [
                'pipe'
            ];
        } else {
            $methods = [
                'getActionResolverMiddleware',
                'getActionExecutorMiddleware',
                'getResponderResolverMiddleware',
                'getResponderExecutorMiddleware'
            ];
        }

        $middleware = $this->getMockBuilder(AdroitMiddleware::class)
            ->setMethods($methods)
            ->setConstructorArgs([
                'routing',
                [],
                []
            ])
            ->getMock();

        if (!$mockPipe) {
            $middleware->expects($this->any())
                ->method('getActionResolverMiddleware')
                ->will($this->returnValue($this->actionResolverMiddleware));

            $middleware->expects($this->any())
                ->method('getActionExecutorMiddleware')
                ->will($this->returnValue($this->actionExecutorMiddleware));

            $middleware->expects($this->any())
                ->method('getResponderResolverMiddleware')
                ->will($this->returnValue($this->responderResolverMiddleware));

            $middleware->expects($this->any())
                ->method('getResponderExecutorMiddleware')
                ->will($this->returnValue($this->responderExecutorMiddleware));
        }
        return $middleware;
    }

    /**
     * @test
     */
    public function beforeResolveActionMiddlewareWillBeCalledBeforeActionResolveMiddleware()
    {
        $expectedOrder = [
            'beforeResolveAction',
            'resolveAction'
        ];

        $order = [];

        $beforeResolveActionMiddleware = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'beforeResolveAction';
        });

        $actionResolveMiddlewareStub = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'resolveAction';
        });

        $this->actionResolverMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($actionResolveMiddlewareStub));

        $middleware = $this->getMockedAdroitMiddleware();

        $middleware->beforeResolveAction($beforeResolveActionMiddleware);
        $middleware->__invoke($this->request, $this->response);

        $this->assertEquals($order, $expectedOrder);
    }

    /**
     * @test
     */
    public function beforeExecuteActionMiddlewareWillBeCalledBeforeActionExecuteMiddleware()
    {
        $expectedOrder = [
            'beforeActionExecute',
            'actionExecute'
        ];

        $order = [];

        $beforeExecuteActionMiddleware = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'beforeActionExecute';
        });

        $actionExecutorMiddlewareStub = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'actionExecute';
        });

        $this->actionResolverMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));

        $this->actionExecutorMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($actionExecutorMiddlewareStub));

        $middleware = $this->getMockedAdroitMiddleware();

        $middleware->beforeExecuteAction($beforeExecuteActionMiddleware);
        $middleware->__invoke($this->request, $this->response);

        $this->assertEquals($order, $expectedOrder);
    }

    /**
     * @test
     */
    public function beforeResolveResponderMiddlewareWillBeCalledBeforeResolveResponderMiddleware()
    {
        $expectedOrder = [
            'beforeResolveResponder',
            'resolveResponder'
        ];

        $order = [];

        $this->actionResolverMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));

        $this->actionExecutorMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));

        $beforeResolveResponderMiddleware = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'beforeResolveResponder';
        });

        $responderResolverMiddlewareStub = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'resolveResponder';
        });

        $this->responderResolverMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($responderResolverMiddlewareStub));

        $middleware = $this->getMockedAdroitMiddleware();

        $middleware->beforeResolveResponder($beforeResolveResponderMiddleware);
        $middleware->__invoke($this->request, $this->response);
        $this->assertEquals($order, $expectedOrder);
    }

    /**
     * @test
     */
    public function beforeExecuteResponderMiddlewareWillBeCalledBeforeResponderExecutorMiddleware()
    {
        $expectedOrder = [
            'beforeExecuteResponder',
            'executeResponder'
        ];

        $order = [];

        $this->actionResolverMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));

        $this->actionExecutorMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));

        $this->responderResolverMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));


        $beforeExecuteResponderMiddleware = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'beforeExecuteResponder';
        });

        $responderExecutorMiddlewareStub = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'executeResponder';
        });

        $this->responderExecutorMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($responderExecutorMiddlewareStub));


        $middleware = $this->getMockedAdroitMiddleware();

        $middleware->beforeExecuteResponder($beforeExecuteResponderMiddleware);
        $middleware->__invoke($this->request, $this->response);
        $this->assertEquals($order, $expectedOrder);
    }

    /**
     * @test
     */
    public function invokeCreatesActionResolverMiddleware()
    {
        // Get mock, without the constructor being called
        $middleware = $this->getMockedAdroitMiddleware();

        // set expectations for constructor calls
        $middleware->expects($this->once())
            ->method('getActionResolverMiddleware')
            ->with([], self::$routingResultAttribute);

        $middleware->__invoke(new ServerRequest(), new Response());
    }

    /**
     * @test
     */
    public function invokeCreatesActionExecutorMiddleware()
    {
        // Get mock, without the constructor being called
        $middleware = $this->getMockedAdroitMiddleware();

        // set expectations for constructor calls
        $middleware->expects($this->once())
            ->method('getActionExecutorMiddleware')
            ->with(Action::class, Payload::class);

        $middleware->__invoke(new ServerRequest(), new Response());
    }

    /**
     * @test
     */
    public function invokeCreatesResponderResolverMiddleware()
    {
        // Get mock, without the constructor being called
        $middleware = $this->getMockedAdroitMiddleware();

        // set expectations for constructor calls
        $middleware->expects($this->once())
            ->method('getResponderResolverMiddleware')
            ->with([], Payload::class, Responder::class);

        $middleware->__invoke(new ServerRequest(), new Response());
    }

    /**
     * @test
     */
    public function invokeCreatesResponderExecutorMiddleware()
    {
        // Get mock, without the constructor being called
        $middleware = $this->getMockedAdroitMiddleware();

        // set expectations for constructor calls
        $middleware->expects($this->once())
            ->method('getResponderExecutorMiddleware')
            ->with(Responder::class, Payload::class);

        $middleware->__invoke(new ServerRequest(), new Response());
    }

    /**
     * @test
     */
    public function createsWorkingMiddlewaresAndPipesCorrectOrder()
    {
        $expectedMiddlewares = [
            ActionResolverMiddleware::class,
            ActionExecutorMiddleware::class,
            ResponderResolverMiddleware::class,
            ResponderExecutorMiddleware::class
        ];

        $pipedMiddlewares = [];

        $middleware = $this->getMockedAdroitMiddleware(true);
        $middleware->expects($this->any())
            ->method('pipe')
            ->will($this->returnCallback(function ($middleware) use (&$pipedMiddlewares) {
                $pipedMiddlewares[] = get_class($middleware);
            }));

        $middleware->__invoke(new ServerRequest(), new Response());
        $this->assertSame($expectedMiddlewares, $pipedMiddlewares);
    }

    /**
     * Returns a middleware. You may define an additional callable which will be executed in front of
     * the default behavior for testing purpose (e.g. testing call order)
     *
     * @param callable|null $specializedFn
     * @return \Closure
     */
    protected function createTestMiddleware(callable $specializedFn = null)
    {
        return function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            callable $next = null
        ) use ($specializedFn) {
            if ($specializedFn) {
                $specializedFn();
            }

            if ($next) {
                $response = $next($request, $response);
            }

            return $response;
        };
    }
}
