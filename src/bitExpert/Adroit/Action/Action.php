<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types = 1);

namespace bitExpert\Adroit\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * An Action is an adapter between the contents of an incoming HTTP request and the
 * corresponding business logic that should be executed to process this request. An
 * appropriate Action for each request will be selected by the
 * {@link \bitExpert\Adroit\Action\Resolver\ActionResolver}.
 *
 * This interface is primarily meant for documentation use. You MAY use it but a callable will be fine, too.
 *
 * @api
 */
interface Action
{
    /**
     * Executes the action for the given $request. Will return either a
     * {@link \Psr\Http\Message\ServerRequestInterface;} instance which gets sent to
     * the client "as-is" or a {@link \bitExpert\Adroit\Domain\Payload} instance
     * which will be evaluated by the {@link \bitExpert\Adroit\Responder\ResponderMiddleware}.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return \bitExpert\Adroit\Domain\Payload|ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response);
}
