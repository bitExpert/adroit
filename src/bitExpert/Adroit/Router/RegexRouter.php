<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Router;

use bitExpert\Slf4PsrLog\LoggerFactory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A more sophisticated implementation of an {@link \bitExpert\Adroit\Router\Router}
 * which will map the current request path to a configured action token based on some
 * regex magic.
 *
 * @api
 */
class RegexRouter implements Router
{
    /**
     * @var \Psr\Log\LoggerInterface the logger instance.
     */
    protected $logger;
    /**
     * @var string
     */
    protected $baseURL;
    /**
     * @var string|null
     */
    protected $defaultActionToken;
    /**
     * @var array
     */
    protected $routes;

    /**
     * Creates a new {@link \bitExpert\Adroit\Router\RegexRouter}.
     *
     * @param string $baseURL
     */
    public function __construct($baseURL)
    {
        // completes the base url with a / if not set in configuration
        $this->baseURL = rtrim($baseURL, '/') . '/';
        $this->defaultActionToken = null;
        $this->routes = [];

        $this->logger = LoggerFactory::getLogger(__CLASS__);
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultActionToken($defaultActionToken)
    {
        $this->defaultActionToken = $defaultActionToken;
    }

    /**
     * Sets the routes.
     *
     * @param array $routes
     */
    public function setRoutes(array $routes)
    {
        foreach ($routes as $route) {
            if ($route instanceof RegexRouter) {
                $this->routes = array_merge($this->routes, $route->routes);
                continue;
            }

            if ($route instanceof Route) {
                $methods = $route->getMethods();

                // convert the given route path into the regex needed
                $path = preg_replace('#\[:(.+?)\]#i', '(?P<$1>[^/]+?)/?', $route->getPath());

                foreach ($methods as $method) {
                    if (!isset($this->routes[$method])) {
                        $this->routes[$method] = [];
                    }

                    $this->routes[$method][] = [
                        'path' => $route->getPath(),
                        'pathRegEx' => '#^' . $path . '$#i',
                        'actionToken' => $route->getActionToken(),
                    ];
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function resolveActionToken(ServerRequestInterface $request)
    {
        $requestUri = $request->getUri();
        if (!isset($this->routes[$request->getMethod()]) || null === $requestUri) {
            $this->logger->error(
                sprintf(
                    'No routes found for request method "%s". Returning default route "%s"',
                    $request->getMethod(),
                    $this->defaultActionToken
                )
            );

            return $request->withAttribute(self::ACTIONTOKEN_ATTR, $this->defaultActionToken);
        }

        // strip query string if provided
        $requestPath = $requestUri->getPath();
        $queryStringPos = strpos($requestPath, '?');
        if (false !== $queryStringPos) {
            $requestPath = substr($requestPath, 0, $queryStringPos);
        }

        $this->logger->debug(sprintf('Analysing request path "%s"', $requestPath));
        foreach ($this->routes[$request->getMethod()] as $route) {
            $this->logger->debug(sprintf('Trying to match requested path to route "%s"', $route['path']));

            $urlVars = [];
            if (preg_match_all($route['pathRegEx'], $requestPath, $urlVars)) {
                // remove all elements which should not be set in the request,
                // e.g. the matching url string as well as all numeric items
                unset($urlVars[0]);
                foreach ($urlVars as $name => $value) {
                    if (!is_string($name)) {
                        unset($urlVars[$name]);
                    } else {
                        $urlVars[$name] = $value[0];
                    }
                }
                // setting route params as query params
                $request = $request->withQueryParams($urlVars);

                $this->logger->debug(
                    sprintf(
                        'Matching route found. Setting actionToken to "%s"',
                        $route['actionToken']
                    )
                );
                return $request->withAttribute(self::ACTIONTOKEN_ATTR, $route['actionToken']);
            }
        }

        $this->logger->debug(
            sprintf(
                'No matching route found. Setting default actionToken "%s"',
                $this->defaultActionToken
            )
        );

        return $request->withAttribute(self::ACTIONTOKEN_ATTR, $this->defaultActionToken);
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     */
    public function createLink($actionToken, array $params = [])
    {
        if (empty($actionToken)) {
            throw new \InvalidArgumentException('Please provide an actionToken, otherwise a link cannot be created!');
        }

        // try to find path for given $actionToken
        $path = '';
        foreach ($this->routes as $routeCollection) {
            foreach ($routeCollection as $route) {
                if ($actionToken === $route['actionToken']) {
                    $path = $route['path'];
                    break 2;
                }
            }
        }

        // when no path for the given $actionToken can be found,
        // stop processing...
        if (empty($path)) {
            return null;
        }

        // try to replace all params in the path
        foreach ($params as $name => $value) {
            $path = str_replace('[:' . $name . ']', $value, $path);
        }

        // in case not all placeholders could be resolved, stop processing...
        if (false !== strpos($path, '[:')) {
            return null;
        }

        return $path;
    }
}
