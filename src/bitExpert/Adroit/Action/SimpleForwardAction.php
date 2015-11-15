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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SimpleForwardAction extends AbstractAction
{
    /**
     * @var string
     */
    protected $responder;

    /**
     * Sets the id of the responder to instantiate for building the response.
     *
     * @param string $responder
     * @throws \InvalidArgumentException
     */
    public function setResponder($responder)
    {
        if (!is_string($responder)) {
            throw new \InvalidArgumentException('You are only allowed to pass the identifier of the Responder!');
        }

        $this->responder = $responder;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->responder;
    }
}
