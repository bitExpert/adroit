<?php

namespace bitExpert\Adroit\Action;

use bitExpert\Adroit\Domain\DomainPayload;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * An Action is an adapter between the contents of an incoming HTTP request and the
 * corresponding business logic that should be executed to process this request. An
 * appropriate Action for each request will be selected by the
 * {@link \bitExpert\Adroit\Web\Action\Resolver\Resolver}.
 *
 * @api
 */
interface Action
{
    /**
     * Prepares and executes the action for the given $request. Will return either a
     * {@link \Psr\Http\Message\ServerRequestInterface;} instance which gets sent to
     * the client "as-is" or a {@link \bitExpert\Adroit\Domain\DomainPayload} instance
     * which will be evaluated by the {@link \bitExpert\Adroit\HttpKernel}.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return DomainPayload|ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response);
}
