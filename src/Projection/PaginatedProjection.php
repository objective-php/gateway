<?php

namespace ObjectivePHP\Gateway\Projection;

use ObjectivePHP\Gateway\ResultSet\PaginatedResultSetInterface;

/**
 * Class Projection
 * @package ObjectivePHP\Gateway\Projection
 */
class PaginatedProjection extends Projection implements PaginatedProjectionInterface
{
    
    /**
     * @var int
     */
    protected $currentPage;
    
    /**
     * @var int
     */
    protected $perPage;
    
    /**
     * @var int
     */
    protected $total;
    
    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }
    
    /**
     * @param mixed $currentPage
     *
     * @return $this
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
        
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getPageSize()
    {
        return $this->perPage;
    }
    
    /**
     * @param mixed $perPage
     *
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
        
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getTotal()
    {
        return $this->total;
    }
    
    /**
     * @param mixed $total
     *
     * @return $this
     */
    public function setTotal($total)
    {
        $this->total = $total;
        
        return $this;
    }
}
