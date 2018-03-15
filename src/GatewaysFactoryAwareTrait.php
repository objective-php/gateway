<?php

namespace ObjectivePHP\Gateway;

/**
 * Trait GatewaysFactoryAwareTrait
 *
 * @package ObjectivePHP\Gateway
 */
trait GatewaysFactoryAwareTrait
{
    /**
     * @var GatewaysFactory
     */
    protected $gatewaysFactory;

    /**
     * Get GatewaysFactory
     *
     * @return GatewaysFactory
     */
    public function getGatewaysFactory(): GatewaysFactory
    {
        return $this->gatewaysFactory;
    }

    /**
     * Set GatewaysFactory
     *
     * @param GatewaysFactory $gatewaysFactory
     *
     * @return $this
     */
    public function setGatewaysFactory(GatewaysFactory $gatewaysFactory)
    {
        $this->gatewaysFactory = $gatewaysFactory;

        return $this;
    }
}
