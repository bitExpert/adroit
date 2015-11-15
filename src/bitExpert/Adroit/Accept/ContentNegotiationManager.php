<?php

/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Accept;

use Negotiation\AcceptHeader;
use Negotiation\FormatNegotiator;
use Negotiation\NegotiatorInterface;
use Psr\Http\Message\MessageInterface;

/**
 * The ContentNegotiationManager parses the 'Accept' header of the request.
 *
 * @api
 */
class ContentNegotiationManager
{
    /**
     * @var NegotiatorInterface
     */
    protected $negotiator;

    /**
     * Creates a new {@link \bitExpert\Adroit\Accept\ContentNegotiationManager}.
     *
     * @param NegotiatorInterface|null $negotiator
     */
    public function __construct(NegotiatorInterface $negotiator = null)
    {
        if (null === $negotiator) {
            $negotiator = new FormatNegotiator();
        }

        $this->negotiator = $negotiator;
    }

    /**
     * Returns the "best match" of the given $priorities. Will return null in case
     * no match could be identified or a string containing the best matching Accept
     * header.
     *
     * @param MessageInterface $request
     * @param array $priorities A set of priorities.
     * @return null|string
     */
    public function getBestMatch(MessageInterface $request, array $priorities = array())
    {
        if (!$request->hasHeader('Accept')) {
            return null;
        }

        $header = $this->negotiator->getBest(implode(',', $request->getHeader('Accept')), $priorities);
        if ($header instanceof AcceptHeader) {
            return $header->getValue();
        }

        return null;
    }
}
