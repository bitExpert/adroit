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

namespace bitExpert\Adroit\Responder\Resolver;

use bitExpert\Adroit\Resolver\AbstractResolverMiddleware;
use bitExpert\Adroit\Resolver\ResolveException;
use bitExpert\Adroit\Responder\ResponderExecutionException;
use bitExpert\Adroit\Responder\ResponderMiddleware;
use bitExpert\Adroit\Resolver\Resolver;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use bitExpert\Adroit\Responder\Resolver\ResponderResolver;

class ResponderResolverMiddleware extends AbstractResolverMiddleware
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
     * @param ResponderResolver[] $resolvers
     * @param string $domainPayloadAttribute
     * @param string $responderAttribute
     * @throws \InvalidArgumentException
     */
    public function __construct(array $resolvers, string $domainPayloadAttribute, string $responderAttribute)
    {
        parent::__construct($resolvers);

        $this->domainPayloadAttribute = $domainPayloadAttribute;
        $this->responderAttribute = $responderAttribute;
    }

    /**
     * @inheritdoc
     * @throws ResponderResolveException
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    ) : ResponseInterface {
        $domainPayload = $request->getAttribute($this->domainPayloadAttribute);

        // if the return value is a response instance, directly return it
        if ($domainPayload instanceof ResponseInterface) {
            $this->logger->debug('Received response. Skipping resolvers.');
            $response = $domainPayload;

            if ($next) {
                $response = $next($request->withAttribute($this->responderAttribute, $response), $response);
            }

            return $response;
        }

        try {
            $responder = $this->resolve($request);
        } catch (ResolveException $e) {
            throw new ResponderResolveException('None of given resolvers could resolve a responder', $e->getCode(), $e);
        }

        if ($next) {
            $response = $next($request->withAttribute($this->responderAttribute, $responder), $response);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    protected function getIdentifier(ServerRequestInterface $request)
    {
        $domainPayload = $request->getAttribute($this->domainPayloadAttribute);

        if (null === $domainPayload) {
            return null;
        }

        return $domainPayload->getType();
    }

    /**
     * @inheritdoc
     */
    protected function isValidResolver(Resolver $resolver) : bool
    {
        return ($resolver instanceof ResponderResolver);
    }
}
