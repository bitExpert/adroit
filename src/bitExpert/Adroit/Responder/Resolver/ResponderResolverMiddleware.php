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

use bitExpert\Adroit\Resolver\AbstractResolverMiddleware;
use bitExpert\Adroit\Resolver\ResolveException;
use bitExpert\Adroit\Responder\ResponderMiddleware;
use bitExpert\Adroit\Resolver\Resolver;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ResponderResolverMiddleware extends AbstractResolverMiddleware implements ResponderMiddleware
{
    /**
     * @var string
     */
    protected $domainPayloadAttribute;

    /**
     * @param \bitExpert\Adroit\Responder\Resolver\ResponderResolver|\bitExpert\Adroit\Responder\Resolver\ResponderResolver[] $resolvers
     * @param string $domainPayloadAttribute
     * @throws \InvalidArgumentException
     */
    public function __construct($resolvers, $domainPayloadAttribute)
    {
        parent::__construct($resolvers);

        $this->domainPayloadAttribute = $domainPayloadAttribute;
    }

    /**
     * @inheritdoc
     * @throws ResponderResolveException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $domainPayload = $request->getAttribute($this->domainPayloadAttribute);

        // if the return value is a response instance, directly return it
        if ($domainPayload instanceof ResponseInterface) {
            $this->logger->debug('Received response. Returning directly.');
            $response = $domainPayload;

            if ($next) {
                $response = $next($request, $response);
            }

            return $response;
        }

        try {
            $responder = $this->resolve($request);
        } catch (ResolveException $e) {
            throw new ResponderResolveException('None of given resolvers could resolve a responder', $e->getCode(), $e);
        }

        $response = $responder($domainPayload, $response);

        if ($next) {
            $response = $next($request, $response);
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
    protected function isValidResolver(Resolver $resolver)
    {
        return ($resolver instanceof ResponderResolver);
    }
}
