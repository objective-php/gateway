<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 15/06/2017
 * Time: 11:19
 */

namespace ObjectivePHP\Gateway\ResultSet\Descriptor;


/**
 * Class ResultSetDescriptor
 *
 * @package ObjectivePHP\Gateway\Entity
 */
class ResultSetDescriptor implements ResultSetDescriptorInterface
{
    /**
     * @var array
     */
    protected $filters = [];
    
    /**
     * @var int
     */
    protected $page = 0;
    
    /**
     * @var int
     */
    protected $pageSize = self::DEFAULT_PAGE_SIZE;
    
    /**
     * @var array
     */
    protected $sort = [];
    
    /**
     * @var string
     */
    protected $groupBy = '';
    
    /**
     * @var array
     */
    protected $aggregationRules = [];
    
    /**
     * @var int
     */
    protected $size = 0;
    
    /**
     * @var string
     */
    protected $collectionName = null;
    
    /**
     * ResultSetDescriptor constructor.
     *
     * @param string $collectionName
     */
    public function __construct($collectionName)
    {
        $this->collectionName = $collectionName;
    }
    
    
    /**
     * @param        $property
     * @param        $value
     * @param string $operator
     *
     * @return $this
     */
    public function addFilter($property, $value, $operator = self::OP_EQUALS)
    {
        $this->filters[] = compact('property', 'value', 'operator');
        
        return $this;
    }
    
    /**
     * @param int $page
     * @param int $pageSize
     *
     * @return $this
     */
    public function paginate($page = 1, $pageSize = self::DEFAULT_PAGE_SIZE)
    {
        $this->page     = $page;
        $this->pageSize = $pageSize;
        
        return $this;
    }
    
    /**
     * @param     $property
     * @param int $direction
     *
     * @return $this
     */
    public function sort($property, $direction = SORT_ASC)
    {
        $this->sort[$property] = $direction;
        
        return $this;
    }
    
    /**
     * @param $property
     *
     * @return $this
     */
    public function groupBy($property)
    {
        $this->groupBy = $property;
        
        return $this;
    }
    
    /**
     * @param $property
     * @param $aggregationType
     *
     * @return $this
     */
    public function aggregate($property, $aggregationType)
    {
        $this->aggregationRules[$property] = $aggregationType;
        
        return $this;
    }
    
    public function collection(string $collectionName)
    {
        $this->collectionName = $collectionName;
        
        return $this;
    }
    
    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
    
    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }
    
    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }
    
    /**
     * @return array
     */
    public function getAggregationRules(): array
    {
        return $this->aggregationRules;
    }
    
    /**
     * @return string
     */
    public function getGroupedByProperty(): string
    {
        return $this->groupBy;
    }
    
    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }
    
    /**
     * @param $size
     *
     * @return $this
     */
    public function setSize($size)
    {
        $this->size = $size;
        
        return $this;
    }
    
    public function getCollectionName(): string
    {
        return $this->collectionName;
    }
    
}
