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
 * Abstract middleware which resolves using an identifier and checks the result
 * for being callable
 *
 * @package bitExpert\Adroit\Resolver
 */
abstract class CallableResolverMiddleware extends AbstractResolverMiddleware
{
    /**
     * @inheritdoc
     */
    protected function isValidResult($result)
    {
        return is_callable($result);
    }
}
