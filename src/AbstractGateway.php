<?php

namespace ObjectivePHP\Gateway;

use ObjectivePHP\Gateway\Entity\EntityInterface;
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
    protected $defaultTargetEntity;

    /**
     * @var
     */
    protected $targetEntity;

    /**
     * @var bool Tells whether new query results should be return as is
     */
    protected $rawDataForNextQuery = false;

    /**
     * @var
     */
    protected $cache;

    /**
     * @var array
     */
    protected $validatedEntities = [];

    /**
     * @var
     */
    protected $perPage;

    /**
     * @var bool
     */
    protected $paginateCurrentQuery = false;

    /**
     * @var bool
     */
    protected $paginateNextQuery = false;

    /**
     * @var
     */
    protected $currentPage;

    /**
     * @var int
     */
    protected $defaultPerPage = 20;

    /**
     * @var HydratorInterface
     */
    protected $hydrator;

    /**
     * @var string
     */
    protected $hydratorClass;

    /**
     * @param $entity
     *
     * @return $this
     */
    public function setTargetEntity($entity)
    {
        $this->targetEntity = $entity;

        return $this;
    }

    /**
     * @return AbstractGateway
     */
    public function raw()
    {
        $this->rawDataForNextQuery = true;

        return $this;
    }


    /**
     * @param      $page
     * @param null $resultsPerPage
     *
     * @return $this
     */
    public function paginate($page, $resultsPerPage = null)
    {

        $this->paginateNextQuery = true;

        $this->currentPage = $page;

        if (!is_null($resultsPerPage)) {
            $this->perPage = $resultsPerPage;
        }

        return $this;
    }

    /**
     * @param $data
     *
     * @return EntityInterface|array
     */
    protected function entityFactory($data)
    {
        $targetEntityClass = $this->targetEntity ?: $this->defaultTargetEntity;

        if ($this->rawDataForNextQuery || is_null($targetEntityClass)) {
            $data['context'] = $this->extractContext($data, true);
            return $data;
        }

        /** @var EntityInterface $entity */
        $entity = new $targetEntityClass;

        $this->getHydrator()->hydrate($data, $entity);

        return $entity;
    }

    protected function extractContext(&$data, $clear = false)
    {
        $contexts = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'context_') === 0) {
                $contexts[substr($key, 8)] = $value;
                if ($clear) unset($data[$key]);
            }
        }

        return $contexts;
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

        return $this->getHydrator();

    }
}
