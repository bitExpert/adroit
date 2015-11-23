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
 * Matcher which uses regular expression
 */
class RegExMatcher implements Matcher
{
    /**
     * @var string
     */
    protected $regex;

    /**
     * Creates a new {@link \bitExpert\Adroit\Router\Matcher\RegexMatcher}.
     *
     * @param string $regex
     */
    public function __construct($regex)
    {
        $this->regex = $regex;
    }

    /**
     * @inheritdoc
     */
    public function match($value)
    {
        $value = str_replace('#', '\#', $value);
        return (preg_match(sprintf('#^%s$#', $this->regex), $value) > 0);
    }
}
