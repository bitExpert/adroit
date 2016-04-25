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

use bitExpert\Pathfinder\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\EmitterInterface;

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
        $this->request = new ServerRequest([], [], '/', 'GET');
        $this->response = new Response();
        $emitter = $this->getMock(EmitterInterface::class);
        $this->application = WebApplication::createDefault(null, [], [], $emitter);

        $this->application->addRoute(
            Route::get('/')->to(function (ServerRequestInterface $request, ResponseInterface $response) {
                //
            })->named('home')
        );
    }

    /**
     * @test
     */
    public function sendsHeadersCorrectly()
    {

    }
}
