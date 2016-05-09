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

use bitExpert\Adroit\Action\Action;
use bitExpert\Adroit\Action\Resolver\ActionResolverMiddleware;
use bitExpert\Adroit\Action\Resolver\DefaultActionResolverMiddleware;
use bitExpert\Adroit\Action\Executor\ActionExecutorMiddleware;
use bitExpert\Adroit\Action\Executor\DefaultActionExecutorMiddleware;
use bitExpert\Adroit\Responder\Resolver\ResponderResolverMiddleware;
use bitExpert\Adroit\Responder\Resolver\DefaultResponderResolverMiddleware;
use bitExpert\Adroit\Responder\Executor\ResponderExecutorMiddleware;
use bitExpert\Adroit\Responder\DefaultResponderExecutorMiddleware;
use bitExpert\Adroit\Responder\Responder;
use bitExpert\Adroit\Domain\Payload;
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
    protected static $actionAttribute = Action::class;
    protected static $payloadAttribute = Payload::class;
    protected static $responderAttribute = Responder::class;

    /**
     * @var string
     */
    protected $routingResultAttribute;
    /**
     * @var \bitExpert\Adroit\Action\ActionResolver|
     */
    protected $actionResolvers;
    /**
     * @var
     */
    protected $responderResolvers;
    /**
     * @var callable[]
     */
    protected $beforeActionResolverMiddlewares;
    /**
     * @var callable[]
     */
    protected $beforeActionExecutorMiddlewares;
    /**
     * @var callable[]
     */
    protected $beforeResponderResolverMiddlewares;
    /**
     * @var callable[]
     */
    protected $beforeResponderExecutorMiddlewares;


    protected $actionResolverMiddleware;
    protected $actionExecutorMiddleware;
    protected $responderResolverMiddleware;
    protected $responderExecutorMiddleware;

    /**
     * Creates a new {\bitExpert\Adroit\AdroitMiddleware}.
     */
    public function __construct($routingResultAttribute, array $actionResolvers, array $responderResolvers)
    {
        parent::__construct();

        $this->routingResultAttribute = $routingResultAttribute;
        $this->actionResolvers = $actionResolvers;
        $this->responderResolvers = $responderResolvers;

        $this->beforeActionResolverMiddlewares = [];
        $this->beforeActionExecutorMiddlewares = [];
        $this->beforeResponderResolverMiddlewares = [];
        $this->beforeResponderExecutorMiddlewares = [];
    }


    /**
     * Adds the given middleware to the pipe before the action middleware
     * (chainable)
     *
     * @param callable $middleware
     * @return $this
     */
    public function beforeResolveAction(callable $middleware)
    {
        $this->beforeActionResolverMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * Adds the given middleware to the pipe before the action middleware
     * (chainable)
     *
     * @param callable $middleware
     * @return $this
     */
    public function beforeExecuteAction(callable $middleware)
    {
        $this->beforeActionExecutorMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * Adds the given middleware to the pipe after the action middleware and before the responder middleware
     * (chainable)
     *
     * @param callable $middleware
     * @return $this
     */
    public function beforeResolveResponder(callable $middleware)
    {
        $this->beforeResponderResolverMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * Adds the given middleware to the pipe after the action middleware and before the responder middleware
     * (chainable)
     *
     * @param callable $middleware
     * @return $this
     */
    public function beforeExecuteResponder(callable $middleware)
    {
        $this->beforeResponderExecutorMiddlewares[] = $middleware;
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
        $actionResolverMiddleware = $this->getActionResolverMiddleware(
            $this->actionResolvers,
            $this->routingResultAttribute,
            self::$actionAttribute
        );

        $actionExecutorMiddleware = $this->getActionExecutorMiddleware(
            self::$actionAttribute,
            self::$payloadAttribute
        );

        $responderResolverMiddleware = $this->getResponderResolverMiddleware(
            $this->responderResolvers,
            self::$payloadAttribute,
            self::$responderAttribute
        );

        $responderExecutorMiddleware = $this->getResponderExecutorMiddleware(
            self::$responderAttribute,
            self::$payloadAttribute
        );

        $this->pipeEach($this->beforeActionResolverMiddlewares);
        $this->pipe($actionResolverMiddleware);
        $this->pipeEach($this->beforeActionExecutorMiddlewares);
        $this->pipe($actionExecutorMiddleware);
        $this->pipeEach($this->beforeResponderResolverMiddlewares);
        $this->pipe($responderResolverMiddleware);
        $this->pipeEach($this->beforeResponderExecutorMiddlewares);
        $this->pipe($responderExecutorMiddleware);
    }

    /**
     * @param \bitExpert\Adroit\Action\Resolver\ActionResolver[] $actionResolvers
     * @param string $routingResultAttribute
     * @param string $actionAttribute
     * @return ActionResolverMiddleware
     */
    protected function getActionResolverMiddleware($actionResolvers, $routingResultAttribute, $actionAttribute)
    {
        return new ActionResolverMiddleware($actionResolvers, $routingResultAttribute, $actionAttribute);
    }

    /**
     * @param string $actionAttribute
     * @param string $payloadAttribute
     * @return ActionExecutorMiddleware
     */
    protected function getActionExecutorMiddleware($actionAttribute, $payloadAttribute)
    {
        return new ActionExecutorMiddleware($actionAttribute, $payloadAttribute);
    }

    /**
     * @param \bitExpert\Adroit\Responder\Resolver\ResponderResolver[] $responderResolvers
     * @param string $payloadAttribute
     * @param string $responderAttribute
     * @return ResponderResolverMiddleware
     */
    protected function getResponderResolverMiddleware($responderResolvers, $payloadAttribute, $responderAttribute)
    {
        return new ResponderResolverMiddleware($responderResolvers, $payloadAttribute, $responderAttribute);
    }

    /**
     * @param string $responderAttribute
     * @param string $payloadAttribute
     * @return ResponderExecutorMiddleware
     */
    protected function getResponderExecutorMiddleware($responderAttribute, $payloadAttribute)
    {
        return new ResponderExecutorMiddleware($responderAttribute, $payloadAttribute);
    }

    /**
     * Creates a {@link bitExpert\Adroit\AdroitMiddleware using the default ADR middlewares
     *
     * @param String $routingResultAttribute
     * @param \bitExpert\Adroit\Action\Resolver\ActionResolver[] $actionResolvers
     * @param \bitExpert\Adroit\Responder\Resolver\ResponderResolver[] $responderResolvers
     * @return AdroitMiddleware
     */
    public static function create($routingResultAttribute, array $actionResolvers, array $responderResolvers)
    {
        return new static($routingResultAttribute, $actionResolvers, $responderResolvers);
    }
}
