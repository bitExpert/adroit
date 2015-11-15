<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Action\Resolver;

use Psr\Http\Message\ServerRequestInterface;

/**
 * An action resolver will provide a {@link \bitExpert(Adriot\Action\Action} instance
 * for the given $actionToken.
 *
 * @api
 */
interface ActionResolver
{
    /**
     * Creates and returns an action object from the given {@link \Psr\Http\Message\ServerRequestInterface}.
     * If no matching {@link \bitExpert\Adroit\Action\Action} instance could be found, null will be
     * returned.
     *
     * @param ServerRequestInterface $request
     * @return \bitExpert\Adroit\Action\Action|null
     */
    public function resolve(ServerRequestInterface $request);
}
