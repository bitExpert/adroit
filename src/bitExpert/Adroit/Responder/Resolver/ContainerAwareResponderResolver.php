<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Responder\Resolver;

use bitExpert\Adroit\Domain\DomainPayloadInterface;
use bitExpert\Adroit\Responder\Responder;
use bitExpert\Slf4PsrLog\LoggerFactory;
use Exception;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Implementation of an {@link \bitExpert\Adroit\Responder\Resolver\ResponderResolver} which will
 * pull the actions from a "container-aware" service.
 */
class ContainerAwareResponderResolver implements ResponderResolver
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
     * Creates a new {@link \bitExpert\Adroit\Action\Resolver\ContainerAwareResponderResolver}.
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
    public function resolve(ServerRequestInterface $request, DomainPayloadInterface $domainPayload)
    {
        if (!$this->container->has($domainPayload->getType())) {
            $this->logger->error(
                sprintf(
                    'Could not find responder with id "%s"',
                    $domainPayload->getType()
                )
            );

            return null;
        }

        try {
            $responder = $this->container->get($domainPayload->getType());
            if ($responder instanceof Responder) {
                return $responder;
            } else {
                $this->logger->debug(
                    sprintf(
                        'Got "%s" but expected type "%s". Ignoring.',
                        is_object($responder) ? get_class($responder) : $responder,
                        Responder::class
                    )
                );
            }
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'An error occurred while trying to instantiate "%s": %s',
                    $domainPayload->getType(),
                    $e->getMessage()
                )
            );
        }

        return null;
    }
}
