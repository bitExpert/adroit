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
 * A resolver will resolve something by using the request and given identifier
 *
 * @api
 */
interface Resolver
{
    /**
     * Creates and returns an action object using the given $actionToken.
     * If no matching {@link \bitExpert\Adroit\Action\Action} instance could be found, null will be
     * returned.
     *
     * @param mixed $identifier
     * @return mixed
     */
    public function resolve($identifier);
}
