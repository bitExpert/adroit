# bitexpert/disco
This package provides a [PSR-7](http://www.php-fig.org/psr/psr-7/) compatible [ADR](http://pmjones.io/adr/) middleware.

[![Build Status](https://travis-ci.org/bitExpert/adroit.svg?branch=release%2Fr0.2.0)](https://travis-ci.org/bitExpert/adroit)
[![Dependency Status](https://www.versioneye.com/php/bitexpert:adroit/0.2.0/badge?style=flat)](https://www.versioneye.com/php/bitexpert:adroit/0.2.0)


Installation
------------

The preferred way of installation is through Composer. Simply add `bitexpert/adroit` as a dependency:

```
composer.phar require bitexpert/adroit
```

Usage
-----

The configure the \bitExpert\Adroit\AdroitMiddleware middleware you need provide an array of 
\bitExpert\Adroit\Action\Resolver\ActionResolver, an array of \bitExpert\Adroit\Responder\Resolver\ResponderResolver and
a router instance.

ActionResolver
--------------

As the name implies ActionResolvers are responsible for resolving an action class instance from the so-called actionToken.
The actionToken is basically used to identify a route. Adroit comes with a default implementation of an ActionResolver 
(\bitExpert\Adroit\Action\Resolver\ContainerAwareActionResolver) which uses any 
[container-interop](https://github.com/container-interop/container-interop) compatible DI container as a backend.

```php
/** @var \Interop\Container\ContainerInterface $container */
$actionResolver = new \bitExpert\Adroit\Action\Resolver\ContainerAwareActionResolver($container);
```

ResponderResolver
-----------------

Similar to the ActionResolvers the ResponderResolvers are responsible for resolving an responder class instance from the
$type defined in the DomainPayload instance. Adroit comes with a default implementation of an ResponderResolver 
(\bitExpert\Adroit\Responder\Resolver\ContainerAwareResponderResolver) which uses any 
[container-interop](https://github.com/container-interop/container-interop) compatible DI container as a backend.

```php
/** @var \Interop\Container\ContainerInterface $container */
$responderResolver = new \bitExpert\Adroit\Responder\Resolver\ContainerAwareResponderResolver($container);
```

In addition to that we ship a NegotiatingResponderResolver (\bitExpert\Adroit\Responder\Resolver\NegotiatingResponderResolver)
which can be used to select a responder based on the content type of the incoming request.

```php
$negotiatingResponderResolver = new \bitExpert\Adroit\Responder\Resolver\NegotiatingResponderResolver(
    [
        'text/html' => $responderResolver
    ]
);
```

Router
------

The router is responsible for resolving the actionToken from the given route as well building an url for a given 
actionToken (and it`s parameters). The router comes in two flavours, once as a simple PropertyRouter 
(\bitExpert\Adroit\Router\PropertyRouter) which will look up the actionToken based on an url parameter or the RegexRouter
(\bitExpert\Adroit\Router\RegexRouter) which will map the whole url to an actionToken.

```php
$baseUrl = 'http://myapp.loc:8080';
$router = new \bitExpert\Adroit\Router\RegexRouter($baseUrl);
$router->setRoutes(
    [
        new Route('GET', '/', 'index'),
        new Route('GET', '/question/[:title]_[:id]', 'question'),
        new Route(['GET', 'POST'], '/editquestion', 'editquestion')
    ]
);
```

Actions
-------

Actions come in two flavours as well: The SimpleForwardAction (\bitExpert\Adroit\Action\SimpleForwardAction) which will
always return the defined responder no matter what. In case you want to implement your own action logic (who does not 
want that?) extend the \bitExpert\Adroit\Action\AbstractAction base class and simply implement the execute() method. In
case you need some more customizations implement the \bitExpert\Adroit\Action\Action interface.

Action classes are allowed to either return a DomainPayload object (\bitExpert\Adroit\Domain\DomainPayload) or an PSR-7
response object implementing the \Psr\Http\Message\ResponseInterface interface. By default you should aim to return a 
DomainPayload object. The PSR-7 response might come in handy when you have to deal with file downloads where you most 
likely not want to read the file in your action class, push the content to the responder just to to write it to the response
message body.

The DomainPayload object takes an $type (an identifier for an responder) as well as an array of domain data. The 
DomainPayload object will be passed "as-is" to the responder.

Responders
----------

Responders have to return a PSR-7 response object which in will be returned by the AdroitMiddleware. Adroit ships two
different implementations by default. The JsonResponder (\bitExpert\Adroit\Responder\JsonResponder) which will simply 
json_encode() the DomainPayload data and the TwigResponder (\bitExpert\Adroit\Responder\TwigResponder) which will render
a Twig template with the given DomainPayload data.

Usage
-----

```php
/** @var \Psr\Http\Message\ServerRequestInterface $request */
/** @var \Psr\Http\Message\ResponseInterface $response */
$middleware = new \bitExpert\Adroit\AdroitMiddleware([$actionResolver], [$responderResolver], $router);
$response = $middleware($request, $response);
```

Adroit itself does not depend on a concrete PSR-7 implementation which means you should be able to use it in your set-up
without running into problems. Just for the unit tests Adroit depends on zendframework/zend-diactoros as a PSR-7 implementation.

License
-------

Adroit is released under the Apache 2.0 license.
