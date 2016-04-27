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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use bitExpert\Adroit\Resolver\Resolver;
/**
 * Unit test for {@link \bitExpert\Adroit\Action\Resolver\ActionResolverMiddleware}.
 *
 * @covers \bitExpert\Adroit\Action\Resolver\ActionResolverMiddleware
 * @covers \bitExpert\Adroit\Resolver\AbstractResolverMiddleware
 * @covers \bitExpert\Adroit\Resolver\CallableResolverMiddleware
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

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function throwsExceptionIfResolverIsNotAnActionResolver()
    {
        $resolvers = array_merge($this->resolvers, [$this->getMockForAbstractClass(Resolver::class)]);
        new ActionResolverMiddleware($resolvers, self::$routingResultAttribute, self::$domainPayloadAttribute);
    }

    /**
     * @test
     */
    public function returnsTheCorrectDomainPayloadAttribute()
    {
        $attribute = $this->middleware->getDomainPayloadAttribute();
        $this->assertEquals(self::$domainPayloadAttribute, $attribute);
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Resolver\ResolveException
     */
    public function throwsExceptionIfActionIdentifierCannotBeDetermined()
    {
        $this->middleware->__invoke(new ServerRequest(), new Response());
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Resolver\ResolveException
     */
    public function throwsExceptionIfRoutingResultFailed()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute(self::$routingResultAttribute, RoutingResult::forFailure(RoutingResult::FAILED_NOT_FOUND));
        $this->middleware->__invoke($request, new Response());
    }

    /**
     * @test
     */
    public function callsNextMiddlewareIfPresentAndActionCouldBeExecuted()
    {
        $called = false;
        $next = function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) use (&$called) {
            $called = true;
        };
        $callableAction = function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) {};
        $resolver = $this->resolvers[0];
        $resolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($callableAction));

        $request = new ServerRequest();

        $route = Route::get('/', 'action');
        $request = $request->withAttribute(self::$routingResultAttribute, RoutingResult::forSuccess($route));
        $this->middleware->__invoke($request, new Response(), $next);

        $this->assertTrue($called);
    }
}
