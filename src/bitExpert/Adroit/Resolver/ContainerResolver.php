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

namespace bitExpert\Adroit\Resolver;

use bitExpert\Slf4PsrLog\LoggerFactory;
use Interop\Container\ContainerInterface;

/**
 * Implementation of an {@link \bitExpert\Adroit\Resolver\Resolver} which will
 * pull the results from a "container-aware" service.
 */
class ContainerResolver implements Resolver
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
     * @throws \RuntimeException
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = LoggerFactory::getLogger(__CLASS__);
    }

    /**
     * {@inheritDoc}
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Interop\Container\Exception\NotFoundException
     */
    public function resolve($identifier)
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

        return $this->container->get($identifier);
    }
}
