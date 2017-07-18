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
abstract class AbstractGateway implements GatewayInterface, PaginableGatewayInterface
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

    /**
     * @param $data
     *
     * @return ProjectionInterface|array
     */
    protected function entityFactory($data)
    {
        $entityClass = $this->entityClass ?? $this->defaultEntityClass;

        if(!$entityClass)
        {
            throw new GatewayException('No entity class provided.');
        }

        if(!class_exists($entityClass))
        {
            throw new GatewayException(sprintf('Target entity class "%s" not found.', $entityClass));
        }

        /** @var ProjectionInterface $entity */
        $entity = new $entityClass;

        if (!$entityClass instanceof EntityInterface) {
            throw new GatewayException(sprintf('Entity class "%s" does not implement "%s".', $entityClass, EntityInterface::class));
        }

        $this->getHydrator()->hydrate($data, $entity);

        return $entity;
    }


    /**
     * @return HydratorInterface
     */
    public function getHydrator() : HydratorInterface
    {
        if(is_null($this->hydrator)) {
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
