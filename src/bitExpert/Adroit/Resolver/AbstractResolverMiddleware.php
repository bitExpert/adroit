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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractResolverMiddleware
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var Resolver[]
     */
    protected $resolvers;

    /**
     * @param Resolver[] | Resolver $resolvers
     * @throws \InvalidArgumentException
     */
    public function __construct(array $resolvers)
    {
        $this->logger = LoggerFactory::getLogger(__CLASS__);
        $this->resolvers = $resolvers;
    }

    /**
     * Internal resolver setter which validates the resolvers
     *
     * @param \bitExpert\Adroit\Resolver\Resolver[] $resolvers
     * @throws \InvalidArgumentException
     */
    private function validateResolvers(array $resolvers)
    {
        foreach ($resolvers as $index => $resolver) {
            if (!$this->isValidResolver($resolver)) {
                throw new \InvalidArgumentException(sprintf(
                    'Resolver at index %s of type "%s" is not valid resolver type',
                    $index,
                    get_class($resolver)
                ));
            }
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     */
    abstract public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    ) : ResponseInterface;

    /**
     * Returns whether given resolver is valid or not
     *
     * @param Resolver $resolver
     * @return bool
     */
    abstract protected function isValidResolver(Resolver $resolver) : bool;

    /**
     * Returns the identifier used for the resolver process
     *
     * @param ServerRequestInterface $request
     * @return mixed
     */
    abstract protected function getIdentifier(ServerRequestInterface $request);

    /**
     * Returns whether the resolved result is valid or not
     *
     * @param mixed $result
     * @return bool
     */
    protected function isValidResult($result) : bool
    {
        return is_callable($result);
    }

    /**
     * @param ServerRequestInterface $request
     * @throws ResolveException
     * @throws \InvalidArgumentException
     * @return mixed
     */
    protected function resolve(ServerRequestInterface $request)
    {
        $identifier = $this->getIdentifier($request);
        $resolvers = $this->getApplicableResolvers($request);

        $identifierName = is_object($identifier) ? get_class($identifier) : (string) $identifier;

        $this->validateResolvers($resolvers);

        foreach ($resolvers as $index => $resolver) {
            $resolved = $resolver->resolve($identifier);
            $resolvedName = is_object($resolved) ? get_class($resolved) : (string) $resolved;

            if (!$this->isValidResult($resolved)) {
                // step out of the loop when an action could be found
                // by the resolver. First resolver wins!
                $this->logger->debug(sprintf(
                    '"%s" resolved via resolver "%s" at index %s using identifier "%s" is not a valid result. Skipped.',
                    $resolvedName,
                    get_class($resolver),
                    $index,
                    $identifierName
                ));

                continue;
            }

            // step out of the loop when an action could be found
            // by the resolver. First resolver wins!
            $this->logger->debug(sprintf(
                'Successfully resolved "%s" via resolver "%s" at index %s using identifier "%s"',
                $resolvedName,
                get_class($resolver),
                $index,
                $identifierName
            ));

            return $resolved;
        }

        $message = sprintf(
            'Identifier "%s" could not be resolved',
            $identifierName
        );

        // step out of the loop when an action could be found
        // by the resolver. First resolver wins!
        $this->logger->error($message);

        throw new ResolveException($message);
    }

    /**
     * @param ServerRequestInterface $request
     * @return Resolver[]
     */
    protected function getApplicableResolvers(ServerRequestInterface $request) : array
    {
        return $this->resolvers;
    }
}
