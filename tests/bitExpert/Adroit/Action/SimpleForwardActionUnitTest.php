<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Action;

use bitExpert\Adroit\Domain\DomainPayload;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * Unit test for {@link \bitExpert\Adroit\Action\SimpleForwardAction}.
 *
 * @covers \bitExpert\Adroit\Action\SimpleForwardAction
 * @covers \bitExpert\Adroit\Action\AbstractAction
 */
class SimpleForwardActionUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function invokeAlwaysReturnsDomainPayload()
    {
        $action = new SimpleForwardAction(

        );
        $domainPayload = $action->__invoke(new ServerRequest(), new Response());

        $this->assertInstanceOf(DomainPayload::class, $domainPayload);
    }

    /**
     * @test
     */
    public function domainPayloadContainsTheResponderPassedToTheAction()
    {
        $action = new SimpleForwardAction();
        $action->setResponder('myResponder');
        $domainPayload = $action->__invoke(new ServerRequest(), new Response());

        $this->assertSame('myResponder', $domainPayload->getType());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setResponderThrowsExceptionIfResponderIsNotAString()
    {
        $action = new SimpleForwardAction();
        $action->setResponder(new \stdClass());
    }
}
