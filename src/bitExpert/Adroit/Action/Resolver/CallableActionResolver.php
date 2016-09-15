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

namespace bitExpert\Adroit\Action\Resolver;

use bitExpert\Adroit\Resolver\CallableResolver;

/**
 * Implementation of an {@link \bitExpert\Adroit\Action\Resolver\ActionResolver} which will
 * return the action identifier if it is callable itself e.g. when a callable class is used as identifier
 * or a lambda function / closure
 */
class CallableActionResolver extends CallableResolver implements ActionResolver
{

}
