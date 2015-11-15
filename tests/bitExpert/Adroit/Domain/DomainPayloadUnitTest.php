<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit;

use bitExpert\Adroit\Domain\DomainPayload;

/**
 * Unit test for {@link \bitExpert\Adroit\Domain\DomainPayload}.
 *
 * @covers \bitExpert\Adroit\Domain\DomainPayload
 */
class DomainPayloadUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function willReturnTypeGivenWhenInstanceWasCreated()
    {
        $domainPayload = new DomainPayload('test');

        $this->assertEquals('test', $domainPayload->getType());
    }

    /**
     * @test
     */
    public function willReturnModelGivenWhenInstanceWasCreated()
    {
        $model = ['a' => '1', 'b' => 2];
        $domainPayload = new DomainPayload('test', $model);

        $this->assertEquals($model, $domainPayload->getValues());
    }

    /**
     * @test
     */
    public function queryingModelAttributeWillReturnSetValue()
    {
        $model = ['a' => '1', 'b' => 2];
        $domainPayload = new DomainPayload('test', $model);

        $this->assertEquals($model['b'], $domainPayload->getValue('b'));
    }

    /**
     * @test
     */
    public function queryingNonExistentModelAttributeWillReturnNull()
    {
        $domainPayload = new DomainPayload('test', ['a' => '1', 'b' => 2]);

        $this->assertNull($domainPayload->getValue('c'));
    }

    /**
     * @test
     */
    public function modifiedSingleAttributeWillBeReturnedCorrectly()
    {
        $domainPayload = new DomainPayload('test', ['a' => '1', 'b' => 2]);
        $domainPayload = $domainPayload->withValue('b', 3);

        $this->assertEquals(3, $domainPayload->getValue('b'));
    }

    /**
     * @test
     */
    public function modifiedAttributeCollectionWillBeReturnedCorrectly()
    {
        $domainPayload = new DomainPayload('test', ['a' => '1', 'b' => 2]);
        $domainPayload = $domainPayload->withValues(['b' => 4, 'c' => 'd']);

        $this->assertEquals(4, $domainPayload->getValue('b'));
        $this->assertEquals('d', $domainPayload->getValue('c'));
    }
}
