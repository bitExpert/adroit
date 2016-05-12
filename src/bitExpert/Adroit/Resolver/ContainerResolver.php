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

/**
 * Implementation of an {@link \bitExpert\Adroit\Resolver\Resolver} which will
 * pull the results from a "container-aware" service.
 */
class ContainerResolver extends AbstractMappingResolver
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
     * @param array $mappings
     * @throws \RuntimeException
     */
    public function __construct(ContainerInterface $container, array $mappings = [])
    {
        parent::__construct($mappings);
        
        $this->container = $container;
        $this->logger = LoggerFactory::getLogger(__CLASS__);
    }

    /**
     * @inheritdoc
     */
    protected function map($identifier)
    {
        if (!count($this->mappings)) {
            return $identifier;
        }

        return parent::map($identifier);
    }

    /**
     * {@inheritDoc}
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Interop\Container\Exception\NotFoundException
     */
    public function resolveMapped($identifier)
    {
        if (!is_string($identifier)) {
            return null;
        }

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
