<?php

namespace ObjectivePHP\Gateway;

use ObjectivePHP\Gateway\Entity\EntityInterface;
use ObjectivePHP\Gateway\Entity\EntitySetDescriptorInterface;
use ObjectivePHP\Gateway\ResultSet\ResultSetInterface;

/**
 * Interface GatewayInterface
 *
 * @package ObjectivePHP\Gateway
 */
interface GatewayInterface
{
    
    /**
     * Retrieve entities from persistence layer
     *
     * @param EntitySetDescriptorInterface $descriptor
     *
     * @return ResultSetInterface
     */
    public function fetchAll(EntitySetDescriptorInterface $descriptor) : ResultSetInterface;

    /**
     * Persist one or more entities
     *
     * Save or update entities representation in persitence layer
     *
     * @param EntityInterface $entity
     * @return bool
     */
    public function persist(EntityInterface ...$entities);

    /**
     * @param EntityInterface[] ...$entities
     * @return mixed
     */
    public function update(EntityInterface ...$entities);

    /**
     * @param EntitySetDescriptorInterface $descriptor
     * @param mixed                        $data          Traversable data container (['field' => 'value'])
     *
     * @return mixed
     */
    public function batchUpdate(EntitySetDescriptorInterface $descriptor, $data);
    
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
     * @param EntitySetDescriptorInterface $descriptor
     *
     * @return bool
     */
    public function batchDelete(EntitySetDescriptorInterface $descriptor);
    
}
