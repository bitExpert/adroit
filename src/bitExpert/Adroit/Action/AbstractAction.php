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

use bitExpert\Adroit\Domain\DomainPayload;
use bitExpert\Slf4PsrLog\LoggerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Base class for all actions providing a default implementation of the prepareAndExecute()
 * method. Child classes need only to implement the execute() method.
 *
 * @api
 */
abstract class AbstractAction implements Action
{
    /**
     * @var \Psr\Log\LoggerInterface the logger instance.
     */
    private $logger = null;

    /**
     * Returns a {@link \Psr\Log\LoggerInterface} logger implementation.
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            $this->logger = LoggerFactory::getLogger(__CLASS__);
        }

        return $this->logger;
    }

    /**
     * {@inheritDoc}
     * @throws RuntimeException
     */
    public function prepareAndExecute(ServerRequestInterface $request, ResponseInterface $response)
    {
        $responseOrPayload = $this->execute($request, $response);

        // when the action returns a Response object we can simply return it
        if ($responseOrPayload instanceof ResponseInterface) {
            return $responseOrPayload;
        }

        // when the action returns a ModelAndResponder object we can simply return it
        if ($responseOrPayload instanceof DomainPayload) {
            return $responseOrPayload;
        }

        throw new RuntimeException(sprintf('%s::execute() returned no matching type!', __CLASS__));
    }

    /**
     * Creates a domain payload instance of the given type with given data
     *
     * @param $type
     * @param array $data
     * @return DomainPayload
     */
    protected function createPayload($type, array $data = [])
    {
        return new DomainPayload($type, $data);
    }

    /**
     * Executes an action.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return DomainPayload|ResponseInterface
     */
    abstract protected function execute(ServerRequestInterface $request, ResponseInterface $response);
}
