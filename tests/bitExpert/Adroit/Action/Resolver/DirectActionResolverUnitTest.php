<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Action\Resolver;

use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * Unit test for {@link \bitExpert\Adroit\Action\Resolver\DirectActionResolver}.
 *
 * @covers \bitExpert\Adroit\Action\Resolver\DirectActionResolver
 */
class DirectActionResolverUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DirectActionResolver
     */
    protected $resolver;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resolver = new DirectActionResolver();
    }

    /**
     * @test
     */
    public function returnsArgumentIfCallable()
    {
        $request = new ServerRequest();
        $identifier = function () {};
        $result = $this->resolver->resolve($request, $identifier);

        $this->assertSame($identifier, $result);
    }

    /**
     * @test
     */
    public function returnsNullIfArgumentIsNotCallable()
    {
        $request = new ServerRequest();
        $identifier = 'notCallable';
        $result = $this->resolver->resolve($request, $identifier);

        $this->assertNull($result);
    }
}
