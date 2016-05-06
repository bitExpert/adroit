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

use bitExpert\Adroit\Domain\Payload;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;

/**
 * Unit test for {@link \bitExpert\Adroit\Responder\Resolver\ResponderResolverMiddleware}.
 */
class ResponderResolverMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
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
            ->will($this->returnValue($this->createSimpleResponder()));

        $secondResolver->expects($this->never())
            ->method('resolve');

        $payload = $this->getMock(Payload::class);

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
            ->will($this->returnValue($this->createSimpleResponder()));


        $payload = $this->getMock(Payload::class);

        $request = new ServerRequest();
        $request = $request->withAttribute(self::$domainPayloadAttribute, $payload);
        $this->middleware->__invoke($request, new Response());
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Responder\Resolver\ResponderResolveException
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


        $payload = $this->getMock(Payload::class);

        $request = new ServerRequest();
        $request = $request->withAttribute(self::$domainPayloadAttribute, $payload);
        $this->middleware->__invoke($request, new Response());
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Responder\Resolver\ResponderResolveException
     */
    public function throwsExceptionIfDomainPayloadIsNotPresent()
    {
        $this->middleware->__invoke(new ServerRequest(), new Response());
    }

    /**
     * @test
     */
    public function returnsDomainPayloadDirectlyIfItIsAResponse()
    {
        foreach ($this->resolvers as $resolver) {
            $resolver->expects($this->never())
                ->method('resolve');
        }

        $request = new ServerRequest();
        $givenResponse = $this->getMockForAbstractClass(ResponseInterface::class);
        $request = $request->withAttribute(self::$domainPayloadAttribute, $givenResponse);
        $response = $this->middleware->__invoke($request, new Response());
        $this->assertSame($givenResponse, $response);
    }

    /**
     * @test
     */
    public function callsNextMiddlewareIfPresentAndDomainPayloadIsResponse()
    {
        $called = false;

        $next = function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            callable $next = null
        ) use (&$called) {
            $called = true;
        };

        $request = new ServerRequest();
        $givenResponse = $this->getMockForAbstractClass(ResponseInterface::class);
        $request = $request->withAttribute(self::$domainPayloadAttribute, $givenResponse);
        $this->middleware->__invoke($request, new Response(), $next);
        $this->assertTrue($called);
    }

    /**
     * @test
     */
    public function callsNextMiddlewareIfPresentAndDomainPayloadIsDomainPayload()
    {
        $called = false;

        $next = function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            callable $next = null
        ) use (&$called) {
            $called = true;
        };

        $request = new ServerRequest();
        $payload = $this->getMock(Payload::class);
        $this->resolvers[0]->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($this->createSimpleResponder()));

        $request = $request->withAttribute(self::$domainPayloadAttribute, $payload);
        $this->middleware->__invoke($request, new Response(), $next);
        $this->assertTrue($called);
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Responder\ResponderExecutionException
     */
    public function throwsExceptionIfResponderDoesNotReturnAResponse()
    {
        $this->resolvers[0]->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue(function () {

            }));

        $this->middleware->__invoke(new ServerRequest(), new Response());
    }

    /**
     * Creates a simple responder which returns a response to be valid
     * @return \Closure
     */
    protected function createSimpleResponder()
    {
        return function (Payload $payload, ResponseInterface $response) {
            return $response;
        };
    }
}
