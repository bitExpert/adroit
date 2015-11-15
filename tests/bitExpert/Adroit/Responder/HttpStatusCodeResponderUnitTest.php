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
 * Unit test for {@link \bitExpert\Adroit\Responder\HttpStatusCodeResponder}.
 *
 * @covers \bitExpert\Adroit\Responder\HttpStatusCodeResponder
 */
class HttpStatusCodeResponderUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $this->response = new Response();
    }

    /**
     * @test
     */
    public function responseCodeIsPassedToResponseObject()
    {
        $responder = new HttpStatusCodeResponder(200);
        $domainPayload = new DomainPayload('test');
        $response = $responder->buildResponse($domainPayload, $this->response);

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function nonIntegerResponseCodeWillThrowAnException()
    {
        $domainPayload = new DomainPayload('test');
        $responder = new HttpStatusCodeResponder('hello');

        $responder->buildResponse($domainPayload, $this->response);
    }
}
