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

use bitExpert\Pathfinder\Matcher\NumericMatcher;
use bitExpert\Pathfinder\Route;
use bitExpert\Pathfinder\Router;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\EmitterInterface;
use bitExpert\Adroit\Routing\RoutingMiddleware;
use bitExpert\Adroit\Action\ActionMiddleware;
use bitExpert\Adroit\Responder\ResponderMiddleware;

/**
 * Unit test for {@link \bitExpert\Adroit\WebApplication}.
 *
 * @covers \bitExpert\Adroit\WebApplication
 */
class WebApplicationUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var ResponseInterface
     */
    protected $response;
    /**
     * @var EmitterInterface
     */
    protected $emitter;
    /**
     * @var WebApplication
     */
    protected $application;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->request = new ServerRequest([], [], '/', 'GET');
        $this->response = new Response();
        $this->emitter = $this->getMock(EmitterInterface::class);
    }

    /**
     * @test
     */
    public function setErrorHandlerWillBeCalledWhenExceptionIsThrown()
    {
        $app = WebApplication::createDefault(null, [], [], $this->emitter);
        $called = false;
        $errorHandler = function (ServerRequestInterface $request, ResponseInterface $response, $err) use (&$called){
            $called = true;
        };
        $app->setErrorHandler($errorHandler);
        $app->addRoute(
            Route::get('/')->to(function (ServerRequestInterface $request, ResponseInterface $response) {
                throw new \Exception();
            })->named('home')
        );
        $app->run($this->request, $this->response);
        $this->assertTrue($called);
    }

    /**
     * @test
     */
    public function beforeRoutingMiddlewareWillBeCalledBeforeRoutingMiddleware()
    {
        $expectedOrder = [
            'beforeRouting',
            'routing'
        ];

        $order = [];
        $beforeRoutingMiddleware = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'beforeRouting';
        });

        $routingMiddleware = $this->getMockBuilder(RoutingMiddleware::class, ['__invoke'])
            ->disableOriginalConstructor()
            ->getMock();

        $routingMiddlewareStub = $this->createTestMiddleware(function () use(&$order) {
            $order[] = 'routing';
        });

        $routingMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($routingMiddlewareStub));

        $actionMiddleware = $this->getMockBuilder(ActionMiddleware::class)
            ->disableOriginalConstructor()
            ->getMock();

        $responderMiddleware = $this->getMockBuilder(ResponderMiddleware::class)
            ->disableOriginalConstructor()
            ->getMock();

        $app = new WebApplication($routingMiddleware, $actionMiddleware, $responderMiddleware, $this->emitter);

        $app->beforeRouting($beforeRoutingMiddleware);
        $app->run($this->request, $this->response);
        $this->assertEquals($order, $expectedOrder);
    }

    /**
     * @test
     */
    public function beforeActionMiddlewareWillBeCalledBeforeActionMiddleware()
    {
        $expectedOrder = [
            'beforeAction',
            'action'
        ];

        $order = [];
        $beforeActionMiddleware = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'beforeAction';
        });

        $routingMiddleware = $this->getMockBuilder(RoutingMiddleware::class, ['__invoke'])
            ->disableOriginalConstructor()
            ->getMock();

        $routingMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));

        $actionMiddlewareStub = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'action';
        });

        $actionMiddleware = $this->getMockBuilder(ActionMiddleware::class, ['__invoke'])
            ->disableOriginalConstructor()
            ->getMock();

        $actionMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($actionMiddlewareStub));

        $responderMiddleware = $this->getMockBuilder(ResponderMiddleware::class)
            ->disableOriginalConstructor()
            ->getMock();

        $app = new WebApplication($routingMiddleware, $actionMiddleware, $responderMiddleware, $this->emitter);

        $app->beforeAction($beforeActionMiddleware);
        $app->run($this->request, $this->response);
        $this->assertEquals($order, $expectedOrder);
    }

    /**
     * @test
     */
    public function beforeResponderMiddlewareWillBeCalledBeforeResponderMiddleware()
    {
        $expectedOrder = [
            'beforeResponder',
            'responder'
        ];

        $order = [];

        $routingMiddleware = $this->getMockBuilder(RoutingMiddleware::class, ['__invoke'])
            ->disableOriginalConstructor()
            ->getMock();

        $routingMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));


        $actionMiddleware = $this->getMockBuilder(ActionMiddleware::class, ['__invoke'])
            ->disableOriginalConstructor()
            ->getMock();

        $actionMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));

        $beforeResponderMiddleware = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'beforeResponder';
        });

        $responderMiddleware = $this->getMockBuilder(ResponderMiddleware::class)
            ->disableOriginalConstructor()
            ->getMock();

        $responderMiddlewareStub = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'responder';
        });

        $responderMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($responderMiddlewareStub));

        $app = new WebApplication($routingMiddleware, $actionMiddleware, $responderMiddleware, $this->emitter);

        $app->beforeResponder($beforeResponderMiddleware);
        $app->run($this->request, $this->response);
        $this->assertEquals($order, $expectedOrder);
    }

    /**
     * @test
     */
    public function getProducesAndRegistersARoute()
    {
        $this->routeCreationTestFunction('get');
    }

    /**
     * @test
     */
    public function postProducesAndRegistersARoute()
    {
        $this->routeCreationTestFunction('post');
    }

    /**
     * @test
     */
    public function putProducesAndRegistersARoute()
    {
        $this->routeCreationTestFunction('put');
    }

    /**
     * @test
     */
    public function deleteProducesAndRegistersARoute()
    {
        $this->routeCreationTestFunction('delete');
    }

    /**
     * @test
     */
    public function optionsProducesAndRegistersARoute()
    {
        $this->routeCreationTestFunction('options');
    }

    /**
     * @test 
     */
    public function patchProducesAndRegistersARoute()
    {
        $this->routeCreationTestFunction('patch');
    }

    /**
     * Returns a middleware. You may define an additional callable which will be executed in front of the default behavior
     * for testing purpose (e.g. testing call order)
     *
     * @param callable|null $specializedFn
     * @return \Closure
     */
    protected function createTestMiddleware(callable $specializedFn = null)
    {
        return function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) use ($specializedFn) {
            if ($specializedFn) {
                $specializedFn();
            }

            if ($next) {
                $response = $next($request, $response);
            }

            return $response;
        };
    }

    /**
     * Creates a route creation testing function for the appropriate shorthand WebApplication function
     * to create a route
     *
     * @param string $method
     */
    protected function routeCreationTestFunction($method)
    {
        $name = 'user';
        $path = '/user/[:id]';
        $target = 'userAction';
        $matchers = [
            'id' => [$this->getMock(NumericMatcher::class)]
        ];

        $router = $this->createRouteCreationTestRouter(strtoupper($method), $name, $path, $target, $matchers);
        $app = WebApplication::createDefault($router);
        $function = strtolower($method);
        $app->$function($name, $path, $target, $matchers);
    }

    /**
     * Creates a router which observes the incoming route and compares the properties
     * to the properties having been used to create the route
     *
     * @param $method
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createRouteCreationTestRouter($method, $name, $path, $target, $matchers)
    {
        $router = $this->getMockForAbstractClass(Router::class, ['addRoute']);
        $self = $this;
        $router->expects($this->once())
            ->method('addRoute')
            ->will($this->returnCallback(function (Route $route) use ($self, $method, $name, $path, $target, $matchers) {
                $routeMethods = $route->getMethods();
                $self->assertContains($method, $routeMethods);
                $routePath = $route->getPath();
                $self->assertEquals($routePath, $path);
                $routeName = $route->getName();
                $self->assertEquals($routeName, $name);
                $routeTarget = $route->getTarget();
                $self->assertEquals($routeTarget, $target);
                $routeMatchers = $route->getMatchers();
                $self->assertEquals($routeMatchers, $matchers);
            }));

        return $router;
    }
}
