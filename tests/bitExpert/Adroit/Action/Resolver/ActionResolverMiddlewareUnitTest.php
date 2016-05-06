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
        $this->request =  (new ServerRequest())->withAttribute(self::$routingResultAttribute, 'action');
        $this->response = new Response();
        $this->resolvers = [
            $this->getMockForAbstractClass(ActionResolver::class),
            $this->getMockForAbstractClass(ActionResolver::class)
        ];

        $this->middleware = new ActionResolverMiddleware($this->resolvers, self::$routingResultAttribute, self::$domainPayloadAttribute);
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
            ->will($this->returnValue(function () {}));


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
        $mock = $this->getMockForAbstractClass(ActionResolverMiddleware::class, [
            $resolvers,
            self::$routingResultAttribute,
            self::$domainPayloadAttribute
        ]);

        $mock->__invoke($this->request, $this->response);
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

        $this->middleware->__invoke($this->request, $this->response, $next);

        $this->assertTrue($called);
    }
}
