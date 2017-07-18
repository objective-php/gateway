<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 18/07/2017
 * Time: 14:17
 */

namespace ObjectivePHP\Gateway;

use ObjectivePHP\Gateway\Entity\EntityInterface;
use ObjectivePHP\Gateway\Exception\MetaGatewayException;
use ObjectivePHP\Gateway\Projection\ProjectionInterface;
use ObjectivePHP\Gateway\ResultSet\Descriptor\ResultSetDescriptorInterface;
use ObjectivePHP\Gateway\ResultSet\ResultSetInterface;

/**
 * Class MetaGateway
 *
 * @package ObjectivePHP\Gateway
 */
class MetaGateway implements MetaGatewayInterface
{
    const WRITING_MASTER = 1;
    const ALLOWED_FLAGS = array(self::WRITING_MASTER);
    
    /**
     * @var array
     */
    protected $gateways          = array();
    
    protected $writeMaster;
    
    protected $readingPriorities = array();
    
    protected $writingPriorities = array();
    
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
     * @param ResultSetDescriptorInterface $descriptor
     *
     * @return ProjectionInterface
     */
    public function fetch(ResultSetDescriptorInterface $descriptor): ProjectionInterface
    {
        return $this->proxyReadingRequest(__FUNCTION__, $descriptor);
    }
    
    /**
     * @param       $method
     * @param array ...$parameters
     */
    protected function proxy($method, ...$parameters)
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->can($method, ...$parameters)) {
                $result = $gateway->$method(...$parameters);
            }
        }
    }
    
    /**
     * @param ResultSetDescriptorInterface $descriptor
     *
     * @return ResultSetInterface
     */
    public function fetchAll(ResultSetDescriptorInterface $descriptor): ResultSetInterface
    {
        return $this->proxyReadingRequest(__FUNCTION__, $descriptor);
    }
    
    /**
     * @param $key
     *
     * @return EntityInterface
     */
    public function fetchOne($key): EntityInterface
    {
        return $this->proxyReadingRequest(__FUNCTION__, $key);
    }
    
    /**
     * @param EntityInterface[] ...$entities
     *
     * @return bool
     */
    public function persist(EntityInterface ...$entities): bool
    {
        return $this->proxyWritingRequest(__FUNCTION__, $entities);
    }
    
    /**
     * @param ResultSetDescriptorInterface $descriptor
     * @param mixed                        $data
     */
    public function update(ResultSetDescriptorInterface $descriptor, $data)
    {
        return $this->proxyWritingRequest(__FUNCTION__, $descriptor, $data);
    }
    
    /**
     * @param EntityInterface[] ...$entities
     */
    public function delete(EntityInterface ...$entities)
    {
        return $this->proxyWritingRequest(__FUNCTION__, $entities);
    }
    
    /**
     * @param ResultSetDescriptorInterface $descriptor
     */
    public function purge(ResultSetDescriptorInterface $descriptor)
    {
        return $this->proxyWritingRequest(__FUNCTION__, $descriptor);
    }
    
    /**
     * @param       $method
     * @param array $parameters
     *
     * @return bool
     */
    public function can($method, array $parameters): bool
    {
        return true;
    }
    
    protected function proxyReadingRequest($method, ...$parameters)
    {
        foreach($this->readingPriorities as $id)
        {
            if($this->gateways[$id]->can($method, $parameters))
            {
                return $this->gateways[$id]->$method(...$parameters);
            }
        }
        
    }
    
    /**
     * @param string           $id
     * @param GatewayInterface $gateway
     * @param int              $readingPriority
     * @param int              $writingPriority
     * @param int              $flags
     *
     * @return $this
     */
    public function registerGateway(
        string $id,
        GatewayInterface $gateway,
        int $readingPriority = 0,
        int $writingPriority = 0,
        int $flags = 0
    ) {
        $this->gateways[$id] = $gateway;
     
        $unknownFlags = $flags;
        
        foreach (self::ALLOWED_FLAGS as $flag) {
            if ($unknownFlags & $flag) {
                $unknownFlags -= $flag;
            }
        }
        
        if ($unknownFlags) {
            throw new MetaGatewayException(
                sprintf('Unknown registration flags %d', $flags)
            );
        }
        
        if ($writingPriority == PHP_INT_MAX && !$flags & self::WRITING_MASTER) {
            throw new MetaGatewayException('PHP_INT writing priority is reserved to the WRITING_MASTER gateway. You may have forgotten the ' . MetaGateway::class . '::WRITING_MASTER flag.');
        }
        
        if ($flags & self::WRITING_MASTER && isset($this->writingPriorities[PHP_INT_MAX])) {
            throw new MetaGatewayException('Cannot register two gateways as WRITING_MASTER');
        }
        
        $this->updateReadingPriorities($id, $readingPriority, $flags);
        $this->updateWritingPriorities($id, $writingPriority, $flags);
        
        return $this;
    }
    
    protected function updateReadingPriorities($id, $priority, $flags)
    {
        $readingPriorities = array();
        $inserted          = false;
        
        // recompute priorities if already set
        while (isset($this->readingPriorities[$priority])) {
            $priority--;
        }
        
        foreach ($this->readingPriorities as $position => $gatewayReference) {
            if ($priority > $position && !$inserted) {
                $readingPriorities[$priority] = $id;
                $inserted                     = true;
            }
            
            $readingPriorities[$position] = $gatewayReference;
        }
        
        // insert first priority
        if (!$inserted) {
            $readingPriorities[$priority] = $id;
        }
        
        $this->readingPriorities = $readingPriorities;
    }
    
    protected function updateWritingPriorities($id, $priority, $flags)
    {
        $writingPriorities = array();
        $inserted          = false;
        
        if ($flags & self::WRITING_MASTER) {
            $priority = PHP_INT_MAX;
        } else {
            // recompute priorities if already set
            while (isset($this->writingPriorities[$priority])) {
                $priority--;
            }
        }
        
        foreach ($this->writingPriorities as $position => $gatewayReference) {
            if ($priority > $position && !$inserted) {
                $writingPriorities[$priority] = $id;
                $inserted                     = true;
            }
            
            $writingPriorities[$position] = $gatewayReference;
        }
        
        // insert first priority
        if (!$inserted) {
            $writingPriorities[$priority] = $id;
        }
        
        $this->writingPriorities = $writingPriorities;
    }
    
    /**
     * @return array
     */
    public function getGateways()
    {
        return $this->gateways;
    }
    
    public function getReadingPriorities()
    {
        return $this->readingPriorities;
    }
    
    public function getWritingPriorities()
    {
        return $this->writingPriorities;
    }
    
}
