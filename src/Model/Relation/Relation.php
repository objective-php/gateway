<?php

namespace ObjectivePHP\Gateway\Model\Relation;

/**
 * Class Relation
 *
 * @package ObjectivePHP\Gateway\Model
 */
class Relation
{
    /**
     * @var string
     */
    protected $entityClass;

    /**
     * Relation constructor.
     *
     * @param string $entityClass
     */
    public function __construct(string $entityClass)
    {
        $this->setEntityClass($entityClass);
    }

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
}
