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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Stratigility\MiddlewarePipe;

/**
 * MiddlewarePipe implementation for an Adroit web application.
 *
 * @api
 */
class WebApplication extends MiddlewarePipe
{
    /**
     * @var EmitterInterface
     */
    protected $emitter;

    /**
     * Creates a new {\bitExpert\Adroit\WebApplication}.
     *
     * @param EmitterInterface $emitter
     */
    public function __construct(EmitterInterface $emitter = null)
    {
        parent::__construct();

        if (null === $emitter) {
            $emitter = new SapiEmitter();
        }
        $this->emitter = $emitter;
    }

    /**
     * Runs the application by invoking itself with the request and response, and emitting the returned response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response)
    {
        $response = parent::__invoke($request, $response);
        $this->emitter->emit($response);
    }
}
