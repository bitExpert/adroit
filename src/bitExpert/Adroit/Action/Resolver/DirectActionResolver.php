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
use Psr\Http\Message\ServerRequestInterface;

/**
 * Implementation of an {@link \bitExpert\Adroit\Action\Resolver\ActionResolver} which will
 * return the action identifier if it is callable itself e.g. when a callable class is used as identifier
 * or a lambda function / closure
 */
class DirectActionResolver implements ActionResolver
{
    /**
     * @inheritdoc
     */
    public function resolve (ServerRequestInterface $request, $identifier)
    {
        if (is_callable($identifier)) {
            return $identifier;
        }

        return null;
    }
}
