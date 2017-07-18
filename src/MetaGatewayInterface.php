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
     * @param GatewayInterface $gateway
     * @return mixed
     */
    public function registerGateway(GatewayInterface $gateway);

}
