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
     * Creates a new {@link \bitExpert\Adroit\Router\Route}.
     *
     * @param array|string $methods The HTTP methods the route is active (e.g. GET, POST, PUT, ...)
     * @param string $path
     * @param string $actionToken
     */
    public function __construct($methods, $path, $actionToken)
    {
        if (!is_array($methods)) {
            $methods = [$methods];
        }

        $this->methods = array_map('strtoupper', $methods);
        $this->path = $path;
        $this->actionToken = $actionToken;
    }

    /**
     * Returns the HTTP methods the route is active for.
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
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
}
