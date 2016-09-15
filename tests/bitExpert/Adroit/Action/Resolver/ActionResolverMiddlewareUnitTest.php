<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types = 1);

namespace bitExpert\Adroit\Action\Resolver;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use bitExpert\Adroit\Resolver\Resolver;

/**
 * Unit test for {@link \bitExpert\Adroit\Action\Resolver\ActionResolverMiddleware}.
 */
class ActionResolverMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected static $routingResultAttribute = 'routingResult';
    /**
     * @var string
     */
    protected static $actionAttribute = 'action';
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $resolvers;
    /**
     * @var ActionResolverMiddleware
     */
    protected $middleware;
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->request =  (new ServerRequest())->withAttribute(self::$routingResultAttribute, 'actionToken');
        $this->response = new Response();
        $this->resolvers = [
            $this->getMockForAbstractClass(ActionResolver::class),
            $this->getMockForAbstractClass(ActionResolver::class)
        ];

        $this->middleware = new ActionResolverMiddleware(
            $this->resolvers,
            self::$routingResultAttribute,
            self::$actionAttribute
        );
    }

    /**
     * @test
     */
    public function returnsFirstResolvedValueWhichIsNotNullAndStops()
    {
        $firstResolver = $this->resolvers[0];
        $secondResolver = $this->resolvers[1];

        $firstResolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($this->createSimpleAction()));

        $secondResolver->expects($this->never())
            ->method('resolve');


        $this->middleware->__invoke($this->request, $this->response);
    }

    /**
     * @test
     */
    public function returnsSecondResolvedValueIfFirstResolverFails()
    {
        $firstResolver = $this->resolvers[0];
        $secondResolver = $this->resolvers[1];

        $firstResolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue(null));

        $secondResolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($this->createSimpleAction()));


        $this->middleware->__invoke($this->request, $this->response);
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Action\Resolver\ActionResolveException
     */
    public function throwsActionResolveExceptionIfAllResolversFail()
    {
        $firstResolver = $this->resolvers[0];
        $secondResolver = $this->resolvers[1];

        $firstResolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue(null));

        $secondResolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue(null));


        $this->middleware->__invoke($this->request, $this->response);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function throwsExceptionIfResolverIsNotAnActionResolver()
    {
        $resolvers = array_merge($this->resolvers, [$this->getMockForAbstractClass(Resolver::class)]);
        $middleware = new ActionResolverMiddleware(
            $resolvers,
            self::$routingResultAttribute,
            self::$actionAttribute
        );

        $middleware->__invoke($this->request, $this->response);
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Action\Resolver\ActionResolveException
     */
    public function throwsExceptionIfActionIdentifierCannotBeDetermined()
    {
        $this->request = $this->request->withoutAttribute(self::$routingResultAttribute);
        $this->middleware->__invoke($this->request, $this->response);
    }

    /**
     * @test
     */
    public function callsNextMiddlewareIfPresentAndActionCouldBeResolved()
    {
        $called = false;
        $next = function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            callable $next = null
        ) use (&$called) : ResponseInterface {
            $called = true;
            return $response;
        };

        $resolver = $this->resolvers[0];
        $resolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($this->createSimpleAction()));

        $this->middleware->__invoke($this->request, $this->response, $next);

        $this->assertTrue($called);
    }

    /**
     * Creates a simple action which returns the response directly to be a valid action
     * @return \Closure
     */
    protected function createSimpleAction()
    {
        return function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };
    }
}
