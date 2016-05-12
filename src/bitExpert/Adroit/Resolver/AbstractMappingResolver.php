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

/**
 * Class AbstractMappingResolver
 * A Resolver which maps the original identifier to another one before resolving
 *
 * @package bitExpert\Adroit\Resolver
 */
abstract class AbstractMappingResolver implements Resolver
{
    /**
     * @var array
     */
    protected $mappings;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * MappingResolver constructor.
     * @param array $mappings
     */
    public function __construct(array $mappings)
    {
        $this->logger = LoggerFactory::getLogger(__CLASS__);
        $this->mappings = $mappings;
    }

    /**
     * @inheritdoc
     */
    public function resolve($identifier)
    {
        $mappedIdentifier = $this->map($identifier);

        if (!$mappedIdentifier) {
            $this->logger->warning(sprintf(
                'Could not find mapping for identifier "%s"',
                $identifier
            ));
            return null;
        }

        return $this->resolveMapped($mappedIdentifier);
    }

    /**
     * Maps the original identifier to the mapped one
     *
     * @param $identifier
     * @return mixed|null
     */
    protected function map($identifier)
    {
        if (!isset($this->mappings[$identifier])) {
            return null;
        }

        return $this->mappings[$identifier];
    }

    /**
     * Resolves the mapped identifier
     *
     * @param $mappedIdentifier
     * @return mixed
     */
    abstract protected function resolveMapped($mappedIdentifier);
}
