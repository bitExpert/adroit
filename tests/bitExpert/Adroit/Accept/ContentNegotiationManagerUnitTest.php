<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Accept;

use Negotiation\AcceptHeader;
use Negotiation\NegotiatorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Request;

/**
 * Unit test for {@link \bitExpert\Adroit\Accept\ContentNegotiationManager}.
 *
 * @covers \bitExpert\Adroit\Accept\ContentNegotiationManager
 */
class ContentNegotiationManagerUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var \Negotiation\NegotiatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $negotiator;
    /**
     * @var ContentNegotiator
     */
    protected $manager;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $this->request = new Request();
        $this->negotiator = $this->getMock(NegotiatorInterface::class);
        $this->manager = new ContentNegotiationManager($this->negotiator);
    }

    /**
     * @test
     */
    public function willReturnAcceptHeaderValueWhenMatchWasFound()
    {
        $this->request = $this->request->withHeader('Accept', 'text/html');
        $this->negotiator->expects($this->once())
            ->method('getBest')
            ->will($this->returnValue(new AcceptHeader('text/html', 1.0)));

        $manager = new ContentNegotiationManager($this->negotiator);
        $bestMatch = $manager->getBestMatch($this->request);

        $this->assertSame('text/html', $bestMatch);
    }

    /**
     * @test
     */
    public function willReturnNullWhenMatchWasNotFound()
    {
        $this->request = $this->request->withHeader('Accept', 'text/html');
        $this->negotiator->expects($this->once())
            ->method('getBest')
            ->will($this->returnValue(null));

        $manager = new ContentNegotiationManager($this->negotiator);
        $bestMatch = $manager->getBestMatch($this->request);

        $this->assertNull($bestMatch);
    }

    /**
     * @test
     */
    public function whenNoNegotiatorWasGivenAFormatNegotiatorWillBeUsedAsFallback()
    {
        $manager = new ContentNegotiationManager();
        $bestMatch = $manager->getBestMatch($this->request);

        $this->assertNull($bestMatch);
    }
}
