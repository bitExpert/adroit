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

namespace bitExpert\Adroit\Responder\Executor;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

class ResponderExecutorMiddleware
{
    /**
     * @var string
     */
    protected $responderAttribute;
    /**
     * @var string
     */
    protected $domainPayloadAttribute;

    /**
     * Creates a new {@link \bitExpert\Adroit\Responder\Executor\ResponderExecutorMiddleware}.
     *
     * @param string $responderAttribute
     * @param string $domainPayloadAttribute
     */
    public function __construct(string $responderAttribute, string $domainPayloadAttribute)
    {
        $this->responderAttribute = $responderAttribute;
        $this->domainPayloadAttribute = $domainPayloadAttribute;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     * @throws ResponderExecutionException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $responder = $this->getResponder($request);

        if (!$responder) {
            throw new ResponderExecutionException('Could not find responder in request');
        }

        if ($responder instanceof ResponseInterface) {
            $response = $responder;

            if ($next) {
                $response = $next($request, $response);
            }

            return $response;
        }

        $payload = $this->getPayload($request);

        if (!$payload) {
            throw new ResponderExecutionException('Could not find domain payload in request');
        }

        if (!is_callable($responder)) {
            throw new ResponderExecutionException(sprintf(
                'Could not execute responder "%s" because it is not callable',
                is_object($responder) ? get_class($responder) : (string) $responder
            ));
        }

        $response = $responder($payload, $response);

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    protected function getResponder(ServerRequestInterface $request)
    {
        return $request->getAttribute($this->responderAttribute);
    }

    protected function getPayload(ServerRequestInterface $request)
    {
        return $request->getAttribute($this->domainPayloadAttribute);
    }
}
