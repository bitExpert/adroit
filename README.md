# bitexpert/adroit
This package provides a [PSR-7](http://www.php-fig.org/psr/psr-7/) compatible [ADR](http://pmjones.io/adr/) middleware. 

[![Build Status](https://travis-ci.org/bitExpert/adroit.svg?branch=master)](https://travis-ci.org/bitExpert/adroit)
[![Coverage Status](https://coveralls.io/repos/github/bitExpert/adroit/badge.svg?branch=master)](https://coveralls.io/github/bitExpert/adroit?branch=master)

Installation
------------

The preferred way of installing `bitexpert/adroit` is through Composer. Simply add `bitexpert/adroit` as a dependency:

```
composer.phar require bitexpert/adroit
```

Usage
-----

The configure the \bitExpert\Adroit\AdroitMiddleware middleware you need provide an array of 
\bitExpert\Adroit\Action\Resolver\ActionResolver, an array of \bitExpert\Adroit\Responder\Resolver\ResponderResolver and
a action request attribute telling adroit where to look for the action identifier.

ActionResolver
--------------

As the name implies ActionResolvers are responsible for resolving an action class instance from the so-called actionToken.
The actionToken is basically used to identify a route. Adroit comes with a default implementation of an [ActionResolver](src/bitExpert/Adroit/Action/Resolver/ContainerActionResolver.php) which uses any 
[container-interop](https://github.com/container-interop/container-interop) compatible DI container as a backend.

Of course you may implement your own ActionResolvers using the (\bitExpert\Adroit\Action\ActionResolver) interface.

```php
/** @var \Interop\Container\ContainerInterface $container */
$actionResolver = new \bitExpert\Adroit\Action\Resolver\ContainerActionResolver($container);
```

ResponderResolver
-----------------

Similar to the ActionResolvers the ResponderResolvers are responsible for resolving an responder class instance from the
$type defined in the DomainPayload instance. Adroit comes with a default implementation of an ResponderResolver 
(\bitExpert\Adroit\Responder\Resolver\ContainerAwareResponderResolver) which uses any 
[container-interop](https://github.com/container-interop/container-interop) compatible DI container as a backend.

Of course you may implement your own ResponderResolvers using the [ResponderResolver](src/bitExpert/Adroit/Responder/ResponderResolver.php) interface.

```php
/** @var \Interop\Container\ContainerInterface $container */
$responderResolver = new \bitExpert\Adroit\Responder\Resolver\ContainerAwareResponderResolver($container);
```

(Domain)Payload
---------------
You may define your own payload class(es) by implementing the \bitExpert\Adroit\Domain\Payload interface.
This gives you the opportunity to freely define the payload according to your needs.
This example implementation will be used in the documentation as follows:

```php
<?php
namespace Acme\Domain;
use bitExpert\Adroit\Domain\Payload;

class CustomPayload implements Payload
{
    protected $type;
    protected $data;

    public function __construct($type, array $data = [])
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function getType()
    {
        return $this->type;
    }

    public fuction getValue($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }
}
```



Actions
-------

In case you want to implement your own action logic (who does not want that?) you may use any callable following the signature of the [Action](src/bitExpert/Adroit/Action/Action.php) interface or create your own Action class and implement the interface. 

Action classes are allowed to either return an object which implements the [Payload](src/bitExpert/Adroit/Domain/Payload.php) interface or an PSR-7 response object implementing the \Psr\Http\Message\ResponseInterface interface. By default you should aim to return a 
Payload object. The PSR-7 response might come in handy when you have to deal with file downloads where you most 
likely not want to read the file in your action class, push the content to the responder just to to write it to the response message body.


```php
<?php 
use Acme\Domain\CustomPayload;
use bitExpert\Adroit\Action\Action;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HelloWorldAction implements Action
{
    /**
     * @inheritdoc
     */
    protected function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return new CustomPayload('hello', ['name' => 'World']);
    }
}

```

Responders
----------

Responders have to return a PSR-7 response object. Responders are not forced to implement the [Responder](src/bitExpert/Adroit/Responder/Responder.php) interface
so you **may** use Closures as well but implementing the interface is recommended:

```php
<?php 
namespace Acme\Responder\HelloResponder;

use bitExpert\Adroit\Responder\Responder;
use bitExpert\Adroit\Domain\Payload;
use Psr\Http\Message\ResponseInterface;

class HelloResponder implements Responder
{
    /**
     * @inheritdoc
     */
    public function __invoke(Payload $domainPayload, ResponseInterface $response)
    {
        $response->getBody()->rewind();
        $response->getBody()->write('Hello ' . $domainPayload->getValue('name'));
        
        return $response->withStatus(200)
    }
}

```

Usage
-----

Since Adroit provides a handy set of middlewares to achieve ADR you simply have to configure your ActionResolver(s) and
ResponderResolver(s). For the following example we use the ArrayContainer of [bitexpert/specialist](https://github.com/bitExpert/specialist)  which are configured using
an array of mappings between the action identifier and the action and the domain payload type to the appropriate responder:


```php
<?php
use bitExpert\Specialist\Container\ArrayContainer;

$container = new ArrayContainer([
    'helloAction' => function (ServerRequestInterface $request, ResponseInterface $response) {
        return new CustomPayload('hello', [
            'name' => 'World'
        ]);
    },
    'hello' => function (Payload $domainPayload, ResponseInterface $response) {
        $response->getBody()->rewind();
        $response->getBody()->write('Hello ' . $domainPayload->getValue('name'));
        return $response;
    };    
]);

// create the action resolver
$actionResolver = new ContainerActionResolver($container);


// create the responder resolver
$responderResolver = new ContainerResponderResolver($container);

// Provide the request attribute where the routing result identifier is kept
// and your resolvers
$adroit = new AdroitMiddleware('action', [$actionResolver], [$responderResolver]);

// create a request containing an action identifier inside the routing result attribute
$request = ServerRequestFactory::fromGlobals()->withAttribute('action', 'helloAction');

// and run adroit
$response = $adroit($request, new Response());
$emitter = new SapiEmitter();
$emitter->emit($response);

```
As you can see, you also may use simple callables as actions and responders.

Adroit itself does not depend on a concrete PSR-7 implementation which means you should be able to use it in your set-up
without running into problems. Just for the unit tests Adroit depends on zendframework/zend-diactoros as a PSR-7 implementation.

Middleware hooks
----------------
Adroit provides several hooks to be as flexible as a standard middleware pipe while
implementing the ADR paradigm.

You may use the following hooks to manipulate things in between the execution of
the middlewares needed for ADR itself:

```php
// Gets piped in front of the ActionResolverMiddleware
$adroit->beforeResolveAction($yourMiddleware);

// Gets piped in front of the ActionExecutorMiddleware
$adroit->beforeExecuteAction($yourMiddleware);

// Gets piped in front of the ResponderResolverMiddleware
$adroit->beforeResolveResponder($yourMiddleware);

// Gets piped in front of the ResponderExecutorMiddleware
$adroit->beforeExecuteResponder($yourMiddleware);
```

These hooks allow great flexibility but with great flexibility also comes
great responsibility ;-) Please note that the hooks are named "before"
and so are to implement the middlewares:

```php

function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) {

    // Your awesome code

    if ($next)
        $response = $next($request, $response);
    }

    return $response;
}

```

Of course you may implement it different, but that would not hit the "before" in the hook name.
Please be aware of that!


Routing
-------
To avoid external dependencies we removed routing from Adroit since this may be achieved by using any routing mechanism you like.
You just need to ensure that action identifying value will be set to a request attribute of your choice and tell the
ActionMiddleware where to look for it.

License
-------

Adroit is released under the Apache 2.0 license.
