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

use bitExpert\Adroit\Domain\DomainPayloadInterface;
use Exception;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * The HttpStatusCodeResponder creates a response for the given $statusCode. The
 * responder is used in cases where it is needed to return an "error" to the client,
 * e.g. when the {@link \bitExpert\Adroit\Responder\Resolver\NegotiatingResponderResolver}
 * is not able to resolve a responder for the requested media type.
 *
 * @api
 */
class HttpStatusCodeResponder implements Responder
{
    /**
     * @var int
     */
    protected $statusCode;

    /**
     * Creates a new {@link \itExpert\Adroit\Responder\HttpStatusCodeResponder}.
     *
     * @param int $statusCode
     */
    public function __construct($statusCode)
    {
        $this->statusCode = (int) $statusCode;
    }

    /**
     * {@inheritDoc}
     * @throws RuntimeException
     */
    public function buildResponse(DomainPayloadInterface $domainPayload, ResponseInterface $response)
    {
        try {
            return $response->withStatus($this->statusCode);
        } catch (Exception $e) {
            throw new RuntimeException('Response object could not be instantiated! ' . $e->getMessage());
        }
    }
}
