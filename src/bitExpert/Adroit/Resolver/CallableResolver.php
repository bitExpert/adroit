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

/**
 * Implementation of an {@link \bitExpert\Adroit\Resolver\Resolver} which will
 * return the identifier if it is callable itself e.g. when a callable class is used as identifier
 * or a lambda function / closure
 */
class CallableResolver implements Resolver
{
    /**
     * @inheritdoc
     */
    public function resolve($identifier)
    {
        if (is_callable($identifier)) {
            return $identifier;
        }

        return null;
    }
}