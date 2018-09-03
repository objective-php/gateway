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

    /** @var array */
    protected $gateways = [];

    protected $writeMaster;

    protected $readingPriorities = [];

    protected $writingPriorities = [];

    protected $methodsMapping    = [
        'fetch'    => self::FETCH,
        'fetchOne' => self::FETCH_ONE,
        'fetchAll' => self::FETCH_ALL,
        'persist'  => self::PERSIST,
        'update'   => self::UPDATE,
        'create'   => self::CREATE,
        'delete'   => self::DELETE,
        'purge'    => self::PURGE
    ];

    /** @var bool */
    protected $didFallback = false;

    /** @var string */
    protected $newId;

    /**
     * @param ResultSetDescriptorInterface $descriptor
     *
     * @return ProjectionInterface
     *
     * @throws MetaGatewayException
     */
    public function fetch(ResultSetDescriptorInterface $descriptor): ProjectionInterface
    {
        return $this->proxyReadingRequest(__FUNCTION__, $descriptor);
    }

    /**
     * @param string $method
     * @param mixed ...$parameters
     *
     * @return mixed
     * @throws MetaGatewayException
     */
    protected function proxyReadingRequest(string $method, ...$parameters)
    {
        $this->didFallback = false;
        $lastException = null;

        $gatewaysTried = [];

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

    /**
     * @return array
     */
    protected function getRegisteredGatewaysClasses(): array
    {
        $classes = [];
        foreach ($this->getGateways() as $id => $gateway) {
            $classes[$id] = \get_class($gateway);
        }

        return $classes;
    }

    /**
     * @return array
     */
    public function getGateways(): array
    {
        return $this->gateways;
    }

    /**
     * @param ResultSetDescriptorInterface $descriptor
     *
     * @return ResultSetInterface
     *
     * @throws MetaGatewayException
     */
    public function fetchAll(ResultSetDescriptorInterface $descriptor): ResultSetInterface
    {
        return $this->proxyReadingRequest(__FUNCTION__, $descriptor);
    }

    /**
     * @param $key
     *
     * @return EntityInterface
     *
     * @throws MetaGatewayException
     */
    public function fetchOne($key): EntityInterface
    {
        return $this->proxyReadingRequest(__FUNCTION__, $key);
    }

    /**
     * @param EntityInterface ...$entities
     *
     * @return bool
     * @throws MetaGatewayException
     */
    public function persist(EntityInterface ...$entities): bool
    {
        return $this->proxyWritingRequest(__FUNCTION__, [], ...$entities);
    }

    /**
     * @param EntityInterface $entity
     *
     * @return bool
     * @throws MetaGatewayException
     */
    public function create(EntityInterface $entity): bool
    {
        return $this->proxyWritingRequest(__FUNCTION__, ['id' => $this->getNewId()], $entity);
    }

    /**
     * @param ResultSetDescriptorInterface $descriptor
     * @param mixed                        $data
     *
     * @return bool|mixed
     * @throws MetaGatewayException
     */
    public function update(ResultSetDescriptorInterface $descriptor, $data)
    {
        return $this->proxyWritingRequest(__FUNCTION__, [], $descriptor, $data);
    }

    /**
     * @param EntityInterface ...$entities
     *
     * @return bool
     * @throws MetaGatewayException
     */
    public function delete(EntityInterface ...$entities): bool
    {
        return $this->proxyWritingRequest(__FUNCTION__, [], ...$entities);
    }

    /**
     * @param ResultSetDescriptorInterface $descriptor
     *
     * @return bool
     * @throws MetaGatewayException
     */
    public function purge(ResultSetDescriptorInterface $descriptor): bool
    {
        return $this->proxyWritingRequest(__FUNCTION__, [], $descriptor);
    }

    /**
     * @param string $method
     * @param array $options
     * @param mixed ...$parameters
     *
     * @return bool
     * @throws MetaGatewayException
     */
    protected function proxyWritingRequest(string $method, array $options = [], ...$parameters): bool
    {
        $lastException = null;
        $result = false;

        foreach ($this->writingPriorities as $priority => $id) {
            /** @var AbstractGateway $gateway */
            $gateway = $this->gateways[$id];
            $gateway->setOptions($options);
            if ($gateway->can($method, $parameters)) {
                try {
                    $result = $gateway->$method(...$parameters);
                    if (!$result) {
                        throw new MetaGatewayException(sprintf(
                            'At least one gateway (%s: %s) did not achieve performing requested writing operation (%s).',
                            $id,
                            \get_class($gateway),
                            $method
                        ));
                    }
                } catch (\Throwable $exception) {
                    $this->trigger(
                        OnProxyWritingRequestException::class,
                        $this,
                        array(
                            'exception'     => $exception,
                            'method'        => $method,
                            'parameters'    => $parameters
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
     * @param string $method
     * @param array $parameters
     *
     * @return bool
     */
    public function can(string $method, ...$parameters): bool
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
     *
     * @throws MetaGatewayException
     */
    public function registerGateway(string $id, GatewayInterface $gateway, int $readingPriority = 0, int $writingPriority = 0, int $flags = 0): self
    {
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

        if ($writingPriority === PHP_INT_MAX && !$flags & self::WRITING_MASTER) {
            throw new MetaGatewayException('PHP_INT writing priority is reserved to the WRITING_MASTER gateway. You may have forgotten the ' . MetaGateway::class . '::WRITING_MASTER flag.');
        }

        if ($flags & self::WRITING_MASTER && isset($this->writingPriorities[PHP_INT_MAX])) {
            throw new MetaGatewayException('Cannot register two gateways as WRITING_MASTER');
        }

        $this->updateReadingPriorities($id, $readingPriority, $flags);
        $this->updateWritingPriorities($id, $writingPriority, $flags);

        return $this;
    }

    /**
     * @param $id
     * @param $priority
     * @param $flags
     */
    protected function updateReadingPriorities($id, $priority, $flags)
    {
        $readingPriorities = [];
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

    /**
     * @param $id
     * @param $priority
     * @param $flags
     */
    protected function updateWritingPriorities($id, $priority, $flags)
    {
        $writingPriorities = [];
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
    public function getReadingPriorities(): array
    {
        return $this->readingPriorities;
    }

    /**
     * @return array
     */
    public function getWritingPriorities(): array
    {
        return $this->writingPriorities;
    }

    /**
     * @return bool
     */
    public function didFallback(): bool
    {
        return $this->didFallback;
    }

    /**
     * @return string|null
     */
    public function getNewId()
    {
        return $this->newId;
    }

    /**
     * @param string $newId
     *
     * @return $this
     */
    public function setNewId(string $newId): self
    {
        $this->newId = $newId;

        return $this;
    }
}
