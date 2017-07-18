<?php

namespace ObjectivePHP\Gateway;

use ObjectivePHP\Gateway\Entity\EntityInterface;
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

    const FETCH = 1;
    const FETCH_ONE = 2;
    const FETCH_ALL = 4;
    const PERSIST = 8;
    const UPDATE = 16;
    const DELETE = 32;
    const PURGE = 64;

    public function fetch(ResultSetDescriptorInterface $resultSetDescriptor): ProjectionInterface;

    /**
     * Retrieve projections from persistence layer
     *
     * @param ResultSetDescriptorInterface $descriptor
     *
     * @return ResultSetInterface
     */
    public function fetchAll(ResultSetDescriptorInterface $descriptor): ResultSetInterface;

    /**
     * Retrieve one single projection from persistence layer
     *
     * @return EntityInterface
     */
    public function fetchOne($key): EntityInterface;

    /**
     * Persist one or more entities
     *
     * Save or update entities representation in persistence layer
     *
     * @param EntityInterface[] $entities
     * @return bool
     */
    public function persist(EntityInterface ...$entities): bool;


    /**
     * @param ResultSetDescriptorInterface $descriptor
     * @param mixed $data Traversable data container (['field' => 'value'])
     *
     * @return mixed
     */
    public function update(ResultSetDescriptorInterface $descriptor, $data);

    /**
     * Delete one or more entities
     *
     * @param EntityInterface $entity
     *
     * @return bool
     */
    public function delete(EntityInterface ...$entities);

    /**
     * Delete entities matching descriptor
     *
     * @param ResultSetDescriptorInterface $descriptor
     *
     * @return bool
     */
    public function purge(ResultSetDescriptorInterface $descriptor);

    /**
     * Tells MetaGateway whether actual gateway can fetch/push to data to backend using given method and parameters
     *
     * @param $method
     *
     * @return bool
     */
    public function can($method, array $parameters): bool;

}
