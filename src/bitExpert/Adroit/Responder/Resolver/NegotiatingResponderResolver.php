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

use bitExpert\Adroit\Accept\ContentNegotiationManager;
use bitExpert\Adroit\Responder\HttpStatusCodeResponder;
use bitExpert\Adroit\Responder\Responder;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The ContentNegotiatingResponderResolver does not resolve responders itself, but
 * delegates to the configured $responderResolvers. This responder resolver uses
 * the requested media type to select a suitable Responder for a request. The
 * requested media type is determined through the configured
 * {@link \bitExpert\Adroit\Accept\ContentNegotiationManager}.
 *
 * @api
 */
class NegotiatingResponderResolver implements ResponderResolver
{
    /**
     * @var ResponderResolver[]
     */
    protected $responderResolver;
    /**
     * @var ContentNegotiationManager
     */
    protected $negotiationManager;

    /**
     * Creates a new {@link \bitExpert\Adroit\Responder\Resolver\NegotiatingResponderResolver}.
     *
     * @param ContentNegotiationManager $negotiationManager
     * @param ResponderResolver[] $responderResolver
     */
    public function __construct(ContentNegotiationManager $negotiationManager, array $responderResolver)
    {
        $this->negotiationManager = $negotiationManager;
        $this->responderResolver = $responderResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(ServerRequestInterface $request, $identifier)
    {
        $type = $this->negotiationManager->getBestMatch($request, array_keys($this->responderResolver));
        if (null !== $type) {
            $resolvers = [];
            if (isset($this->responderResolver[$type])) {
                $resolvers = $this->responderResolver[$type];
                if (!is_array($resolvers)) {
                    $resolvers = [$resolvers];
                }
            }

            foreach ($resolvers as $resolver) {
                if (!$resolver instanceof ResponderResolver) {
                    continue;
                }

                $responder = $resolver->resolve($request, $identifier);
                if ($responder instanceof Responder) {
                    // step out of the loop when a responder could be found
                    // by the resolvers. First resolvers wins!
                    return $responder;
                }
            }
        }

        // in case no matching responder could be found for the requested type we need
        // to notify the client with an "406 Not acceptable" response.
        return new HttpStatusCodeResponder(406);
    }
}
