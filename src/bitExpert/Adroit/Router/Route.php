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

use bitExpert\Adroit\Router\Matcher\Matcher;

/**
 * Route Domain object
 */
class Route
{
    /**
     * @var string[]
     */
    protected $methods;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var string
     */
    protected $actionToken;
    /**
     * @var Matcher[]
     */
    protected $matchers;

    /**
     * Creates a new {@link \bitExpert\Adroit\Router\Route}.
     *
     * @param array|string $methods The HTTP methods the route is active (e.g. GET, POST, PUT, ...)
     * @param string|null $path
     * @param string|null $actionToken
     */
    public function __construct($methods = [], $path = null, $actionToken = null, $matchers = [])
    {
        $this->path = $path;
        $this->actionToken = $actionToken;
        $this->methods = is_array($methods) ? $methods : [$methods];
        $this->methods = array_map('self::normalizeMethod', $this->methods);
        $this->matchers = $matchers;
    }

    /**
     * Creates a new route instance
     *
     * @param array $methods
     * @param string|null $path
     * @param string|null $actionToken
     * @param array $matchers
     * @return static
     */
    public static function create($methods = [], $path = null, $actionToken = null, $matchers = [])
    {
        return new static($methods, $path, $actionToken, $matchers);
    }

    /**
     * Creates a new GET accepting route
     *
     * @param string|null $path
     * @param string|null $actionToken
     * @param array $matchers
     * @return Route
     */
    public static function get($path = null, $actionToken = null, $matchers = [])
    {
        return self::create('GET', $path, $actionToken, $matchers);
    }

    /**
     * Creates a new POST accepting route
     *
     * @param string|null $path
     * @param string|null $actionToken
     * @param array $matchers
     * @return Route
     */
    public static function post($path = null, $actionToken = null, $matchers = [])
    {
        return self::create('POST', $path, $actionToken, $matchers);
    }

    /**
     * Creates a new PUT accepting route
     *
     * @param string|null $path
     * @param string|null $actionToken
     * @param array $matchers
     * @return Route
     */
    public static function put($path = null, $actionToken = null, $matchers = [])
    {
        return self::create('PUT', $path, $actionToken, $matchers);
    }

    /**
     * Creates a new DELETE accepting route
     *
     * @param string|null $path
     * @param string|null $actionToken
     * @param array $matchers
     * @return Route
     */
    public static function delete($path = null, $actionToken = null, $matchers = [])
    {
        return self::create('DELETE', $path, $actionToken, $matchers);
    }

    /**
     * Creates a new OPTIONS accepting route
     *
     * @param string|null $path
     * @param string|null $actionToken
     * @param array $matchers
     * @return Route
     */
    public static function options($path = null, $actionToken = null, $matchers = [])
    {
        return self::create('OPTIONS', $path, $actionToken, $matchers);
    }

    /**
     * Creates a new PATCH accepting route
     *
     * @param string|null $path
     * @param string|null $actionToken
     * @param array $matchers
     * @return Route
     */
    public static function patch($path = null, $actionToken = null, $matchers = [])
    {
        return self::create('PATCH', $path, $actionToken, $matchers);
    }

    /**
     * Sets the method(s) which the route should accept
     *
     * @param array|string $methods The HTTP method(s) the route should handle
     * @return Route
     */
    public function accepting($methods)
    {
        $methods = is_array($methods) ? $methods : [$methods];

        $instance = clone($this);
        $normalizedMethods = array_map('self::normalizeMethod', $methods);
        $instance->methods = array_unique(array_merge($instance->methods, $normalizedMethods));

        return $instance;
    }

    /**
     * Removes given method(s) from the set of methods the route should handle
     *
     * @param array|string $methods The HTTP method(s) the route should no longer handle
     * @return Route
     */
    public function refusing($methods)
    {
        $methods = is_array($methods) ? $methods : [$methods];

        $instance = clone($this);
        $normalizedMethods = array_map('self::normalizeMethod', $methods);

        $instance->methods = array_diff($instance->methods, $normalizedMethods);

        return $instance;
    }

    /**
     * Sets matcher(s) which the given param should match for the route to be active
     *
     * @param string $param The param name to set the matcher(s) for
     * @param array|Matcher $matchers The matcher or array of matchers for the param
     * @return Route
     */
    public function ifMatches($param, $matchers)
    {
        $instance = clone($this);

        if (!isset($instance->matchers[$param])) {
            $instance->matchers[$param] = [];
        }

        $matchers = is_array($matchers) ? $matchers : [$matchers];

        foreach ($matchers as $matcher) {
            if ($matcher instanceof Matcher) {
                continue;
            }

            throw new \InvalidArgumentException(sprintf(
                'Given matcher does not implement %s',
                Matcher::class
            ));
        }

        $instance->matchers[$param] = array_merge($instance->matchers[$param], $matchers);

        return $instance;
    }

    /**
     * Returns a route having removed all formerly set matchers for the param with given name
     *
     * @param string $param The name of the param all matchers should be removed for
     * @return Route
     */
    public function whateverMatches($param)
    {
        if (!isset($this->methods[$param])) {
            return $this;
        }

        $instance = clone($this);

        unset($instance->methods[$param]);

        return $instance;
    }

    /**
     * Returns the route with a new path configuration
     *
     * @param string $path The new path
     * @return Route
     */
    public function from($path)
    {
        $instance = clone($this);
        $instance->path = $path;
        return $instance;
    }

    /**
     * Returns the route with a new action token
     *
     * @param string $actionToken The new action token
     * @return Route
     */
    public function to($actionToken)
    {
        $instance = clone($this);
        $instance->actionToken = $actionToken;
        return $instance;
    }

    /**
     * Returns the path of the route.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the actionToken which is associated with the route.
     *
     * @return string
     */
    public function getActionToken()
    {
        return $this->actionToken;
    }

    /**
     * Returns the methods accepted by this route
     *
     * @return array|\string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Returns defined matchers for params of the route
     *
     * @return array
     */
    public function getMatchers()
    {
        return $this->matchers;
    }

    /**
     * Helper function to normalize HTTP request methods (trimmed to uppercase)
     *
     * @return Callable
     */
    protected function normalizeMethod($method)
    {
        return strtoupper(trim($method));
    }
}
