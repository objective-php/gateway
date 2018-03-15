<?php

namespace ObjectivePHP\Gateway;

use ObjectivePHP\Gateway\Exception\GatewayNotFoundException;
use ObjectivePHP\Gateway\Exception\GatewaysFactoryException;
use ObjectivePHP\ServicesFactory\Exception\ServiceNotFoundException;
use ObjectivePHP\ServicesFactory\ServicesFactory;

/**
 * Class GatewayFactory
 *
 * @package ObjectivePHP\Gateway
 */
class GatewaysFactory extends ServicesFactory
{
    /**
     * {@inheritdoc}
     *
     * @throws GatewayNotFoundException When gateways was not found
     * @throws GatewaysFactoryException If the reference doesn't produce a GatewayInterface instance
     */
    public function get($service, $params = [])
    {
        try {
            $gateway = parent::get($service, $params);
        } catch (ServiceNotFoundException $e) {
            throw new GatewayNotFoundException(
                sprintf(
                    'Gateway reference "%s" matches no registered gateway in this factory or its delegate containers',
                    $service
                ),
                $e->getCode(),
                $e
            );
        }

        if (!$gateway instanceof GatewayInterface) {
            throw new GatewaysFactoryException(
                sprintf(
                    'Gateway factory must produce %s instance, "%s" reference produce %s instance',
                    GatewayInterface::class,
                    $service,
                    get_class($gateway)
                )
            );
        }

        if ($gateway instanceof GatewaysFactoryAwareInterface) {
            $gateway->setGatewaysFactory($this);
        }

        return $gateway;
    }
}
