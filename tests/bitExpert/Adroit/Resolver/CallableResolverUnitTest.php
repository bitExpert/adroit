<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types = 1);

namespace bitExpert\Adroit\Resolver;

/**
 * Unit test for {@link \bitExpert\Adroit\Resolver\CallableResolver}.
 */
class CallableResolverUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CallableResolver
     */
    protected $resolver;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resolver = new CallableResolver();
    }

    /**
     * @test
     */
    public function returnsArgumentIfCallable()
    {
        $identifier = function () {

        };
        
        $result = $this->resolver->resolve($identifier);

        $this->assertSame($identifier, $result);
    }

    /**
     * @test
     */
    public function returnsNullIfArgumentIsNotCallable()
    {
        $identifier = 'notCallable';
        $result = $this->resolver->resolve($identifier);

        $this->assertNull($result);
    }
}
