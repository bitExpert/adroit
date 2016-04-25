<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Action\Resolver;

use bitExpert\Pathfinder\Route;
use bitExpert\Pathfinder\RoutingResult;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * Unit test for {@link \bitExpert\Adroit\Action\Resolver\ActionResolverMiddleware}.
 *
 * @covers \bitExpert\Adroit\Action\Resolver\ActionResolverMiddleware
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
    protected static $domainPayloadAttribute = 'domainPayload';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $resolvers;
    /**
     * @var ActionResolverMiddleware
     */
    protected $middleware;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resolvers = [
            $this->getMockForAbstractClass(ActionResolver::class),
            $this->getMockForAbstractClass(ActionResolver::class)
        ];

        $this->middleware = new ActionResolverMiddleware(
            $this->resolvers,
            self::$routingResultAttribute,
            self::$domainPayloadAttribute
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
            ->will($this->returnValue(function () {}));

        $secondResolver->expects($this->never())
            ->method('resolve');

        $request = new ServerRequest();
        $route = Route::get('/')->to(function () {})->named('home');
        $request = $request->withAttribute('routingResult', RoutingResult::forSuccess($route));
        $this->middleware->__invoke($request, new Response());
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
            ->will($this->returnValue(function () {}));


        $request = new ServerRequest();
        $route = Route::get('/')->to(function () {})->named('home');
        $request = $request->withAttribute(self::$routingResultAttribute, RoutingResult::forSuccess($route));

        $this->middleware->__invoke($request, new Response());
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Resolver\ResolveException
     */
    public function throwsResolveExceptionIfAllResolversFail()
    {
        $firstResolver = $this->resolvers[0];
        $secondResolver = $this->resolvers[1];

        $firstResolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue(null));

        $secondResolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue(null));


        $request = new ServerRequest();
        $route = Route::get('/')->to(function () {})->named('home');
        $request = $request->withAttribute(self::$routingResultAttribute, RoutingResult::forSuccess($route));
        $this->middleware->__invoke($request, new Response());
    }
}
