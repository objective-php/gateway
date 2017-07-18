<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 17/07/2017
 * Time: 11:11
 */

namespace ObjectivePHP\Gateway;

interface MetaGatewayInterface extends GatewayInterface
{
    
    /**
     * @param                  $id
     * @param GatewayInterface $gateway
     * @param int              $readingPriority
     * @param int              $writingPriority
     * @param int              $flags
     *
     * @return mixed
     */
    public function registerGateway(string $id, GatewayInterface $gateway, int $readingPriority = 0, int $writingPriority = 0, int $flags = 0);
}
