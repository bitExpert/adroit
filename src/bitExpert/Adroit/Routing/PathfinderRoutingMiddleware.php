<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Routing;

/**
 * Extension of the {@link \bitExpert\Pathfinder\Middleware\RoutingMiddleware} which implements the Adroit specific
 * {@link \bitExpert\Adroit\Routing\RoutingMiddleware}
 *
 * @package bitExpert\Adroit\Routing
 */
class PathfinderRoutingMiddleware extends \bitExpert\Pathfinder\Middleware\RoutingMiddleware implements RoutingMiddleware
{
    /**
     * @inheritdoc
     */
    public function getRouter()
    {
        return $this->router;
    }
}
