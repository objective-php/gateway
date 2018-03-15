<?php

namespace ObjectivePHP\Gateway;

use ObjectivePHP\Events\EventsHandlerAwareInterface;
use ObjectivePHP\Events\EventsHandlerAwareTrait;
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
    const ALLOWED_FLAGS  = [self::WRITING_MASTER];

    /**
     * @var GatewayInterface[]
     */
    protected $gateways = [];

    /**
     * @var array
     */
    protected $readingPriorities = [];

    /**
     * @var array
     */
    protected $writingPriorities = [];

    /**
     * @var array
     */
    protected $methodsMapping    = [
        'fetch'    => self::FETCH,
        'fetchOne' => self::FETCH_ONE,
        'fetchAll' => self::FETCH_ALL,
        'persist'  => self::PERSIST,
        'update'   => self::UPDATE,
        'delete'   => self::DELETE,
        'purge'    => self::PURGE
    ];

    /**
     * @var bool
     */
    protected $didFallback = false;

    /**
     * {@inheritdoc}
     */
    public function fetch(ResultSetDescriptorInterface $descriptor): ProjectionInterface
    {
        return $this->proxyReadingRequest(__FUNCTION__, $descriptor);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll(ResultSetDescriptorInterface $descriptor): ResultSetInterface
    {
        return $this->proxyReadingRequest(__FUNCTION__, $descriptor);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne($key)
    {
        return $this->proxyReadingRequest(__FUNCTION__, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(...$entities)
    {
        $this->proxyWritingRequest(__FUNCTION__, ...$entities);
    }

    /**
     * {@inheritdoc}
     */
    public function update(ResultSetDescriptorInterface $descriptor, $data)
    {
        $this->proxyWritingRequest(__FUNCTION__, $descriptor, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(...$entities)
    {
        $this->proxyWritingRequest(__FUNCTION__, ...$entities);
    }

    /**
     * {@inheritdoc}
     */
    public function purge(ResultSetDescriptorInterface $descriptor)
    {
        $this->proxyWritingRequest(__FUNCTION__, $descriptor);
    }

    /**
     * {@inheritdoc}
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
     * Get Gateways
     *
     * @return GatewayInterface[]
     */
    public function getGateways(): array
    {
        return $this->gateways;
    }

    /**
     * Register a gateway
     *
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
            throw new MetaGatewayException(
                'PHP_INT writing priority is reserved to the WRITING_MASTER gateway. You may have forgotten the ' .
                MetaGateway::class . '::WRITING_MASTER flag.'
            );
        }

        if ($flags & self::WRITING_MASTER && isset($this->writingPriorities[PHP_INT_MAX])) {
            throw new MetaGatewayException('Cannot register two gateways as WRITING_MASTER');
        }

        $this->updateReadingPriorities($id, $readingPriority, $flags);
        $this->updateWritingPriorities($id, $writingPriority, $flags);

        return $this;
    }

    /**
     * Get ReadingPriorities
     *
     * @return array
     */
    public function getReadingPriorities(): array
    {
        return $this->readingPriorities;
    }

    /**
     * Get WritingPriorities
     *
     * @return array
     */
    public function getWritingPriorities(): array
    {
        return $this->writingPriorities;
    }

    /**
     * Get DidFallback
     *
     * @return bool
     */
    public function didFallback(): bool
    {
        return $this->didFallback;
    }

    /**
     * @param string $method
     * @param array  ...$parameters
     *
     * @return mixed
     *
     * @throws MetaGatewayException
     */
    protected function proxyReadingRequest(string $method, ...$parameters)
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
            'No gateway was able to perform requested ' .
            'reading operation (%s). Requested gateways: %s. Registered gateways: %s.',
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
                            'At least one gateway (%s: %s) did not achieve performing requested writing operation ' .
                            '(%s).',
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
}
