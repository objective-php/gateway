<?php

namespace ObjectivePHP\Gateway;

use ObjectivePHP\Events\EventsHandler;
use ObjectivePHP\Events\EventsHandlerAwareInterface;
use ObjectivePHP\Events\EventsHandlerAwareTrait;
use ObjectivePHP\Gateway\Entity\Entity;
use ObjectivePHP\Gateway\Entity\EntityInterface;
use ObjectivePHP\Gateway\Exception\GatewayException;
use ObjectivePHP\Gateway\Projection\ProjectionInterface;
use Zend\Hydrator\ArraySerializable;
use Zend\Hydrator\ClassMethods;
use Zend\Hydrator\HydratorInterface;
use Zend\Hydrator\NamingStrategy\UnderscoreNamingStrategy;
use Zend\Hydrator\NamingStrategyEnabledInterface;

/**
 * Class AbstractGateway
 *
 * @package Fei\ApiServer\Gateway
 */
abstract class AbstractGateway implements GatewayInterface, EventsHandlerAwareInterface
{
    use EventsHandlerAwareTrait;

    const FETCH_ENTITIES   = 1;
    const FETCH_PROJECTION = 2;

    /** @var string */
    const DEFAULT_ENTITY_CLASS = Entity::class;

    /** @var string */
    protected $entityClass;

    /** @var HydratorInterface */
    protected $hydrator;

    /** @var string */
    protected $hydratorClass;

    /** @var string */
    protected $defaultEntityCollection = EntityInterface::DEFAULT_ENTITY_COLLECTION;

    /** @var int */
    protected $allowedMethods = self::ALL;

    /** @var array */
    protected $delegatePersisters = [];

    /** @var EventsHandler */
    protected $eventHandler;

    /** @var array */
    protected $options = [];

    /** @var array */
    protected $methodsMapping = [
        'fetch'    => self::FETCH,
        'fetchOne' => self::FETCH_ONE,
        'fetchAll' => self::FETCH_ALL,
        'persist'  => self::PERSIST,
        'create'  => self::CREATE,
        'update'   => self::UPDATE,
        'delete'   => self::DELETE,
        'purge'    => self::PURGE
    ];

    /**
     * Get EntityClass
     *
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @param string $entityClass
     *
     * @return $this
     */
    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @param string $method
     * @param mixed  ...$parameters
     *
     * @return bool
     */
    public function can(string $method, ...$parameters): bool
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
        return isset($this->methodsMapping[$method]) ? ($this->methodsMapping[$method] & $this->allowedMethods) : method_exists(
            $this,
            $method
        );
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
    public function setAllowedMethods(int $allowedMethods): self
    {
        $this->allowedMethods = $allowedMethods;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultEntityClass(): string
    {
        return self::DEFAULT_ENTITY_CLASS;
    }

    /**
     * @param mixed $defaultEntityClass
     *
     * @return $this
     */
    public function setDefaultEntityClass($defaultEntityClass): self
    {
        $this->defaultEntityClass = $defaultEntityClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getHydratorClass(): string
    {
        return $this->hydratorClass;
    }

    /**
     * @param string $hydratorClass
     *
     * @return $this
     */
    public function setHydratorClass(string $hydratorClass): self
    {
        $this->hydratorClass = $hydratorClass;

        return $this;
    }

    /**
     * @return array
     */
    public function getMethodsMapping(): array
    {
        return $this->methodsMapping;
    }

    /**
     * @param array $methodsMapping
     *
     * @return $this
     */
    public function setMethodsMapping(array $methodsMapping): self
    {
        $this->methodsMapping = $methodsMapping;

        return $this;
    }

    /**
     * Get DefaultCollection
     *
     * @return string
     */
    public function getDefaultEntityCollection(): string
    {
        return $this->defaultEntityCollection;
    }

    /**
     * Set DefaultCollection
     *
     * @param string $defaultEntityCollection
     *
     * @return $this
     */
    public function setDefaultEntityCollection(string $defaultEntityCollection): self
    {
        $this->defaultEntityCollection = $defaultEntityCollection;

        return $this;
    }

    /**
     * @param $property
     * @param $persister
     */
    public function registerDelegatePersister($property, $persister)
    {
        $this->delegatePersisters[$property] = $persister;
    }

    /**
     * @return array
     */
    public function getDelegatePersisters(): array
    {
        return $this->delegatePersisters;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param $data
     *
     * @return array|ProjectionInterface
     *
     * @throws GatewayException
     */
    protected function entityFactory($data)
    {
        $entityClass = $this->entityClass ?? self::DEFAULT_ENTITY_CLASS;

        if (!$entityClass) {
            throw new GatewayException('No entity class provided.');
        }

        if (!class_exists($entityClass)) {
            throw new GatewayException(
                sprintf('Target entity class "%s" not found.', $entityClass),
                GatewayException::ENTITY_NOT_FOUND
            );
        }

        /** @var ProjectionInterface $entity */
        $entity = new $entityClass;

        if (!$entity instanceof EntityInterface) {
            throw new GatewayException(
                sprintf(
                    'Entity class "%s" does not implement "%s".',
                    $entityClass,
                    EntityInterface::class
                ),
                GatewayException::INVALID_ENTITY
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
        if ($this->hydrator === null) {
            $entityClass = $this->entityClass ?? self::DEFAULT_ENTITY_CLASS;
            if ($entityClass !== self::DEFAULT_ENTITY_CLASS) {
                $className = $this->hydratorClass ?: ClassMethods::class;
            } else {
                $className = ArraySerializable::class;
            }

            /** @var HydratorInterface $hydrator */
            $hydrator = new $className;
            if ($hydrator instanceof NamingStrategyEnabledInterface) {
                $hydrator->setNamingStrategy(new UnderscoreNamingStrategy());
            }

            $this->hydrator = $hydrator;
        }

        return $this->hydrator;
    }

    /**
     * @param HydratorInterface $hydrator
     *
     * @return $this
     */
    public function setHydrator(HydratorInterface $hydrator): self
    {
        $this->hydrator = $hydrator;

        return $this;
    }
}
