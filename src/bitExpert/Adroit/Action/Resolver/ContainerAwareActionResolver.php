<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Action\Resolver;

use bitExpert\Adroit\Action\Action;
use bitExpert\Adroit\Router\Router;
use bitExpert\Slf4PsrLog\LoggerFactory;
use Exception;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Implementation of an {@link \bitExpert\Adroit\Action\Resolver\ActionResolver} which will
 * pull the actions from a "container-aware" service.
 */
class ContainerAwareActionResolver implements ActionResolver
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
    public function resolve(ServerRequestInterface $request)
    {
        $actionToken = $request->getAttribute(Router::ACTIONTOKEN_ATTR);
        if (!$this->container->has($actionToken)) {
            $this->logger->error(
                sprintf(
                    'Could not find action with id "%s"',
                    $actionToken
                )
            );

            return null;
        }


        try {
            $action = $this->container->get($actionToken);
            if ($action instanceof Action) {
                return $action;
            } else {
                $this->logger->debug(
                    sprintf(
                        'Got "%s" but expected "%s". Ignoring.',
                        is_object($action) ? get_class($action) : $action,
                        Action::class
                    )
                );
            }
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'An error occurred while trying to instantiate action with id "%s": %s',
                    $actionToken,
                    $e->getMessage()
                )
            );
        }

        return null;
    }
}
