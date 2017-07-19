<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 18/07/2017
 * Time: 13:42
 */

namespace Tests\ObjectivePHP\Gateway;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use ObjectivePHP\Gateway\AbstractGateway;
use ObjectivePHP\Gateway\Entity\EntityInterface;
use ObjectivePHP\Gateway\Exception\MetaGatewayException;
use ObjectivePHP\Gateway\MetaGateway;
use ObjectivePHP\Gateway\Projection\ProjectionInterface;
use ObjectivePHP\Gateway\ResultSet\Descriptor\ResultSetDescriptorInterface;
use ObjectivePHP\Gateway\ResultSet\ResultSetInterface;

class MetaGatewayTest extends Unit
{
    public function testGatewaysRegistration()
    {
        /** @var AbstractGateway $gateway */
        $meta = new MetaGateway();

        $firstGateway = Stub::make(AbstractGateway::class);
        $secondGateway = Stub::make(AbstractGateway::class);

        $meta->registerGateway('first', $firstGateway);
        $meta->registerGateway('second', $firstGateway);

        $this->assertEquals(array('first' => $firstGateway, 'second' => $secondGateway), $meta->getGateways());
        $this->assertEquals(array(0 => 'first', -1 => 'second'), $meta->getReadingPriorities());

        $thirdGateway = Stub::make(AbstractGateway::class);
        $meta->registerGateway('third', $thirdGateway, 10);
        $this->assertEquals(array(10 => 'third', 0 => 'first', -1 => 'second'), $meta->getReadingPriorities());

        $this->assertEquals(array(0 => 'first', -1 => 'second', -2 => 'third'), $meta->getWritingPriorities());

        // writing master registration
        $fourthGateway = Stub::make(AbstractGateway::class);
        $meta->registerGateway('fourth', $fourthGateway, 0, 0 /* will be ignored due to the WRITING_MASTER flag) */, MetaGateway::WRITING_MASTER);
        $this->assertEquals(array(PHP_INT_MAX => 'fourth', 0 => 'first', -1 => 'second', -2 => 'third'), $meta->getWritingPriorities());

        // registering two writing master is forbidden
        $fifthGateway = Stub::make(AbstractGateway::class);

        $this->expectException(MetaGatewayException::class);
        $meta->registerGateway('fourth', $fifthGateway, 0, 0, MetaGateway::WRITING_MASTER);
    }

    public function testSettingWritingPriorityToPHP_INT_MAXIsForbiddenWhenNotRegisteringWritingMaster()
    {
        $meta = new MetaGateway();

        // registering two writing master is forbidden
        $gateway = Stub::make(AbstractGateway::class);

        $this->expectException(MetaGatewayException::class);
        $meta->registerGateway('first', $gateway, 0, PHP_INT_MAX);
    }

    public function testSettingUnknownFlagsOnRegistrationIsForbidden()
    {
        $meta = new MetaGateway();

        // registering two writing master is forbidden
        $gateway = Stub::make(AbstractGateway::class);

        $this->expectException(MetaGatewayException::class);

        $meta->registerGateway('first', $gateway, 0, 0, PHP_INT_MAX);
    }

    public function testFetchRouting()
    {
        $meta = new MetaGateway();
        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);
        $projection = Stub::makeEmpty(ProjectionInterface::class);

        $gateway = Stub::make(AbstractGateway::class, array('fetch' => Stub::once(function () use ($projection) {
            return $projection;
        })), $this);

