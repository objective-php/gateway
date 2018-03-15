<?php

namespace ObjectivePHP\Gateway;

/**
 * Interface GatewaysFactoryAwareInterface
 *
 * @package ObjectivePHP\Gateway
 */
interface GatewaysFactoryAwareInterface
{
    /**
     * Set a gateways factory
     *
     * @param GatewaysFactory $factory
     */
    public function setGatewaysFactory(GatewaysFactory $factory);

    /**
     * Get a gateways factory
     *
     * @return GatewaysFactory
     */
    public function getGatewaysFactory(): GatewaysFactory;
}
