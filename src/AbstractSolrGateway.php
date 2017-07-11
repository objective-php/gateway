<?php

namespace ObjectivePHP\Gateway;

use ObjectivePHP\Gateway\Entity\EntityInterface;
use ObjectivePHP\Gateway\Entity\EntitySet;
use ObjectivePHP\Gateway\Entity\EntitySetDescriptorInterface;
use ObjectivePHP\Gateway\Entity\ResultSetInterface;
use ObjectivePHP\Gateway\Entity\PaginatedEntitySet;
use Solarium\Client;
use Solarium\Core\Query\QueryInterface;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result;


abstract class AbstractSolrGateway extends AbstractGateway
{
    /**
     * Solr client.
     *
     * @var Client
     */
    protected $client;
    
    public function fetchAll(EntitySetDescriptorInterface $descriptor) : ResultSetInterface
    {
        $query = $this->getClient()->createSelect();
        
        $filters = $descriptor->getFilters();
        foreach ($filters as $filter) {
            switch ($filter['operator']) {
                
                default:
                    $filterQuery = $filter['property'] . ':' . $filter['value'];
                    break;
            }
            
            $query->createFilterQuery($filter['property'])->setQuery($filterQuery);
        }
        
        if($size = $descriptor->getSize())
        {
            $this->paginateNextQuery = false;
            $query->setStart(0)->setRows($size);
        } else if($page = $descriptor->getPage())
        {
            $this->paginate($page, $descriptor->getPageSize());
        }
        
        
        return $this->query($query);
    }
    
    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }
    
    /**
     * @param Client $client
     *
     * @return AbstractSolrGateway
     */
    public function setClient(Client $client): AbstractSolrGateway
    {
        $this->client = $client;
        
        return $this;
    }
    
    public function query(QueryInterface $query)
    {
        $this->preparePagination($query);
        
        /** @var Result $result */
        $result = $this->getClient()->execute($query);
        
        
        $resultSet = ($this->paginateNextQuery) ? new PaginatedEntitySet() : new EntitySet();
        
        if($resultSet instanceof PaginatedEntitySet)
        {
            $resultSet->setCurrentPage($this->currentPage)->setPerPage($this->perPage)->setTotal($result->getNumFound());
        }
        
        /** @var Document $document */
        foreach($result->getDocuments() as $document)
        {
            $entity = $this->entityFactory($document->getFields());
            $resultSet[] = $entity;
        }
        
        $this->reset();
        
        return $resultSet;
    }
    
    protected function preparePagination(QueryInterface $query)
    {
        if ($this->paginateNextQuery && $query instanceof Query) {
            $start = ($this->currentPage - 1) * $this->perPage;
            $query->setStart($start)->setRows($this->perPage);
        }
    }
    
    /**
     * @param EntityInterface $entity
     *
     * @throws Exception
     */
    public function persist(EntityInterface $entity)
    {
        throw new Exception('Not implemented yet');
    }
    
    /**
     * @param EntityInterface $entity
     *
     * @throws Exception
     */
    public function delete(EntityInterface $entity)
    {
        throw new Exception('Not implemented yet');
    }
    
}
