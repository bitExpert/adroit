<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Responder;
use bitExpert\Adroit\Routing\PathfinderRoutingMiddleware;
use bitExpert\Pathfinder\Router;

/**
 * Unit test for {@link \bitExpert\Adroit\Routing\PathfinderRoutingMiddleware}.
 *
 * @covers \bitExpert\Adroit\Routing\PathfinderRoutingMiddleware
 */
class PathfinderRoutingMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function returnsSetRouterCorrectly()
    {
        $router = $this->getMockForAbstractClass(Router::class);
        $middleware = new PathfinderRoutingMiddleware($router, 'routingResult');

        $this->assertSame($router, $middleware->getRouter());
    }
}
