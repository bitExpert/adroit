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

use bitExpert\Adroit\Middleware;

/**
 * A routing middleware uses a router to determine a routing result which will be stored inside the
 * routingResultAttribute for further usage
 *
 * @package bitExpert\Adroit\Routing
 */
interface RoutingMiddleware extends Middleware
{
    /**
     * @return string
     */
    public function getRoutingResultAttribute();

    /**
     * @return \bitExpert\Pathfinder\Router
     */
    public function getRouter();
}
