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

use bitExpert\Adroit\Action\ActionMiddleware;
use bitExpert\Adroit\Resolver\CallableResolverMiddleware;
use bitExpert\Adroit\Resolver\ResolveException;
use bitExpert\Adroit\Resolver\Resolver;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ActionResolverMiddleware extends CallableResolverMiddleware implements ActionMiddleware
{
    /**
     * @var string
     */
    protected $routingResultAttribute;
    /**
     * @var string
     */
    protected $domainPayloadAttribute;

    public function __construct($resolvers, $routingResultAttribute, $domainPayloadAttribute)
    {
        parent::__construct($resolvers);

        $this->routingResultAttribute = $routingResultAttribute;
        $this->domainPayloadAttribute = $domainPayloadAttribute;
    }

    /**
     * @inheritdoc
     * @throws ActionResolveException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $actionIdentifier = $this->getActionIdentifier($request);

        if (null === $actionIdentifier) {
            throw new ActionResolveException('Could not determine action for request');
        }

        try {
            /* @var $action callable */
            $action = $this->resolve($request, $actionIdentifier);
        } catch (ResolveException $e) {
            throw new ActionResolveException('None of given resolvers could resolve an action', $e->getCode(), $e);
        }


        // execute the action
        $responseOrPayload = $action($request, $response);

        if ($next) {
            $response = $next($request->withAttribute($this->domainPayloadAttribute, $responseOrPayload), $response);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getDomainPayloadAttribute()
    {
        return $this->domainPayloadAttribute;
    }

    /**
     * @inheritdoc
     */
    protected function isValidResolver (Resolver $resolver)
    {
        return ($resolver instanceof ActionResolver);
    }

    /**
     * Returns the action identifier
     *
     * @param ServerRequestInterface $request
     * @return mixed
     */
    protected function getActionIdentifier(ServerRequestInterface $request)
    {
        return $request->getAttribute($this->routingResultAttribute);
    }
}
