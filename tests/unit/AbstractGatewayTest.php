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
use ObjectivePHP\Gateway\Entity\EntityInterface;
use ObjectivePHP\Gateway\GatewayInterface;
use ObjectivePHP\Gateway\Projection\ProjectionInterface;
use ObjectivePHP\Gateway\ResultSet\Descriptor\ResultSetDescriptorInterface;
use ObjectivePHP\Gateway\ResultSet\ResultSetInterface;
use PHPUnit\Framework\TestCase;

class AbstractGatewayTest extends TestCase
{
    public function testCanMethodDefaultBehaviour()
    {
        /** @var AbstractGateway $gateway */
        $gateway = new SomeGateway();
        $this->assertTrue($gateway->can('fetch'));

        // check that if allowed methods are restricted, can() is still doing the job
        $gateway->setAllowedMethods(GatewayInterface::FETCH_ONE);
        $this->assertFalse($gateway->can('fetch'));

        // also check that by default, only existing methods are allowed
        $this->assertFalse($gateway->can('non-existent-method'));
        $this->assertTrue($gateway->can('existingMethod'));

        // also, if a specific method "canRequestedMethod" is present on the gateway, the call to "can" should be
        // forwarded to the previous
        $this->assertTrue($gateway->can('fetchSomething'));

        // but not if the actual method does not exist!
        $this->assertFalse($gateway->can('fetchSomethingNonExisting'));
    }
}

class SomeGateway extends AbstractGateway
{
    public function existingMethod()
    {
    }

    public function fetch(ResultSetDescriptorInterface $resultSetDescriptor): ProjectionInterface
    {
        // TODO: Implement fetch() method.
    }

    public function fetchAll(ResultSetDescriptorInterface $descriptor): ResultSetInterface
    {
        // TODO: Implement fetchAll() method.
    }

    public function fetchOne($key): EntityInterface
    {
        // TODO: Implement fetchOne() method.
    }

    public function persist(EntityInterface ...$entities): bool
    {
        // TODO: Implement persist() method.
    }

    public function update(ResultSetDescriptorInterface $descriptor, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete(EntityInterface ...$entities)
    {
        // TODO: Implement delete() method.
    }

    public function purge(ResultSetDescriptorInterface $descriptor)
    {
        // TODO: Implement purge() method.
    }

    public function fetchSomething()
    {
    }

    public function canFetchSomething()
    {
        return true;
    }

    public function canFetchSomethingNonExisting()
    {
        return true;
    }
}
