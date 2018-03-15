<?php

namespace ObjectivePHP\Gateway;

use ObjectivePHP\Gateway\Projection\ProjectionInterface;
use ObjectivePHP\Gateway\ResultSet\Descriptor\ResultSetDescriptorInterface;
use ObjectivePHP\Gateway\ResultSet\ResultSetInterface;

/**
 * Interface GatewayInterface
 *
 * @package ObjectivePHP\Gateway
 */
interface GatewayInterface
{
    const FETCH     = 1;
    const FETCH_ONE = 2;
    const FETCH_ALL = 4;
    const PERSIST   = 8;
    const UPDATE    = 16;
    const DELETE    = 32;
    const PURGE     = 64;

    const WRITE = self::PERSIST | self::UPDATE | self::DELETE | self::PURGE;
    const READ  = self::FETCH | self::FETCH_ONE | self::FETCH_ALL;
    const ALL   = self::READ | self::WRITE;

    const HAS_MANY = 1;
    const HAS_ONE = 2;

    /**
     * Retrieve projections from persistence layer
     *
     * @param ResultSetDescriptorInterface $resultSetDescriptor
     *
     * @return ProjectionInterface
     */
    public function fetch(ResultSetDescriptorInterface $resultSetDescriptor): ProjectionInterface;

    /**
     * Retrive a result set of entities from persistence layer
     *
     * @param ResultSetDescriptorInterface $descriptor
     *
     * @return ResultSetInterface
     */
    public function fetchAll(ResultSetDescriptorInterface $descriptor): ResultSetInterface;

    /**
     * Retrieve one single projection from persistence layer
     *
     * @param mixed $key
     *
     * @return object
     */
    public function fetchOne($key);

    /**
     * Persist one or more entities
     *
     * Save or update entities representation in persistence layer
     *
     * @param object[] $entities
     */
    public function persist(...$entities);


    /**
     * Update a collection of entity
     *
     * @param ResultSetDescriptorInterface $descriptor
     * @param mixed                        $data       Traversable data container (['field' => 'value'])
     */
    public function update(ResultSetDescriptorInterface $descriptor, $data);

    /**
     * Delete one or more entities
     *
     * @param object[] $entities
     */
    public function delete(...$entities);

    /**
     * Delete entities matching descriptor
     *
     * @param ResultSetDescriptorInterface $descriptor
     */
    public function purge(ResultSetDescriptorInterface $descriptor);

    /**
     * Tells MetaGateway whether actual gateway can fetch/push to data to backend using given method and parameters
     *
     * @param int   $method
     * @param array $parameters
     *
     * @return bool
     */
    public function can($method, ...$parameters): bool;
}
