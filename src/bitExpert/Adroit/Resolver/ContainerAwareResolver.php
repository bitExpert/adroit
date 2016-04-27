<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Resolver;

use bitExpert\Slf4PsrLog\LoggerFactory;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Abstract implementation of an {@link \bitExpert\Adroit\Resolver\Resolver} which will
 * pull the results from a "container-aware" service.
 */
abstract class ContainerAwareResolver implements Resolver
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var \Psr\Log\LoggerInterface the logger instance.
     */
    protected $logger;

    /**
     * Creates a new {@link \bitExpert\Adroit\Action\Resolver\ContainerAwareActionResolver}.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = LoggerFactory::getLogger(__CLASS__);
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(ServerRequestInterface $request, $identifier)
    {
        if (!$this->container->has($identifier)) {
            $this->logger->error(
                sprintf(
                    'Could not find object defined by id "%s"',
                    $identifier
                )
            );

            return null;
        }

        $resolved = $this->container->get($identifier);
        return $this->isValidResult($resolved) ? $resolved : null;
    }

    /**
     * Returns whether resolved result is valid or not
     *
     * @param $resolved
     * @return bool
     */
    protected function isValidResult($resolved)
    {
        return (null !== $resolved);
    }
}
