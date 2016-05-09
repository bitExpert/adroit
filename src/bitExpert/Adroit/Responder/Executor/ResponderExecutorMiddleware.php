<?php
/**
 * Created by PhpStorm.
 * User: phildenbrand
 * Date: 09.05.16
 * Time: 10:20
 */

namespace bitExpert\Adroit\Responder\Executor;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponderExecutorMiddleware
{
    protected $responderAttribute;
    protected $domainPayloadAttribute;

    public function __construct($responderAttribute, $domainPayloadAttribute)
    {
        $this->responderAttribute = $responderAttribute;
        $this->domainPayloadAttribute = $domainPayloadAttribute;
    }


    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $payload = $this->getPayload($request);

        if (!$payload) {
            throw new ResponderExecutionException('Could not find domain payload in request');
        }

        $responder = $this->getResponder($request);

        if (!$responder) {
            throw new ResponderExecutionException('Could not find responder in request');
        }

        $response = $responder($payload, $response);

        if (!($response instanceof ResponseInterface)) {
            throw new ResponderExecutionException(sprintf(
                'The responder "%s" did not return an instance of "%s"',
                is_object($responder) ? get_class($responder) : (string)$responder,
                ResponseInterface::class
            ));
        }

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    protected function getResponder(ServerRequestInterface $request)
    {
        return $request->getAttribute($this->responderAttribute);
    }

    protected function getPayload(ServerRequestInterface $request)
    {
        return $request->getAttribute($this->domainPayloadAttribute);
    }
}
