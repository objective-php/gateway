<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 18/07/2017
 * Time: 13:42
 */

namespace Tests\ObjectivePHP\Gateway;

use Codeception\Util\Stub;
use ObjectivePHP\Gateway\AbstractGateway;
use ObjectivePHP\Gateway\GatewayInterface;
use PHPUnit\Framework\TestCase;

class AbstractGatewayTest extends TestCase
{
    public function testCanMethodDefaultBehaviour()
    {
        /** @var AbstractGateway $gateway */
        $gateway = Stub::make(AbstractGateway::class);
        $this->assertTrue($gateway->can('fetch', array()));
    
        // check that if allowed methods are restricted, can() is still doing the job
        $gateway->setAllowedMethods(GatewayInterface::FETCH_ONE);
        $this->assertFalse($gateway->can('fetch', array()));
        
        // also check that by default, all non-standard methods are allowed
        $this->assertTrue($gateway->can('non-existent-method', array()));
    }
}
