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

use bitExpert\Adroit\Routing\PathfinderRoutingMiddleware;
use bitExpert\Adroit\Routing\RoutingMiddleware;
use bitExpert\Adroit\Action\Resolver\ActionResolverMiddleware;
use bitExpert\Adroit\Action\Resolver\DirectActionResolver;
use bitExpert\Adroit\Action\ActionMiddleware;
use bitExpert\Adroit\Domain\DomainPayload;
use bitExpert\Adroit\Responder\Resolver\ResponderResolverMiddleware;
use bitExpert\Adroit\Responder\ResponderMiddleware;
use bitExpert\Pathfinder\Psr7Router;
use bitExpert\Pathfinder\Router;
use bitExpert\Pathfinder\Route;
use bitExpert\Pathfinder\RoutingResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Stratigility\MiddlewarePipe;

/**
 * MiddlewarePipe implementation for an Adroit web application.
 *
 * @api
 */
class WebApplication extends MiddlewarePipe
{
    /**
     * @var EmitterInterface
     */
    protected $emitter;
    /**
     * @var callable[]
     */
    protected $beforeRoutingMiddlewares;
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
    protected $beforeEmitterMiddlewares;
    /**
     * @var callable
     */
    protected $errorHandler;
    /**
     * @var callable
     */
    protected $routingMiddleware;
    /**
     * @var callable
     */
    protected $actionMiddleware;
    /**
     * @var callable
     */
    protected $responderMiddleware;
    /**
     * @var string
     */
    protected $defaultRouteClass;

    /**
     * Creates a new {\bitExpert\Adroit\WebApplication}.
     *
     * @param RoutingMiddleware $routingMiddleware
     * @param ActionMiddleware $actionMiddleware
     * @param ResponderMiddleware $responderMiddleware
     * @param EmitterInterface $emitter
     */
    public function __construct(
        RoutingMiddleware $routingMiddleware,
        ActionMiddleware $actionMiddleware,
        ResponderMiddleware $responderMiddleware,
        EmitterInterface $emitter = null)
    {
        parent::__construct();

        $this->defaultRouteClass = null;
        $this->errorHandler = null;

        $this->routingMiddleware = $routingMiddleware;
        $this->actionMiddleware = $actionMiddleware;
        $this->responderMiddleware = $responderMiddleware;

        if (null === $emitter) {
            $emitter = new SapiEmitter();
        }

        $this->emitter = $emitter;

        $this->beforeRoutingMiddlewares = [];
        $this->beforeActionMiddlewares = [];
        $this->beforeResponderMiddlewares = [];
        $this->beforeEmitterMiddlewares = [];
    }

    /**
     * Sets the exception handler
     * (chainable)
     *
     * @param callable $errorHandler
     * @return WebApplication
     */
    public function setErrorHandler(callable $errorHandler)
    {
        $this->errorHandler = $errorHandler;
        return $this;
    }

    /**
     * Adds the given middleware to the pipe before the routing middleware
     * (chainable)
     *
     * @param callable $middleware
     * @return $this
     */
    public function beforeRouting(callable $middleware)
    {
        $this->beforeRoutingMiddlewares[] = $middleware;
        return $this;
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
     * Adds the given middleware to the pipe after the responder middleware and before the emitter is called
     * (chainable)
     *
     * @param callable $middleware
     * @return $this
     */
    public function beforeEmitter(callable $middleware)
    {
        $this->beforeEmitterMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * Runs the application by invoking itself with the request and response, and emitting the returned response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->initialize();
        $response = parent::__invoke($request, $response, $this->errorHandler);
        $this->emitter->emit($response);
    }

    /**
     * Pipes all given middlewares
     * @param callable[] $middlewares
     */
    protected function pipeAll(array $middlewares)
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
        $this->pipeAll($this->beforeRoutingMiddlewares);
        $this->pipe($this->routingMiddleware);
        $this->pipeAll($this->beforeActionMiddlewares);
        $this->pipe($this->actionMiddleware);
        $this->pipeAll($this->beforeResponderMiddlewares);
        $this->pipe($this->responderMiddleware);
        $this->pipeAll($this->beforeEmitterMiddlewares);
    }

    /**
     * Sets the default route class to use for implicit
     * route creation
     *
     * @param $defaultRouteClass
     * @throws \InvalidArgumentException
     */
    public function setDefaultRouteClass($defaultRouteClass)
    {
        if ($defaultRouteClass === Route::class) {
            $this->defaultRouteClass = $defaultRouteClass;
        } else {
            $routeClass = $defaultRouteClass;
            while ($parent = get_parent_class($routeClass)) {
                if ($parent === Route::class) {
                    $this->defaultRouteClass = $defaultRouteClass;
                    break;
                } else {
                    $routeClass = $parent;
                }
            }

            if ($this->defaultRouteClass !== $defaultRouteClass) {
                throw new \InvalidArgumentException(sprintf(
                    'You tried to set "%s" as default route class which does not inherit "%s"',
                    $defaultRouteClass,
                    Route::class
                ));
            }
        }
    }

    /**
     * Creates a route using given params
     *
     * @param mixed $methods
     * @param string $name
     * @param string $path
     * @param mixed $target
     * @param \bitExpert\Pathfinder\Matcher\Matcher[] $matchers
     * @return Route
     */
    protected function createRoute($methods, $name, $path, $target, array $matchers = [])
    {
        $routeClass = $this->defaultRouteClass ?: Route::class;
        $route = forward_static_call([$routeClass, 'create'], $methods, $path, $target);
        $route = $route->named($name);

        foreach ($matchers as $param => $paramMatchers) {
            $route = $route->ifMatches($param, $paramMatchers);
        }

        return $route;
    }

    /**
     * Adds a GET route
     *
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return WebApplication
     */
    public function get($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute('GET', $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds a POST route
     *
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return WebApplication
     */
    public function post($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute('POST', $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds a PUT route
     *
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return WebApplication
     */
    public function put($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute('PUT', $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds a DELETE route
     *
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return WebApplication
     */
    public function delete($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute('DELETE', $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds an OPTIONS route
     *
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return WebApplication
     */
    public function options($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute('OPTIONS', $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds a PATCH route
     *
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return WebApplication
     */
    public function patch($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute('PATCH', $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds given route to router
     *
     * @param Route $route
     * @throws \InvalidArgumentException
     * @return WebApplication
     */
    public function addRoute(Route $route)
    {
        $this->routingMiddleware->getRouter()->addRoute($route);
        return $this;
    }

    /**
     * Creates a WebApplication instance using the default configuration
     *
     * @param Router|null $router
     * @param ActionResolver[] | ActionResolver $actionResolvers
     * @param ResponderResolver[] | ResponderResolver $responderResolvers
     * @param EmitterInterface|null $emitter
     * @return WebApplication
     */
    public static function createDefault(
        Router $router = null,
        $actionResolvers = [],
        $responderResolvers = [],
        EmitterInterface $emitter = null
    ) {
        $router = ($router !== null) ? $router : new Psr7Router('');
        $actionResolvers = (count($actionResolvers) > 0) ? $actionResolvers : [new DirectActionResolver()];
        $routingMiddleware = new PathfinderRoutingMiddleware($router, RoutingResult::class);
        $actionMiddleware = new ActionResolverMiddleware($actionResolvers, RoutingResult::class, DomainPayload::class);
        $responderMiddleware = new ResponderResolverMiddleware($responderResolvers, DomainPayload::class);

        return new self($routingMiddleware, $actionMiddleware, $responderMiddleware, $emitter);
    }
}
