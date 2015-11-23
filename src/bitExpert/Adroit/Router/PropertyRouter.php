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
 * Implementation of an {@link \bitExpert\Adroit\Router\Router} which listens for a
 * defined request variable holding the action token. Standard listener is 'action'.
 *
 * @api
 */
class PropertyRouter implements Router
{
    /**
     * @var string
     */
    protected $baseURL;
    /**
     * @var string
     */
    protected $listener;
    /**
     * @var string|null
     */
    protected $defaultActionToken;
    /**
     * @var bool
     */
    protected $specialCharEncoding;

    /**
     * Creates a new {@link \bitExpert\Adroit\Router\PropertyRouter}.
     *
     * @param string $baseURL
     */
    public function __construct($baseURL)
    {
        // completes the base url with a / if not set in configuration
        $this->baseURL = rtrim($baseURL, '/') . '/';
        $this->listener = 'action';
        $this->defaultActionToken = null;
        $this->specialCharEncoding = false;
    }

    /**
     * Defines whether htmlspecialchars-encoding should be used or not
     * to encode the url which is created with the createLink() method.
     *
     * @param string $listener
     */
    public function setListener($listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultActionToken($defaultActionToken)
    {
        $this->defaultActionToken = $defaultActionToken;
    }

    /**
     * Sets whether htmlspecialchars-encoding should be used or not
     *
     * @param bool $specialCharEncoding
     */
    public function setSpecialCharEncoding($specialCharEncoding)
    {
        $this->specialCharEncoding = (bool) $specialCharEncoding;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveActionToken(ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();
        $actionToken = isset($queryParams[$this->listener]) ? $queryParams[$this->listener] : null;
        if (null === $actionToken) {
            $actionToken = $this->defaultActionToken;
        }

        return $request->withAttribute(self::ACTIONTOKEN_ATTR, $actionToken);
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     */
    public function createLink($actionToken, array $params = [])
    {
        if (empty($actionToken)) {
            throw new \InvalidArgumentException('Please provide an Actiontoken, otherwise a link cannot be build!');
        }

        $action = $this->listener . '=' . $actionToken;
        $params = '&' . http_build_query($params, '', '&');
        $params = ($this->specialCharEncoding) ? htmlspecialchars($params) : $params;
        return $this->baseURL . '?' . $action . $params;
    }
}
