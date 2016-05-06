<?php
/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Domain;

/**
 * The domain payload object represents the domain's payload and its type
 *
 * @api
 */
interface Payload
{
    /**
     * Returns the type of the payload
     *
     * @return string
     */
    public function getType();
}
