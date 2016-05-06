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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use bitExpert\Adroit\Action\ActionMiddleware;
use bitExpert\Adroit\Responder\ResponderMiddleware;

/**
 * Unit test for {@link \bitExpert\Adroit\AdroitMiddleware}.
 */
class AdroitMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
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
     * @var \bitExpert\Adroit\Action\ActionMiddleware
     */
    protected $actionMiddleware;
    /**
     * @var \bitExpert\Adroit\Responder\ResponderMiddleware
     */
    protected $responderMiddleware;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->actionMiddleware = $this->getMock(ActionMiddleware::class);
        $this->responderMiddleware = $this->getMock(ResponderMiddleware::class);

        $this->request = new ServerRequest([], [], '/', 'GET');
        $this->response = new Response();
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

        $actionMiddlewareStub = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'action';
        });

        $this->actionMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($actionMiddlewareStub));


        $middleware = new AdroitMiddleware($this->actionMiddleware, $this->responderMiddleware);

        $middleware->beforeAction($beforeActionMiddleware);
        $middleware->__invoke($this->request, $this->response);
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

        $this->actionMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));

        $beforeResponderMiddleware = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'beforeResponder';
        });

        $responderMiddlewareStub = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'responder';
        });

        $this->responderMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($responderMiddlewareStub));

        $middleware = new AdroitMiddleware($this->actionMiddleware, $this->responderMiddleware);

        $middleware->beforeResponder($beforeResponderMiddleware);
        $middleware->__invoke($this->request, $this->response);
        $this->assertEquals($order, $expectedOrder);
    }

    /**
     * @test
     */
    public function afterResponderMiddlewareWillBeCalledAfterResponderMiddleware()
    {
        $expectedOrder = [
            'responder',
            'afterResponder'
        ];

        $order = [];

        $this->actionMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));

        $afterResponderMiddleware = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'afterResponder';
        });

        $responderMiddlewareStub = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'responder';
        });

        $this->responderMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($responderMiddlewareStub));

        $middleware = new AdroitMiddleware($this->actionMiddleware, $this->responderMiddleware);

        $middleware->afterResponder($afterResponderMiddleware);
        $middleware->__invoke($this->request, $this->response);
        $this->assertEquals($order, $expectedOrder);
    }

    /**
     * @test
     */
    public function createDefaultCreatesMiddlewareWithDefaultMiddlewares()
    {
        $middleware = AdroitMiddleware::createDefault('routing', [], []);
        $this->assertInstanceOf(AdroitMiddleware::class, $middleware);
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
}
