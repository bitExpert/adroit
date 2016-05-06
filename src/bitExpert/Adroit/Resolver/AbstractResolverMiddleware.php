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
use Psr\Http\Message\ResponseInterface;

abstract class AbstractResolverMiddleware
{
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
     * @param $resolvers
     * @throws \InvalidArgumentException
     */
    private function validateResolvers($resolvers)
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
    abstract public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null);

    /**
     * Returns whether given resolver is valid or not
     *
     * @param Resolver $resolver
     * @return bool
     */
    abstract protected function isValidResolver(Resolver $resolver);

    /**
     * Returns the identifier used for the resolver process
     *
     * @param ServerRequestInterface $request
     * @return mixed
     */
    abstract protected function getIdentifier(ServerRequestInterface $request);

    /**
     * Returns whether the resolved is valid or not
     *
     * @param $result
     * @return bool
     */
    protected function isValidResult($result)
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
        $identifierName = $this->getRepresentation($identifier);
        $resolvers = $this->getApplicableResolvers($request);

        $this->validateResolvers($resolvers);

        foreach ($resolvers as $index => $resolver) {
            $resolved = $resolver->resolve($identifier);
            $resolvedName = $this->getRepresentation($resolved);

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
     * @return array|Resolver[]
     */
    protected function getApplicableResolvers(ServerRequestInterface $request)
    {
        return $this->resolvers;
    }

    /**
     * Returns a string representation according to the given param's type
     *
     * @param $obj
     * @return string
     */
    private function getRepresentation($obj)
    {
        if (is_object($obj)) {
            return get_class($obj);
        } else {
            return (string) $obj;
        }
    }
}
