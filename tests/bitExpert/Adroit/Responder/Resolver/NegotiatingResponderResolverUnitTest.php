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

use bitExpert\Adroit\Accept\ContentNegotiationManager;
use bitExpert\Adroit\Domain\DomainPayload;
use bitExpert\Adroit\Responder\HttpStatusCodeResponder;
use bitExpert\Adroit\Responder\Responder;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Unit test for {@link \bitExpert\Adroit\Responder\Resolver\NegotiatingResponderResolver}.
 */
class NegotiatingResponderResolverUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var \bitExpert\Adroit\Domain\DomainPayload
     */
    protected $domainPayload;
    /**
     * @var ContentNegotiationManager
     */
    protected $manager;
    /**
     * @var NegotiatingResponderResolver
     */
    protected $resolver;
    /**
     * @var ResponderResolver
     */
    protected $resolver1;
    /**
     * @var ResponderResolver
     */
    protected $resolver2;
    /**
     * @var Responder
     */
    protected $notAcceptedResponder;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $this->request = $this->getMock(ServerRequestInterface::class);
        $this->domainPayload = new DomainPayload('');
        $this->manager = $this->getMock(ContentNegotiationManager::class, [], [], '', false);
        $this->resolver1 = $this->getMock(ResponderResolver::class);
        $this->resolver2 = $this->getMock(ResponderResolver::class);
        $this->notAcceptedResponder = $this->getMock(Responder::class);
        $this->resolver = new NegotiatingResponderResolver(
            $this->manager,
            $this->notAcceptedResponder,
            [
                'text/html' => $this->resolver1,
                'text/vcard' => [
                    new \stdClass(),
                    new \stdClass()
                ],
                'application/json' =>
                    [
                        new \stdClass(),
                        $this->resolver2,
                        $this->resolver2
                    ]
            ]
        );
    }

    /**
     * @test
     */
    public function noBestMatchWillReturnNotAcceptedResponder()
    {
        $this->manager->expects($this->once())
            ->method('getBestMatch')
            ->will($this->returnValue(null));

        $responder = $this->resolver->resolve($this->request, $this->domainPayload);

        $this->assertSame($this->notAcceptedResponder, $responder);
    }

    /**
     * @test
     */
    public function whenNoRespondersExistForBestMatchWillReturnNotAcceptedResponder()
    {
        $this->manager->expects($this->once())
            ->method('getBestMatch')
            ->will($this->returnValue('application/custom'));

        $responder = $this->resolver->resolve($this->request, $this->domainPayload);

        $this->assertSame($this->notAcceptedResponder, $responder);
    }

    /**
     * @test
     */
    public function oneConfiguredResponderForContentTypeGetsReturned()
    {
        $returnValue = $this->getMock(Responder::class);

        $this->manager->expects($this->once())
            ->method('getBestMatch')
            ->will($this->returnValue('text/html'));
        $this->resolver1->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($returnValue));

        $responder = $this->resolver->resolve($this->request, $this->domainPayload);

        $this->assertEquals($returnValue, $responder);
    }

    /**
     * @test
     */
    public function firstMatchingResponderForContentTypeGetsReturned()
    {
        $returnValue = $this->getMock(Responder::class);

        $this->manager->expects($this->once())
            ->method('getBestMatch')
            ->will($this->returnValue('application/json'));
        $this->resolver2->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($returnValue));

        $responder = $this->resolver->resolve($this->request, $this->domainPayload);

        $this->assertEquals($returnValue, $responder);
    }

    /**
     * @test
     */
    public function respondersNotImplementingTheRequiredInterfaceWillBeIgnored()
    {
        $this->manager->expects($this->once())
            ->method('getBestMatch')
            ->will($this->returnValue('text/vcard'));


        $responder = $this->resolver->resolve($this->request, $this->domainPayload);

        $this->assertSame($this->notAcceptedResponder, $responder);
    }
}
