<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Router\Matcher;

/**
 * Matcher which matches numeric values
 */
class NumericMatcher extends RegExMatcher
{
    /**
     * Creates a new {@link \bitExpert\Adroit\Router\Matcher\NumericMatcher}.
     */
    public function __construct()
    {
        parent::__construct('[1-9]+\d+');
    }
}