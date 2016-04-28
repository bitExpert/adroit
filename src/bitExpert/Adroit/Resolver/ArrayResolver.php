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
use Psr\Http\Message\ServerRequestInterface;

/**
 * Implementation of an {@link \bitExpert\Adroit\Resolver\Resolver} which will
 * pull the results from a "container-aware" service.
 */
class ArrayResolver implements Resolver
{
    /**
     * @var array
     */
    protected $mappings;
    /**
     * @var \Psr\Log\LoggerInterface the logger instance.
     */
    protected $logger;

    /**
     * Creates a new {@link \bitExpert\Adroit\Resolver\ArrayResolver}.
     *
     * @param array $mappings
     * @throws \RuntimeException
     */
    public function __construct(array $mappings)
    {
        $this->mappings = $mappings;
        $this->logger = LoggerFactory::getLogger(__CLASS__);
    }

    /**
     * @inheritdoc
     */
    public function resolve(ServerRequestInterface $request, $identifier)
    {
        if (!array_key_exists($identifier, $this->mappings)) {
            $this->logger->error(
                sprintf(
                    'Could not find object defined by id "%s"',
                    $identifier
                )
            );

            return null;
        }

        return $this->mappings[$identifier];
    }
}
