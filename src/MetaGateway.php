<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 18/07/2017
 * Time: 14:17
 */

namespace ObjectivePHP\Gateway;

use ObjectivePHP\Events\EventsHandlerAwareInterface;
use ObjectivePHP\Events\EventsHandlerAwareTrait;
use ObjectivePHP\Gateway\Entity\EntityInterface;
use ObjectivePHP\Gateway\Event\MetaGateway\OnProxyReadingRequestException;
use ObjectivePHP\Gateway\Event\MetaGateway\OnProxyWritingRequestException;
use ObjectivePHP\Gateway\Exception\MetaGatewayException;
use ObjectivePHP\Gateway\Projection\ProjectionInterface;
use ObjectivePHP\Gateway\ResultSet\Descriptor\ResultSetDescriptorInterface;
use ObjectivePHP\Gateway\ResultSet\ResultSetInterface;

/**
 * Class MetaGateway
 *
 * @package ObjectivePHP\Gateway
 */
class MetaGateway implements MetaGatewayInterface, EventsHandlerAwareInterface
{
    use EventsHandlerAwareTrait;

    const WRITING_MASTER = 1;
    const ALLOWED_FLAGS  = array(self::WRITING_MASTER);

    /**
     * @var array
     */
    protected $gateways = array();

    /**
     * @var
     */
    protected $writeMaster;

    protected $readingPriorities = array();

    protected $writingPriorities = array();

    protected $methodsMapping    = array(
        'fetch'    => self::FETCH,
        'fetchOne' => self::FETCH_ONE,
        'fetchAll' => self::FETCH_ALL,
        'persist'  => self::PERSIST,
        'update'   => self::UPDATE,
        'delete'   => self::DELETE,
        'purge'    => self::PURGE
    );

    protected $didFallback = false;

    /** @var string */
    protected $newId;

    /**
     * @param ResultSetDescriptorInterface $descriptor
     *
     * @return ProjectionInterface
     */
    public function fetch(ResultSetDescriptorInterface $descriptor): ProjectionInterface
    {
        return $this->proxyReadingRequest(__FUNCTION__, $descriptor);
    }

    protected function proxyReadingRequest($method, ...$parameters)
    {
        $this->didFallback = false;
        $lastException = null;

        $gatewaysTried = array();

        foreach ($this->readingPriorities as $id) {
            if ($this->gateways[$id]->can($method, $parameters)) {
                $gatewaysTried[] = $id;
                try {
                    $result = $this->gateways[$id]->$method(...$parameters);
                    if (count($gatewaysTried) > 1) {
                        $this->didFallback = true;
                    }
                    return $result;
                } catch (\Throwable $exception) {
                    $this->trigger(
                        OnProxyReadingRequestException::class,
                        $this,
                        array(
                            'exception' => $exception,
                            'method' => $method,
                            'parameters' => $parameters
                        ),
                        new OnProxyReadingRequestException()
                    );
                    // just let it go
                    $lastException = $exception;
                }
            }
        }

        // no gateway finally made the job, so throw an exception
        $allGateways = $this->getRegisteredGatewaysClasses();
        $actualGateways = implode(', ', array_intersect_key($allGateways, array_flip($gatewaysTried)));

        throw new MetaGatewayException(sprintf(
            'No gateway was able to perform requested reading operation (%s). Requested gateways: %s. Registered gateways: %s.',
            $method,
            $actualGateways,
            implode(', ', $allGateways)
        ), null, $lastException);
    }

    protected function getRegisteredGatewaysClasses()
    {
        $classes = array();
        foreach ($this->getGateways() as $id => $gateway) {
            $classes[$id] = get_class($gateway);
        }

        return $classes;
    }

    /**
     * @return array
     */
    public function getGateways()
    {
        return $this->gateways;
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
        return $this->proxyWritingRequest(__FUNCTION__, ...$entities);
    }

    protected function proxyWritingRequest($method, ...$parameters)
    {
        $lastException = null;

        $result = false;
        foreach ($this->writingPriorities as $priority => $id) {
            if ($this->gateways[$id]->can($method, $parameters)) {
                try {
                    $result = $this->gateways[$id]->$method(...$parameters);
                    if (!$result) {
                        throw new MetaGatewayException(sprintf(
                            'At least one gateway (%s: %s) did not achieve performing requested writing operation (%s).',
                            $id,
                            get_class($this->gateways[$id]),
                            $method
                        ));
                    }
                } catch (\Throwable $exception) {
                    $this->trigger(
                        OnProxyWritingRequestException::class,
                        $this,
                        array(
                            'exception' => $exception,
                            'method' => $method,
                            'parameters' => $parameters
                        ),
                        new OnProxyWritingRequestException()
                    );

                    if ($priority == PHP_INT_MAX) {
                        throw new MetaGatewayException(sprintf(
                            'The writing master gateway (%s) failed performing requested writing operation (%s).',
                            $id,
                            $method
                        ), MetaGatewayException::WRITING_MASTER_FAILURE, $exception);
                    }
                }
            }
        }

        return $result;
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
        return $this->proxyWritingRequest(__FUNCTION__, ...$entities);
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
    public function can($method, ...$parameters): bool
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->can($method, ...$parameters)) {
                return true;
            }
        }

        return false;
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

    public function getReadingPriorities()
    {
        return $this->readingPriorities;
    }

    public function getWritingPriorities()
    {
        return $this->writingPriorities;
    }

    public function didFallback()
    {
        return $this->didFallback;
    }

    /**
     * @return string
     */
    public function getNewId(): string
    {
        return $this->newId;
    }

    /**
     * @param string $newId
     *
     * @return MetaGateway
     */
    public function setNewId(string $newId): MetaGateway
    {
        $this->newId = $newId;

        return $this;
    }
}
