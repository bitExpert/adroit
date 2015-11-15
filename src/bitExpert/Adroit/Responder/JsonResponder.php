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
 * Responder to convert the given model into JSON format.
 *
 * @api
 */
class JsonResponder implements Responder
{
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Set additional HTTP headers.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * {@inheritDoc}
     * @throws RuntimeException
     */
    public function buildResponse(DomainPayloadInterface $domainPayload, ResponseInterface $response)
    {
        try {
            $response->getBody()->rewind();
            $response->getBody()->write(json_encode($domainPayload->getValues()));

            $headers = array_merge($this->headers, ['Content-Type' => 'application/json']);
            foreach ($headers as $header => $value) {
                $response = $response->withHeader($header, $value);
            }

            return $response->withStatus(200);
        } catch (Exception $e) {
            throw new RuntimeException('Response object could not be instantiated! ' . $e->getMessage());
        }
    }
}
