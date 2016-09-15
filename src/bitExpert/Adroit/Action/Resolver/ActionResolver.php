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

use bitExpert\Adroit\Resolver\Resolver;

/**
 * An action resolver will provide a {@link \bitExpert(Adroit\Action\Action} instance
 * for the given $actionToken.
 *
 * @api
 */
interface ActionResolver extends Resolver
{
}
