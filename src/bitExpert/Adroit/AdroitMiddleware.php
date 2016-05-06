<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit;

use bitExpert\Adroit\Action\ActionMiddleware;
use bitExpert\Adroit\Domain\Payload;
use bitExpert\Adroit\Action\Resolver\ActionResolverMiddleware;
use bitExpert\Adroit\Responder\Resolver\ResponderResolverMiddleware;
use bitExpert\Adroit\Responder\ResponderMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Stratigility\MiddlewarePipe;

/**
 * MiddlewarePipe implementation for an Adroit web application.
 *
 * @api
 */
class AdroitMiddleware extends MiddlewarePipe
{
    /**
     * @var callable[]
     */
    protected $beforeActionMiddlewares;
    /**
     * @var callable[]
     */
    protected $beforeResponderMiddlewares;
    /**
     * @var callable[]
     */
    protected $afterResponderMiddlewares;
    /**
     * @var callable
     */
    protected $actionMiddleware;
    /**
     * @var callable
     */
    protected $responderMiddleware;

    /**
     * Creates a new {\bitExpert\Adroit\AdroitMiddleware}.
     *
     * @param ActionMiddleware $actionMiddleware
     * @param ResponderMiddleware $responderMiddleware
     */
    public function __construct(
        ActionMiddleware $actionMiddleware,
        ResponderMiddleware $responderMiddleware)
    {
        parent::__construct();

        $this->actionMiddleware = $actionMiddleware;
        $this->responderMiddleware = $responderMiddleware;

        $this->beforeActionMiddlewares = [];
        $this->beforeResponderMiddlewares = [];
        $this->afterResponderMiddlewares = [];
    }


    /**
     * Adds the given middleware to the pipe before the action middleware
     * (chainable)
     *
     * @param callable $middleware
     * @return $this
     */
    public function beforeAction(callable $middleware)
    {
        $this->beforeActionMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * Adds the given middleware to the pipe after the action middleware and before the responder middleware
     * (chainable)
     *
     * @param callable $middleware
     * @return $this
     */
    public function beforeResponder(callable $middleware)
    {
        $this->beforeResponderMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * Adds the given middleware to the pipe after the responder middleware
     * (chainable)
     *
     * @param callable $middleware
     * @return $this
     */
    public function afterResponder(callable $middleware)
    {
        $this->afterResponderMiddlewares[] = $middleware;
        return $this;
    }


    /**
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $out = null)
    {
        $this->initialize();
        return parent::__invoke($request, $response, $out);
    }

    /**
     * Pipes all given middlewares
     * @param callable[] $middlewares
     */
    protected function pipeEach(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            $this->pipe($middleware);
        }
    }

    /**
     * Initializes the application by piping the fixed middlewares (routing, action, responder)
     * and the configured middlewares in the right order
     */
    protected function initialize()
    {
        $this->pipeEach($this->beforeActionMiddlewares);
        $this->pipe($this->actionMiddleware);
        $this->pipeEach($this->beforeResponderMiddlewares);
        $this->pipe($this->responderMiddleware);
        $this->pipeEach($this->afterResponderMiddlewares);
    }

    /**
     * Creates a {@link bitExpert\Adroit\AdroitMiddleware using the default ADR middlewares
     *
     * @param String $routingResultAttribute
     * @param \bitExpert\Adroit\Action\Resolver\ActionResolver[] $actionResolvers
     * @param \bitExpert\Adroit\Responder\Resolver\ResponderResolver[] $responderResolvers
     * @param $responderResolvers
     * @return static
     */
    public static function createDefault($routingResultAttribute, array $actionResolvers, array $responderResolvers)
    {
        $actionMiddleware = new ActionResolverMiddleware($actionResolvers, $routingResultAttribute, Payload::class);
        $responderMiddleware = new ResponderResolverMiddleware($responderResolvers, Payload::class);

        return new static($actionMiddleware, $responderMiddleware);
    }
}