        $meta->registerGateway('gw', $gateway);
        $this->assertSame($projection, $meta->fetch($resultSetDescriptor));
    }

    public function testFetchAllRouting()
    {
        $meta = new MetaGateway();
        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);
        $resultSet = Stub::makeEmpty(ResultSetInterface::class);

        $gateway = Stub::make(AbstractGateway::class, array('fetchAll' => Stub::once(function () use ($resultSet) {
            return $resultSet;
        })), $this);

        $meta->registerGateway('gw', $gateway);
        $this->assertSame($resultSet, $meta->fetchAll($resultSetDescriptor));
    }

    public function testFetchOneRouting()
    {
        $meta = new MetaGateway();
        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);
        $entity = Stub::makeEmpty(EntityInterface::class);

        $gateway = Stub::make(AbstractGateway::class, array('fetchOne' => Stub::once(function () use ($entity) {
            return $entity;
        })), $this);

        $meta->registerGateway('gw', $gateway);
        $this->assertSame($entity, $meta->fetchOne($resultSetDescriptor));
    }

    public function testPersistRouting()
    {
        $meta = new MetaGateway();
        $entity = Stub::makeEmpty(EntityInterface::class);

        $gateway = Stub::make(AbstractGateway::class, array('persist' => Stub::once(function () {
            return true;
        })), $this);

        $meta->registerGateway('gw', $gateway);

        $meta->persist($entity);
    }

    public function testUpdateRouting()
    {
        $meta = new MetaGateway();

        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);

        $gateway = Stub::make(AbstractGateway::class, array('update' => Stub::exactly(1, function () {
            return true;
        })), $this);

        $meta->registerGateway('gw', $gateway);

        $meta->update($resultSetDescriptor, array('field' => 'new_value'));
    }

    public function testDeleteRouting()
    {
        $meta = new MetaGateway();
        $entity = Stub::makeEmpty(EntityInterface::class);

        $gateway = Stub::make(AbstractGateway::class, array('delete' => Stub::once(function () {
            return true;
        })), $this);

        $meta->registerGateway('gw', $gateway);

        $meta->delete($entity);
    }

    public function testPurgeRouting()
    {
        $meta = new MetaGateway();

        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);

        $gateway = Stub::make(AbstractGateway::class, array('purge' => Stub::exactly(1, function () {
            return true;
        })), $this);

        $meta->registerGateway('gw', $gateway);

        $meta->purge($resultSetDescriptor);
    }

    public function testReadingFallback()
    {
        $meta = new MetaGateway();
        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);
        $resultSet = Stub::makeEmpty(ResultSetInterface::class);

        $firstGateway = Stub::make(AbstractGateway::class, array('fetchAll' => Stub::once(function () use ($resultSet) {
            throw new \Exception('failed!');
        })), $this);
        $secondGateway = Stub::make(AbstractGateway::class, array('fetchAll' => Stub::once(function () use ($resultSet) {
            return $resultSet;
        })), $this);

        $meta->registerGateway('first', $firstGateway);
        $meta->registerGateway('second', $secondGateway);
        $this->assertSame($resultSet, $meta->fetchAll($resultSetDescriptor));
    }

    public function testReadingFailure()
    {
        $meta = new MetaGateway();
        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);
        $resultSet = Stub::makeEmpty(ResultSetInterface::class);

        $firstGateway = Stub::make(AbstractGateway::class, array('fetchAll' => Stub::once(function () use ($resultSet) {
            throw new \Exception('failed!');
        })), $this);

        $meta->registerGateway('first', $firstGateway);

        $this->expectException(MetaGatewayException::class);
        $meta->fetchAll($resultSetDescriptor);
    }

    public function testWritingFallbackWithoutWritingMaster()
    {
        $meta = new MetaGateway();
        $entity = Stub::makeEmpty(EntityInterface::class);

        $firstGateway = Stub::make(AbstractGateway::class, array('persist' => Stub::once(function () {
            throw new \Exception('failed!');
        })), $this);
        $secondGateway = Stub::make(AbstractGateway::class, array('persist' => Stub::once(function () {
            return true;
        })), $this);

        $meta->registerGateway('first', $firstGateway);
        $meta->registerGateway('second', $secondGateway);
        $this->assertTrue($meta->persist($entity));
    }

    public function testWritingFallbackWithWrintingMaster()
    {
        $meta = new MetaGateway();
        $entity = Stub::makeEmpty(EntityInterface::class);

        $firstGateway = Stub::make(AbstractGateway::class, array('persist' => Stub::once(function () {
            throw new \Exception('failed!');
        })), $this);
        $secondGateway = Stub::make(AbstractGateway::class, array('persist' => Stub::never()), $this);

        $meta->registerGateway('first', $firstGateway, 0, PHP_INT_MAX, MetaGateway::WRITING_MASTER);
        $meta->registerGateway('second', $secondGateway);

        $this->expectException(MetaGatewayException::class);

        $meta->persist($entity);
    }

    public function testCanReturnsTrueOnlyIfRegisteredGatewayReturnsTrue()
    {
        $meta = new MetaGateway();

        $this->assertFalse($meta->can('whatever'));

        $gateway = Stub::makeEmpty(AbstractGateway::class, array('can' => Stub::once(function ($method) {
            return true;
        })), $this);

        $meta->registerGateway('single', $gateway);

        $meta->can('whatever');
    }
}
