<?php

namespace ObjectivePHP\Gateway;

use ObjectivePHP\Gateway\Exception\GatewayException;
use ObjectivePHP\Gateway\Model\ModelAwareInterface;
use ObjectivePHP\Gateway\Model\ModelAwareTrait;
use ObjectivePHP\Gateway\Model\Relation\Relation;
use Zend\Hydrator\ClassMethods;
use Zend\Hydrator\HydratorInterface;

/**
 * Class AbstractGateway
 *
 * @package Fei\ApiServer\Gateway
 */
abstract class AbstractGateway implements GatewayInterface, GatewaysFactoryAwareInterface, ModelAwareInterface
{
    use GatewaysFactoryAwareTrait, ModelAwareTrait;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var HydratorInterface
     */
    protected $hydrator;

    /**
     * @var int
     */
    protected $allowedMethods = self::ALL;

    /**
     * AbstractGateway constructor.
     *
     * @param string|null $entityClass
     */
    public function __construct(string $entityClass = null)
    {
        if (!is_null($entityClass)) {
            $this->setEntityClass($entityClass);
        }

        $this->init();
    }

    /**
     * Delegate constructor
     */
    protected function init()
    {
    }

    /**
     * @var array
     */
    protected $methodsMapping = [
        'fetch'    => self::FETCH,
        'fetchOne' => self::FETCH_ONE,
        'fetchAll' => self::FETCH_ALL,
        'persist'  => self::PERSIST,
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
     * Set EntityClass
     *
     * @param string $entityClass
     *
     * @return $this
     */
    public function setEntityClass(string $entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * Get Hydrator
     *
     * @return HydratorInterface
     */
    public function getHydrator(): HydratorInterface
    {
        if (is_null($this->hydrator)) {
            $this->hydrator = new ClassMethods();
        }

        return $this->hydrator;
    }

    /**
     * Set Hydrator
     *
     * @param HydratorInterface $hydrator
     *
     * @return $this
     */
    public function setHydrator(HydratorInterface $hydrator)
    {
        $this->hydrator = $hydrator;

        return $this;
    }

    /**
     * Get AllowedMethods
     *
     * @return int
     */
    public function getAllowedMethods(): int
    {
        return $this->allowedMethods;
    }

    /**
     * Set AllowedMethods
     *
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
     * Get MethodsMapping
     *
     * @return array
     */
    public function getMethodsMapping(): array
    {
        return $this->methodsMapping;
    }

    /**
     * Set MethodsMapping
     *
     * @param array $methodsMapping
     *
     * @return $this
     */
    public function setMethodsMapping(array $methodsMapping)
    {
        $this->methodsMapping = $methodsMapping;

        return $this;
    }

    /**
     * Create a new entity from array with the injected hydrator
     *
     * @param array $data
     *
     * @return object
     *
     * @throws GatewayException
     */
    public function entityFactory(array $data)
    {
        if (!$this->entityClass) {
            throw new GatewayException('No entity class provided.');
        }

        if (!class_exists($this->entityClass)) {
            throw new GatewayException(
                sprintf('Target entity class "%s" not found.', $this->entityClass),
                GatewayException::ENTITY_NOT_FOUND
            );
        }

        $entity = new $this->entityClass;

        $this->getHydrator()->hydrate($data, $entity);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
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
        return (isset($this->methodsMapping[$method]))
            ? ($this->methodsMapping[$method] & $this->allowedMethods)
            : method_exists($this, $method);
    }

    /**
     * Returns the delegated fields
     *
     * @param string $relationType
     *
     * @return array
     */
    protected function getRelatedFields($relationType = Relation::class): array
    {
        return $this->getModel()->getPropertiesFor($this->getEntityClass(), $relationType);
    }
}
