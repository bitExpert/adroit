<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Responder\Resolver;

use bitExpert\Adroit\Domain\DomainPayload;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * Unit test for {@link \bitExpert\Adroit\Responder\Resolver\ResponderResolverMiddleware}.
 *
 * @covers \bitExpert\Adroit\Action\Resolver\ResponderResolverMiddleware
 */
class ActionResolverMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected static $domainPayloadAttribute = 'domainPayload';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $resolvers;
    /**
     * @var ResponderResolverMiddleware
     */
    protected $middleware;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resolvers = [
            $this->getMockForAbstractClass(ResponderResolver::class),
            $this->getMockForAbstractClass(ResponderResolver::class)
        ];

        $this->middleware = new ResponderResolverMiddleware(
            $this->resolvers,
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

        $payload = $this->getMockBuilder(DomainPayload::class)->disableOriginalConstructor()->getMock();

        $request = new ServerRequest();
        $request = $request->withAttribute(self::$domainPayloadAttribute, $payload);
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


        $payload = $this->getMockBuilder(DomainPayload::class)->disableOriginalConstructor()->getMock();

        $request = new ServerRequest();
        $request = $request->withAttribute(self::$domainPayloadAttribute, $payload);
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


        $payload = $this->getMockBuilder(DomainPayload::class)->disableOriginalConstructor()->getMock();

        $request = new ServerRequest();
        $request = $request->withAttribute(self::$domainPayloadAttribute, $payload);
        $this->middleware->__invoke($request, new Response());
    }
}
