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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * Unit test for {@link \bitExpert\Adroit\Action\AbstractAction}.
 *
 * @covers \bitExpert\Adroit\Action\AbstractAction
 */
class AbstractActionUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function responseGeneratedByExecuteWillBePassedToCaller()
    {
        $action = $this->getMock(AbstractAction::class, ['execute']);
        $action->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(new Response()));

        $response = $action->__invoke(new ServerRequest(), new Response());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @test
     */
    public function domainPayloadGeneratedByExecuteWillBePassedToCaller()
    {
        $action = $this->getMock(AbstractAction::class, ['execute']);
        $action->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(new DomainPayload('test')));

        $domainPayload = $action->__invoke($this->getMock(ServerRequestInterface::class), new Response());

        $this->assertInstanceOf(DomainPayload::class, $domainPayload);
        $this->assertSame('test', $domainPayload->getType());
    }


    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function otherReturnValuesGeneratedByExecuteWillThrowAnException()
    {
        $action = $this->getMock(AbstractAction::class, ['execute']);
        $action->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(null));

        $action->__invoke($this->getMock(ServerRequestInterface::class), new Response());
    }
}
