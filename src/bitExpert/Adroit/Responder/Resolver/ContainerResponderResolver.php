<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Responder\Resolver;

use bitExpert\Adroit\Resolver\ContainerResolver;

/**
 * Implementation of an {@link \bitExpert\Adroit\Responder\Resolver\ResponderResolver} which will
 * pull the actions from a "container-aware" service.
 */
class ContainerResponderResolver extends ContainerResolver implements ResponderResolver
{

}
