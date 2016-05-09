<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Action\Executor;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * Unit test for {@link \bitExpert\Adroit\Action\Resolver\ActionExecutorMiddleware}.
 */
class ActionExecutorMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
{
    protected static $actionAttribute = 'action';
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

        $this->middleware = new ActionExecutorMiddleware(
            self::$actionAttribute,
            self::$payloadAttribute
        );
    }

    /**
     * @test
     */
    public function executesActionIfPresent()
    {
        $action = $this->getMock(Action::class, ['__invoke']);
        $action->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($this->response));

        $request = $this->request->withAttribute(self::$actionAttribute, $action);
        $this->middleware->__invoke($request, $this->response);
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Action\Executor\ActionExecutionException
     */
    public function throwsExceptionIfNoActionPresent()
    {
        $this->middleware->__invoke($this->request, $this->response);
    }

    /**
     * @test
     */
    public function callsNextMiddlewareIfPresentAndActionCouldBeExecuted()
    {
        $called = false;

        $next = function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            callable $next = null
        ) use (&$called) {
            $called = true;
        };


        $this->request = $this->request->withAttribute(self::$actionAttribute, function () {
            return new Response();
        });

        $this->middleware->__invoke($this->request, $this->response, $next);

        $this->assertTrue($called);
    }
    
    /**
     * @test
     * @expectedException \bitExpert\Adroit\Action\Executor\ActionExecutionException
     */
    public function throwsExceptionIfActionExecutionDoesNotReturnPayloadOrResponse()
    {
        $action = $this->getMock(Action::class, ['__invoke']);
        $action->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue(null));

        $request = $this->request->withAttribute(self::$actionAttribute, $action);
        $this->middleware->__invoke($request, $this->response);
    }
}
