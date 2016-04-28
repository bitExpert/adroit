<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Action;

use bitExpert\Adroit\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * An action middleware fetches the routing attribute determines which action to use for the defined target
 * executes it and sets the result to the defined domain payload attribute
 *
 * @package bitExpert\Adroit\Action
 */
interface ActionMiddleware
{
    /**
     * Returns the attribute the domain payload will be stored in after having executed the action
     * @return String
     */
    public function getDomainPayloadAttribute();

    /**
     * PSR-7 middleware signatured magic method
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null);
}
