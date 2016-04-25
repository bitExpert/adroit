<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Responder;

use bitExpert\Adroit\Domain\DomainPayload;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;

/**
 * Unit test for {@link \bitExpert\Adroit\Responder\JsonResponder}.
 *
 * @covers \bitExpert\Adroit\Responder\JsonResponder
 */
class JsonResponderUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonResponder
     */
    protected $responder;
    /**
     * @var Response
     */
    protected $response;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $this->response = new Response();
        $this->responder = new JsonResponder();
    }

    /**
     * @test
     */
    public function responderConvertsModelToJson()
    {
        $model = ['1' => 'demo', '2a' => 'test'];
        $domainPayload = new DomainPayload('test', $model);
        $responder = $this->responder;
        $response = $responder($domainPayload, $this->response);
        $response->getBody()->rewind();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertJson($response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function additionalHttpHeadersGetAppendedToResponse()
    {
        $domainPayload = new DomainPayload('test');
        $this->responder->setHeaders(['X-Sender' => 'PHPUnit Testcase']);
        $responder = $this->responder;
        $response = $responder($domainPayload, $this->response);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertTrue($response->hasHeader('X-Sender'));
    }

    /**
     * @test
     */
    public function contentTypeCantBeChanged()
    {
        $domainPayload = new DomainPayload('test');
        $this->responder->setHeaders(['Content-Type' => 'my/type']);
        $responder = $this->responder;
        $response = $responder($domainPayload, $this->response);

        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
    }
}
