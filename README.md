# bitexpert/adroit
This package provides a [PSR-7](http://www.php-fig.org/psr/psr-7/) compatible [ADR](http://pmjones.io/adr/) middleware. 
Have a look at the [adroit-disco-demo](https://github.com/bitExpert/adroit-disco-demo) project to find out how to use
Adroit.

[![Build Status](https://travis-ci.org/bitExpert/adroit.svg?branch=master)](https://travis-ci.org/bitExpert/adroit)
[![Dependency Status](https://www.versioneye.com/php/bitexpert:adroit/dev-master/badge.svg)](https://www.versioneye.com/php/bitexpert:adroit/dev-master)


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
The actionToken is basically used to identify a route. Adroit comes with a default implementation of an ActionResolver 
(\bitExpert\Adroit\Action\Resolver\ContainerAwareActionResolver) which uses any 
[container-interop](https://github.com/container-interop/container-interop) compatible DI container as a backend.

Of course you may implement your own ActionResolvers using the (\bitExpert\Adroit\Action\ActionResolver) interface.

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

Of course you may implement your own ResponderResolvers using the (\bitExpert\Adroit\Responder\ResponderResolver) interface.

```php
/** @var \Interop\Container\ContainerInterface $container */
$responderResolver = new \bitExpert\Adroit\Responder\Resolver\ContainerAwareResponderResolver($container);
```
```

Domain(Payload)
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

Actions come in two flavours as well: The SimpleForwardAction (\bitExpert\Adroit\Action\SimpleForwardAction) which will
always return the defined responder no matter what. In case you want to implement your own action logic (who does not 
want that?) extend the \bitExpert\Adroit\Action\AbstractAction base class and simply implement the execute() method. In
case you need some more customizations implement the \bitExpert\Adroit\Action\Action interface.

You may also use simple Closures as Actions in case you need to implement something really fast.

Action classes are allowed to either return a DomainPayload object (\bitExpert\Adroit\Domain\DomainPayload) or an PSR-7
response object implementing the \Psr\Http\Message\ResponseInterface interface. By default you should aim to return a 
DomainPayload object. The PSR-7 response might come in handy when you have to deal with file downloads where you most 
likely not want to read the file in your action class, push the content to the responder just to to write it to the response
message body.

The Payload object takes an $type  as well as an array of domain data. The
DomainPayload object will be passed "as-is" to the responder:

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

Responders have to return a PSR-7 response object. Responders are not forced to implement (\bitExpert\Adroit\Responder\Responder)
interface so you **may** use Closures as well but implementing the interface is recommended:

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
ResponderResolver(s). For the following example we use fictional "ArrayResolvers" which are configured using
an array of mappings between the action identifier and the action and the domain payload type to the appropriate responder:


```php
<?php

// create the action resolver
$actionResolver = new ArrayActionResolver([
    'helloAction' => function (ServerRequestInterface $request, ResponseInterface $response) {
        return new CustomPayload('hello', [
            'name' => 'World'
        ]);
    }
]);


// create the responder resolver
$responderResolver = new ArrayResponderResolver([
    'hello' => function (Payload $domainPayload, ResponseInterface $response) {
        $request->getBody()->rewind();
        $request->getBody()->write('Hello ' . $domainPayload->getValue('name'));
        return $response;
    };
]);


$adroit = AdroitMiddleware::createDefault([$actionResolver], [$responderResolver], 'action');
$request = ServerRequestFactory::fromGlobals()->withAttribute('action', 'helloAction');

$response = $adroit($request, new Response());
$emitter = new SapiEmitter();
$emitter->emit($response);

```
As you can see, you also may use simple callables as actions and responders.

Adroit itself does not depend on a concrete PSR-7 implementation which means you should be able to use it in your set-up
without running into problems. Just for the unit tests Adroit depends on zendframework/zend-diactoros as a PSR-7 implementation.

Routing
-------
To avoid external dependencies we removed routing from Adroit since this may be achieved by using any routing mechanism you like.
You just need to ensure that action identifying value will be set to a request attribute of your choice and tell the
ActionMiddleware where to look for it.

License
-------

Adroit is released under the Apache 2.0 license.
