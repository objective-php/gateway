<?php

namespace ObjectivePHP\Gateway;

/**
 * Interface MetaGatewayInterface
 *
 * @package ObjectivePHP\Gateway
 */
interface MetaGatewayInterface extends GatewayInterface
{
    /**
     * Register a Gateway
     *
     * @param string           $id
     * @param GatewayInterface $gateway
     * @param int              $readingPriority
     * @param int              $writingPriority
     * @param int              $flags
     *
     * @return mixed
     */
    public function registerGateway(
        string $id,
        GatewayInterface $gateway,
        int $readingPriority = 0,
        int $writingPriority = 0,
        int $flags = 0
    );
}
