<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Stratigility\MiddlewareInterface;
use Psr\Log\NullLogger;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;

/**
 * Unit test for {@link \bitExpert\Adroit\WebApplication}.
 *
 * @covers \bitExpert\Adroit\WebApplication
 */
class WebApplicationUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var ResponseInterface
     */
    protected $response;
    /**
     * @var WebApplication
     */
    protected $application;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->application = new WebApplication();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @requires extension xdebug
     * @requires function xdebug_get_headers
     */
    public function sendsHeadersCorrectly()
    {
        $app = $this->application;
        $headersToSend = array(
            'Content-Type' => 'application/json',
            'Location' => 'http://someawesomedomain.com'
        );

        $response = $this->response;
        foreach ($headersToSend as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $app->run($this->request, $response);

        $sentHeaders = xdebug_get_headers();
        foreach ($headersToSend as $name => $value) {
            $headerStr = sprintf('%s: %s', $name, $value);
            $this->assertTrue(in_array($headerStr, $sentHeaders));
        }
    }
}
