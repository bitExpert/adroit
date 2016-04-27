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
use bitExpert\Pathfinder\RoutingResult;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ActionResolverMiddleware extends CallableResolverMiddleware implements ActionMiddleware
{
    protected $routingResultAttribute;
    protected $domainPayloadAttribute;

    public function __construct($resolvers, $routingResultAttribute, $domainPayloadAttribute)
    {
        parent::__construct($resolvers);

        $this->routingResultAttribute = $routingResultAttribute;
        $this->domainPayloadAttribute = $domainPayloadAttribute;
    }

    /**
     * @inheritdoc
     * @throws ResolveException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $actionIdentifier = $this->getActionIdentifier($request);

        if (null === $actionIdentifier) {
            throw new ResolveException('Could not determine action for request');
        }

        $params = $this->getParams($request);

        $action = $this->resolve($request, $actionIdentifier);

        // inject params determined by the router into the request
        $request = $this->injectParams($request, $params);

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
     * Injects given params to the given request and returns a new {@link \Psr\Http\Message\ServerRequestInterface}
     *
     * @param ServerRequestInterface $request
     * @param array $params
     * @return ServerRequestInterface
     */
    protected function injectParams(ServerRequestInterface $request, array $params = [])
    {
        $params = array_merge($request->getQueryParams(), $params);
        // setting given params as query params
        return $request->withQueryParams($params);
    }

    /**
     * Returns the action identifier
     *
     * @param ServerRequestInterface $request
     * @throws \RuntimeException
     * @return mixed
     */
    protected function getActionIdentifier(ServerRequestInterface $request)
    {
        $routingResult = $this->getRoutingResult($request);

        if ($routingResult->failed()) {
            $this->logger->warning(sprintf(
                'Routing result has been marked as failed. Returning null'
            ));

            return null;
        }

        $route = $routingResult->getRoute();

        return $route->getTarget();
    }

    /**
     * Returns used params
     *
     * @param ServerRequestInterface $request
     * @throws \RuntimeException
     * @return RoutingResult
     */
    protected function getParams(ServerRequestInterface $request)
    {
        return $this->getRoutingResult($request)->getParams();
    }

    /**
     * @param ServerRequestInterface $request
     * @return RoutingResult
     * @throws \RuntimeException
     */
    protected function getRoutingResult(ServerRequestInterface $request)
    {
        $routingResult = $request->getAttribute($this->routingResultAttribute);

        if (null === $routingResult) {
            throw new ResolveException(sprintf(
                'No routing result found in request attribute "%s"',
                $this->routingResultAttribute
            ));
        }

        return $routingResult;
    }
}
