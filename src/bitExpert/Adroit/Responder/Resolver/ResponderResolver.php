<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Responder\Resolver;

use bitExpert\Adroit\Domain\DomainPayloadInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A responder resolver will provide a {@link \bitExpert(Adriot\Action\Action} instance
 * for the given $actionToken,
 *
 * @api
 */
interface ResponderResolver
{
    /**
     * Creates and returns an action object from the given $identifier. If no matching
     * {@link \bitExpert\Adroit\Responder\Responder} instance could be found, null will
     * be returned.
     *
     * @param ServerRequestInterface $request
     * @param DomainPayloadInterface $domainPayload
     * @return \bitExpert\Adroit\Responder\Responder|null
     */
    public function resolve(ServerRequestInterface $request, DomainPayloadInterface $domainPayload);
}
