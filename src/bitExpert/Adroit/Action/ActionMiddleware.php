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
use bitExpert\Adroit\Middleware;

/**
 * An action middleware fetches the routing attribute determines which action to use for the defined target
 * executes it and sets the result to the defined domain payload attribute
 *
 * @package bitExpert\Adroit\Action
 */
interface ActionMiddleware extends Middleware
{
    /**
     * Returns the attribute the domain payload will be stored in after having executed the action
     * @return String
     */
    public function getDomainPayloadAttribute();
}
