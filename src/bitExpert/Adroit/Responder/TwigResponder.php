<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Responder;

use bitExpert\Adroit\Domain\DomainPayloadInterface;
use Exception;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Responder to convert a given Twig template into an response object.
 *
 * @api
 */
class TwigResponder implements Responder
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;
    /**
     * @var string
     */
    protected $template;
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Creates a new {\bitExpert\Adroit\Responder\TwigResponder}.
     *
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Sets the template to render.
     *
     * @param string $template
     * @throws \InvalidArgumentException
     */
    public function setTemplate($template)
    {
        if (!is_string($template)) {
            throw new \InvalidArgumentException('Given template name needs to be of type string!');
        }

        $this->template = $template;
    }

    /**
     * Set additional HTTP headers.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * {@inheritDoc}
     * @throws RuntimeException
     */
    public function buildResponse(DomainPayloadInterface $domainPayload, ResponseInterface $response)
    {
        if (empty($this->template)) {
            throw new RuntimeException('No template set to render!');
        }

        try {
            $response->getBody()->rewind();
            $response->getBody()->write($this->twig->render($this->template, $domainPayload->getValues()));

            $headers = array_merge($this->headers, ['Content-Type' => 'text/html']);
            foreach ($headers as $header => $value) {
                $response = $response->withHeader($header, $value);
            }

            return $response->withStatus(200);
        } catch (Exception $e) {
            throw new RuntimeException('Response object could not be instantiated! ' . $e->getMessage());
        }
    }
}
