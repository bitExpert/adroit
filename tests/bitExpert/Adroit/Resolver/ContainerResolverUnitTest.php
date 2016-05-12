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
use Interop\Container\ContainerInterface;

/**
 * Unit test for {@link \bitExpert\Adroit\Resolver\ContainerAwareResolver}.
 */
class ContainerResolverUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Interop\Container\ContainerInterface
     */
    protected $container;
    /**
     * @var ContainerResolver
     */
    protected $resolver;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->container = $this->getMock(ContainerInterface::class);
        $this->resolver = new ContainerResolver($this->container);
    }

    /**
     * @test
     */
    public function returnsNullIfValueWithIdCannotBeFoundInContainer()
    {
        $id = 'TestID';
        $this->container->expects($this->once())
            ->method('has')
            ->with($id)
            ->will($this->returnValue(false));

        $obj = $this->resolver->resolve($id);
        $this->assertNull($obj);
    }

    /**
     * @test
     */
    public function returnsValueIfValuePresentInContainer()
    {
        $id = 'TestID';
        $obj = new \stdClass();

        $this->container->expects($this->once())
            ->method('has')
            ->with($id)
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('get')
            ->with($id)
            ->will($this->returnValue($obj));

        $resolved = $this->resolver->resolve($id);
        $this->assertSame($obj, $resolved);
    }

    /**
     * @test
     */
    public function returnsNullIfIdIsNull()
    {
        $resolved = $this->resolver->resolve(null);
        $this->assertNull($resolved);
    }

    /**
     * @test
     */
    public function correctlyMapsIdentifiers()
    {
        $identifier = 'test';
        $mappedIdentifier = 'mappedTest';
        $obj = new \stdClass();

        $resolver = new ContainerResolver($this->container, [
            $identifier => $mappedIdentifier
        ]);

        $this->container->expects($this->once())
            ->method('has')
            ->with($mappedIdentifier)
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('get')
            ->with($mappedIdentifier)
            ->will($this->returnValue($obj));

        $resolved = $resolver->resolve($identifier);
        $this->assertSame($obj, $resolved);
    }

    /**
     * @test
     */
    public function directlyReturnsNullIfMappingIsNotFound()
    {
        $identifier = 'test1';

        $resolver = new ContainerResolver($this->container, [
            'test' => 'mappedTest'
        ]);

        $this->container->expects($this->never())
            ->method('has');

        $this->container->expects($this->never())
            ->method('get');

        $resolved = $resolver->resolve($identifier);
        $this->assertNull($resolved);
    }

    /**
     * @test
     */
    public function directlyUsesIdentifierIfMappingsNotProvided()
    {
        $identifier = 'test';
        $obj = new \stdClass();

        $resolver = new ContainerResolver($this->container);

        $this->container->expects($this->once())
            ->method('has')
            ->with($identifier)
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('get')
            ->with($identifier)
            ->will($this->returnValue($obj));

        $resolved = $resolver->resolve($identifier);
        $this->assertSame($obj, $resolved);
    }
}
