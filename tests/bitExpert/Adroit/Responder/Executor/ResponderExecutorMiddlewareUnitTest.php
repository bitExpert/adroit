<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Responder\Executor;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * Unit test for {@link \bitExpert\Adroit\Action\Resolver\ResponderExecutorMiddleware}.
 */
class ResponderExecutorMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected static $responderAttribute = 'responder';
    /**
     * @var string
     */
    protected static $payloadAttribute = 'payload';
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var ResponseInterface
     */
    protected $response;
    /**
     * @var ActionExecutorMiddleware
     */
    protected $middleware;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->request =  new ServerRequest();
        $this->response = new Response();

        $this->middleware = new ResponderExecutorMiddleware(
            self::$responderAttribute,
            self::$payloadAttribute
        );
    }

    /**
     * @test
     */
    public function executesResponderIfResponderAndPayloadPresent()
    {
        $responder = $this->getMock(Responder::class, ['__invoke']);
        $responder->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($this->response));

        $payload = $this->getMock(Payload::class);

        $this->request = $this->request->withAttribute(self::$responderAttribute, $responder);
        $this->request = $this->request->withAttribute(self::$payloadAttribute, $payload);
        $this->middleware->__invoke($this->request, $this->response);
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Responder\Executor\ResponderExecutionException
     */
    public function throwsExceptionIfPayloadNotPresentInRequest()
    {
        $responder = $this->getMock(Responder::class, ['__invoke']);
        $responder->expects($this->never())
            ->method('__invoke');

        $request = $this->request->withAttribute(self::$responderAttribute, $responder);
        $this->middleware->__invoke($request, $this->response);
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Responder\Executor\ResponderExecutionException
     */
    public function throwsExceptionIfResponderNotPresentInRequest()
    {
        $payload = $this->getMock(Payload::class);
        $this->request = $this->request->withAttribute(self::$payloadAttribute, $payload);
        $this->middleware->__invoke($this->request, $this->response);
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Responder\Executor\ResponderExecutionException
     */
    public function throwsExceptionIfRResponderIsNotCallable()
    {
        $responder = 'notCallable';

        $payload = $this->getMock(Payload::class);

        $this->request = $this->request->withAttribute(self::$responderAttribute, $responder);
        $this->request = $this->request->withAttribute(self::$payloadAttribute, $payload);
        $this->middleware->__invoke($this->request, $this->response);
    }

    /**
     * @test
     */
    public function returnsDirectlyIfResponderIsResponse()
    {
        $responder = new Response();
        $this->request = $this->request->withAttribute(self::$responderAttribute, $responder);
        $response = $this->middleware->__invoke($this->request, $this->response);
        $this->assertSame($responder, $response);
    }

    /**
     * @test
     */
    public function executesNextIfGivenAndResponderIsResponse()
    {
        $called = false;
        $next = function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            callable $next = null
        ) use (&$called) {
            $called = true;
            return $response;
        };

        $responder = new Response();
        $this->request = $this->request->withAttribute(self::$responderAttribute, $responder);
        $response = $this->middleware->__invoke($this->request, $this->response, $next);
        $this->assertSame($responder, $response);
        $this->assertTrue($called);
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Responder\Executor\ResponderExecutionException
     */
    public function throwsExceptionIfResponderExecutionDoesNotReturnResponse()
    {
        $responder = $this->getMock(Responder::class, ['__invoke']);
        $responder->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue(null));

        $payload = $this->getMock(Payload::class);

        $this->request = $this->request->withAttribute(self::$responderAttribute, $responder);
        $this->request = $this->request->withAttribute(self::$payloadAttribute, $payload);
        $this->middleware->__invoke($this->request, $this->response);
    }

    /**
     * @test
     */
    public function callsNextMiddlewareIfResponderReturnedAResponse()
    {
        $called = false;

        $next = function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            callable $next = null
        ) use (&$called) {
            $called = true;
        };

        $responder = $this->getMock(Responder::class, ['__invoke']);
        $responder->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue(new Response()));

        $payload = $this->getMock(Payload::class);

        $this->request = $this->request->withAttribute(self::$responderAttribute, $responder);
        $this->request = $this->request->withAttribute(self::$payloadAttribute, $payload);

        $this->middleware->__invoke($this->request, $this->response, $next);

        $this->assertTrue($called);
    }
}
