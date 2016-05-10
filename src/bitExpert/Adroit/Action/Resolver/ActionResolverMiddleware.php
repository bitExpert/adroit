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

use bitExpert\Adroit\Resolver\AbstractResolverMiddleware;
use bitExpert\Adroit\Resolver\ResolveException;
use bitExpert\Adroit\Action\ActionExecutionException;
use bitExpert\Adroit\Resolver\Resolver;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ActionResolverMiddleware extends AbstractResolverMiddleware
{
    /**
     * @var string
     */
    protected $routingResultAttribute;
    /**
     * @var string
     */
    protected $actionAttribute;

    /**
     * @param ActionResolver[] $resolvers
     * @param string $actionAttribute
     * @param string $routingResultAttribute
     * @throws \InvalidArgumentException
     */
    public function __construct(array $resolvers, $routingResultAttribute, $actionAttribute)
    {
        parent::__construct($resolvers);

        $this->actionAttribute = $actionAttribute;
        $this->routingResultAttribute = $routingResultAttribute;
    }

    /**
     * @inheritdoc
     * @throws ActionResolveException
     * @throws ActionExecutionException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        try {
            /* @var $action callable */
            $action = $this->resolve($request);
        } catch (ResolveException $e) {
            throw new ActionResolveException('None of given resolvers could resolve an action', $e->getCode(), $e);
        }


        if ($next) {
            $response = $next($request->withAttribute($this->actionAttribute, $action), $response);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    protected function isValidResolver(Resolver $resolver)
    {
        return ($resolver instanceof ActionResolver);
    }

    /**
     * @inheritdoc
     */
    protected function getIdentifier(ServerRequestInterface $request)
    {
        return $request->getAttribute($this->routingResultAttribute);
    }
}
