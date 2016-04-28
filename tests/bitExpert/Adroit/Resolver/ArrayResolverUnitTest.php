<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Resolver;

use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * Unit test for {@link \bitExpert\Adroit\Resolver\ContainerAwareResolver}.
 *
 * @covers \bitExpert\Adroit\Resolver\ArrayResolver
 */
class ArrayResolverUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $mappings;
    /**
     * @var ContainerAwareResolver
     */
    protected $resolver;

    /**
     * @inheritdoc
     */
    protected function setUp ()
    {
        $this->mappings = [
            'existingId' => 'existingValue'
        ];

        $this->resolver = new ArrayResolver($this->mappings);
    }

    /**
     * @test
     */
    public function returnsNullIfValueWithIdCannotBeFoundInMappings()
    {
        $id = 'TestID';

        $obj = $this->resolver->resolve(new ServerRequest(), $id);
        $this->assertNull($obj);
    }

    /**
     * @test
     */
    public function returnsValueIfPresentInMappings()
    {
        $this->assertGreaterThan(0, count($this->mappings));

        foreach ($this->mappings as $id => $value) {
            $resolved = $this->resolver->resolve(new ServerRequest(), $id);
            $this->assertEquals($value, $resolved);
        }
    }

    /**
     * @test
     */
    public function returnsNullIfIdIsNull()
    {
        $id = null;
        $resolved = $this->resolver->resolve(new ServerRequest(), $id);
        $this->assertNull($resolved);
    }
}
