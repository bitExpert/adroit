<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Router;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Router interface
 *
 * @api
 */
interface Router
{
    /**
     * The name of the attribute used to store the actionToken in the Request.
     *
     * @var string
     */
    const ACTIONTOKEN_ATTR = 'bF.actionToken';

    /**
     * Sets the default action token. It is used to retrieve an action, if
     * no action token can be found in the request.
     *
     * @param string $defaultActionToken
     */
    public function setDefaultActionToken($defaultActionToken);

    /**
     * Extracts the current action token from the request. Will return null
     * in case no action token could be found and no default action token
     * was provided.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function resolveActionToken(ServerRequestInterface $request);

    /**
     * Creates a link to an action identified by the given action token. In
     * case building the link is not possible null is returned. Will throw an
     * \InvalidArgumentException when no $actionToken was passed.
     *
     * @param string $actionToken
     * @param array $params
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function createLink($actionToken, array $params = []);
}
