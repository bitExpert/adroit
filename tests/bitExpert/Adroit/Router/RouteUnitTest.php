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
 * Unit test for {@link \bitExpert\Adroit\Router\Route}.
 *
 * @covers \bitExpert\Adroit\Router\Route
 */
class RouteUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function methodGetsReturnedInCapitalLetters()
    {
        $route = new Route('get', '/', 'test');

        $this->assertSame(['GET'], $route->getMethods());
    }

    /**
     * @test
     */
    public function pathGetsReturnedAsIs()
    {
        $route = new Route('get', '/info', 'test');

        $this->assertSame('/info', $route->getPath());
    }

    /**
     * @test
     */
    public function actionTokenGetsReturnedAsIs()
    {
        $route = new Route('get', '/info', 'test');

        $this->assertSame('test', $route->getActionToken());
    }
}
