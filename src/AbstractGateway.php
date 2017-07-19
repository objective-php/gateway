<?php

namespace ObjectivePHP\Gateway;

use ObjectivePHP\Gateway\Entity\EntityInterface;
use ObjectivePHP\Gateway\Exception\GatewayException;
use ObjectivePHP\Gateway\Projection\ProjectionInterface;
use Zend\Hydrator\ClassMethods;
use Zend\Hydrator\HydratorInterface;
use Zend\Hydrator\NamingStrategy\UnderscoreNamingStrategy;
use Zend\Hydrator\NamingStrategyEnabledInterface;

/**
 * Class AbstractGateway
 *
 * @package Fei\ApiServer\Gateway
 */
abstract class AbstractGateway implements GatewayInterface
{
    
    /**
     * @var
     */
    protected $defaultEntityClass;
    
    /**
     * @var
     */
    protected $entityClass;
    
    /**
     * @var HydratorInterface
     */
    protected $hydrator;
    
    /**
     * @var string
     */
    protected $hydratorClass;
    
    protected $allowedMethods = self::ALL;
    
    protected $methodsMapping = array(
        'fetch'    => self::FETCH,
        'fetchOne' => self::FETCH_ONE,
        'fetchAll' => self::FETCH_ALL,
        'persist'  => self::PERSIST,
        'update'   => self::UPDATE,
        'delete'   => self::DELETE,
        'purge'    => self::PURGE
    );
    
    /**
     * @param $entityClass
     *
     * @return $this
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
        
        return $this;
    }
    
    public function can($method, ...$parameters): bool
    {

        // if method does not exist, simply return false
        if (!method_exists($this, $method)) {
            return false;
        }

        // look for a dedicated method to answer the question
        $canMethod = 'can' . ucfirst($method);
        if (method_exists($this, $canMethod)) {
            return $this->$canMethod(...$parameters);
        }

        // finally, fallback to the default behaviour: is the method standard and reported as allowed, or does
        // the method exists (for non-standard methods only)
        return (isset($this->methodsMapping[$method])) ? $this->methodsMapping[$method] & $this->allowedMethods : method_exists($this, $method);
    }
    
    /**
     * @return int
     */
    public function getAllowedMethods(): int
    {
        return $this->allowedMethods;
    }
    
    /**
     * @param int $allowedMethods
     *
     * @return $this
     */
    public function setAllowedMethods(int $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;
        
        return $this;
    }
    
    /**
     * @param $data
     *
     * @return ProjectionInterface|array
     */
    protected function entityFactory($data)
    {
        $entityClass = $this->entityClass ?? $this->defaultEntityClass;
        
        if (!$entityClass) {
            throw new GatewayException('No entity class provided.');
        }
        
        if (!class_exists($entityClass)) {
            throw new GatewayException(sprintf('Target entity class "%s" not found.', $entityClass));
        }
        
        /** @var ProjectionInterface $entity */
        $entity = new $entityClass;
        
        if (!$entityClass instanceof EntityInterface) {
            throw new GatewayException(
                sprintf(
                    'Entity class "%s" does not implement "%s".', $entityClass,
                    EntityInterface::class
                )
            );
        }
        
        $this->getHydrator()->hydrate($data, $entity);
        
        return $entity;
    }
    
    /**
     * @return HydratorInterface
     */
    public function getHydrator(): HydratorInterface
    {
        if (is_null($this->hydrator)) {
            $className = $this->hydratorClass ?? ClassMethods::class;
            /** @var HydratorInterface $hydrator */
            $hydrator = new $className;
            if ($hydrator instanceof NamingStrategyEnabledInterface) {
                $hydrator->setNamingStrategy(new UnderscoreNamingStrategy());
            }
            $this->hydrator = $hydrator;
        }
        
        return $this->hydrator;
    }
}
