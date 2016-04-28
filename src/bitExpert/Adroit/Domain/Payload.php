<?php
/**
 * This file is part of the Adroit package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adroit\Domain;

/**
 * The domain payload object represents the domain's data it's state and type
 *
 * @api
 */
interface Payload
{
    /**
     * Returns the type of the payload
     *
     * @return string
     */
    public function getType();

    /**
     * Sets a model attribute. Will overwrite existing key => value pairs, if
     * the key already exists. If you use an array as key, the given attributes will
     * be set accordingly
     *
     * Note that this method can be chained, so you may use it comfortably like
     * $model->set('attr1', 'value1')
     *       ->set('attr2', 'value2')
     *       ...
     *
     * @param string|array $key
     * @param mixed $value
     * @return DomainPayloadInterface
     */
    public function withValue($key, $value = null);

    /**
     * Returns a model attribute in case the given key exists. If the key does
     * not exist null will be returned.
     *
     * @param string|null $key the key to retrieve the value for
     * @param mixed $default the default value to return if the given key was not found
     * @return mixed
     */
    public function getValue($key, $default = null);

    /**
     * Returns all attributes as an associative key->value array
     *
     * @return array
     */
    public function getValues();

    /**
     * Sets the status information about the payload and returns
     * the whole object for chainable use
     *
     * @param mixed $status
     * @return \bitExpert\Adroit\Domain\DomainPayload
     */
    public function withStatus($status);

    /**
     * Returns the status information about the payload
     *
     * @return mixed
     */
    public function getStatus();
}
