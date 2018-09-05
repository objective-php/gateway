<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 18/07/2017
 * Time: 13:42
 */

namespace Tests\ObjectivePHP\Gateway;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Codeception\Util\Stub;
use ObjectivePHP\Events\EventsHandler;
use ObjectivePHP\Events\EventsHandlerInterface;
use ObjectivePHP\Gateway\AbstractGateway;
use ObjectivePHP\Gateway\Entity\EntityInterface;
use ObjectivePHP\Gateway\Event\MetaGateway\OnProxyReadingRequestException;
use ObjectivePHP\Gateway\Event\MetaGateway\OnProxyWritingRequestException;
use ObjectivePHP\Gateway\Exception\MetaGatewayException;
use ObjectivePHP\Gateway\GatewayInterface;
use ObjectivePHP\Gateway\MetaGateway;
use ObjectivePHP\Gateway\Projection\ProjectionInterface;
use ObjectivePHP\Gateway\ResultSet\Descriptor\ResultSetDescriptor;
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
        /** @var ResultSetDescriptorInterface $resultSetDescriptor */
        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);
        $projection = Stub::makeEmpty(ProjectionInterface::class);


        /** @var GatewayInterface $gateway */
        $gateway = $this->make(AbstractGateway::class, [
            'fetch' => Expected::once($projection)
        ]);

        $meta->registerGateway('gw', $gateway);
        $this->assertSame($projection, $meta->fetch($resultSetDescriptor));
    }

    public function testFetchAllRouting()
    {
        $meta = new MetaGateway();
        /** @var ResultSetDescriptorInterface $resultSetDescriptor */
        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);
        $resultSet = Stub::makeEmpty(ResultSetInterface::class);

        /** @var GatewayInterface $gateway */
        $gateway = $this->make(AbstractGateway::class, [
            'fetchAll' => Expected::once($resultSet)
        ]);

        $meta->registerGateway('gw', $gateway);
        $this->assertSame($resultSet, $meta->fetchAll($resultSetDescriptor));
    }

    public function testFetchOneRouting()
    {
        $meta = new MetaGateway();
        /** @var ResultSetDescriptorInterface $resultSetDescriptor */
        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);
        $entity = Stub::makeEmpty(EntityInterface::class);

        /** @var GatewayInterface $gateway */
        $gateway = $this->make(AbstractGateway::class, [
            'fetchOne' => Expected::once($entity)
        ]);

        $meta->registerGateway('gw', $gateway);
        $this->assertSame($entity, $meta->fetchOne($resultSetDescriptor));
    }

    public function testPersistRouting()
    {
        $meta = new MetaGateway();
        /** @var EntityInterface $entity */
        $entity = Stub::makeEmpty(EntityInterface::class);

        /** @var GatewayInterface $gateway */
        $gateway = $this->make(AbstractGateway::class, [
            'persist' => Expected::once(true)
        ]);

        $meta->registerGateway('gw', $gateway);

        $meta->persist($entity);
    }

    public function testUpdateRouting()
    {
        $meta = new MetaGateway();

        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);

        /** @var GatewayInterface $gateway */
        $gateway = $this->make(AbstractGateway::class, [
            'update' => Expected::once(true)
        ]);

        $meta->registerGateway('gw', $gateway);

        $meta->update($resultSetDescriptor, array('field' => 'new_value'));
    }

    public function testDeleteRouting()
    {
        $meta = new MetaGateway();

        /** @var EntityInterface $entity */
        $entity = Stub::makeEmpty(EntityInterface::class);

        /** @var GatewayInterface $gateway */
        $gateway = $this->make(AbstractGateway::class, [
            'delete' => Expected::once(true)
        ]);

        $meta->registerGateway('gw', $gateway);

        $meta->delete($entity);
    }

    public function testPurgeRouting()
    {
        $meta = new MetaGateway();

        /** @var ResultSetDescriptorInterface $resultSetDescriptor */
        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);

        /** @var GatewayInterface $gateway */
        $gateway = $this->make(AbstractGateway::class, [
            'purge' => Expected::once(true)
        ]);

        $meta->registerGateway('gw', $gateway);

        $meta->purge($resultSetDescriptor);
    }

    public function testReadingFallback()
    {
        $meta = new MetaGateway();
        /** @var ResultSetDescriptorInterface $resultSetDescriptor */
        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);
        $resultSet = Stub::makeEmpty(ResultSetInterface::class);

        /** @var GatewayInterface $firstGateway */
        $firstGateway = $this->make(AbstractGateway::class, [
            'fetchAll' => Expected::once(function () {
                throw new \Exception('failed!');
            })
        ]);

        /** @var GatewayInterface $secondGateway */
        $secondGateway = $this->make(AbstractGateway::class, [
            'fetchAll' => Expected::once($resultSet)
        ]);

        $meta->registerGateway('first', $firstGateway);
        $meta->registerGateway('second', $secondGateway);
        $this->assertSame($resultSet, $meta->fetchAll($resultSetDescriptor));
    }

    public function testReadingFailure()
    {
        $meta = new MetaGateway();
        /** @var ResultSetDescriptorInterface $resultSetDescriptor */
        $resultSetDescriptor = Stub::makeEmpty(ResultSetDescriptorInterface::class);

        /** @var GatewayInterface $firstGateway */
        $firstGateway = $this->make(AbstractGateway::class, [
            'fetchAll' => Expected::once(function () {
                throw new \Exception('failed!');
            })
        ]);

        $meta->registerGateway('first', $firstGateway);

        $this->expectException(MetaGatewayException::class);
        $meta->fetchAll($resultSetDescriptor);
    }

    public function testReadingFailureTriggerEvent()
    {
        $meta = new MetaGateway();
        $descriptor = new ResultSetDescriptor('test');

        /** @var GatewayInterface $gateway */
        $gateway = $this->make(AbstractGateway::class, [
            'fetchAll' => Expected::once(function () {
                throw new \Exception('failed!');
            })
        ]);

        $meta->registerGateway('gateway', $gateway);

        /** @var EventsHandlerInterface $eventsHandler */
        $eventsHandler = $this->getMockBuilder(EventsHandler::class)->getMock();
        $eventsHandler->expects($this->once())->method('trigger')->with(
            OnProxyReadingRequestException::class,
            $meta,
            array(
                'exception' => new \Exception('failed!'),
                'method' => 'fetchAll',
                'parameters' => array($descriptor)
            ),
            new OnProxyReadingRequestException()
        );

        $meta->setEventsHandler($eventsHandler);

        try {
            $meta->fetchAll($descriptor);
        } catch (\Exception $e) {
        }
    }

    public function testWritingFallbackWithoutWritingMaster()
    {
        $meta = new MetaGateway();
        /** @var EntityInterface $entity */
        $entity = Stub::makeEmpty(EntityInterface::class);

        /** @var GatewayInterface $firstGateway */
        $firstGateway = $this->make(AbstractGateway::class, [
            'persist' => Expected::once(function () {
                throw new \Exception('failed!');
            })
        ]);

        /** @var GatewayInterface $secondGateway */
        $secondGateway = $this->make(AbstractGateway::class, [
            'persist' => Expected::once(true)
        ]);

        $meta->registerGateway('first', $firstGateway);
        $meta->registerGateway('second', $secondGateway);
        $this->assertTrue($meta->persist($entity));
    }

    public function testWritingFallbackWithWrintingMaster()
    {
        $meta = new MetaGateway();
        /** @var EntityInterface $entity */
        $entity = Stub::makeEmpty(EntityInterface::class);

        /** @var GatewayInterface $firstGateway */
        $firstGateway = $this->make(AbstractGateway::class, [
            'persist' => Expected::once(function () {
                throw new \Exception('failed!');
            })
        ]);

        /** @var GatewayInterface $secondGateway */
        $secondGateway = $this->make(AbstractGateway::class, [
            'persist' => Expected::never()
        ]);

        $meta->registerGateway('first', $firstGateway, 0, PHP_INT_MAX, MetaGateway::WRITING_MASTER);
        $meta->registerGateway('second', $secondGateway);

        $this->expectException(MetaGatewayException::class);

        $meta->persist($entity);
    }

    public function testWritingFailureTriggerEvent()
    {
        $meta = new MetaGateway();
        /** @var EntityInterface $entity */
        $entity = Stub::makeEmpty(EntityInterface::class);


        /** @var GatewayInterface $secondGateway */
        $gateway = $this->make(AbstractGateway::class, [
            'persist' => Expected::once(function () {
                throw new \Exception('failed!');
            })
        ]);

        $meta->registerGateway('gateway', $gateway);

        $eventsHandler = $this->getMockBuilder(EventsHandler::class)->getMock();
        $eventsHandler->expects($this->once())->method('trigger')->with(
            OnProxyWritingRequestException::class,
            $meta,
            array(
                'exception' => new \Exception('failed!'),
                'method' => 'persist',
                'parameters' => array($entity)
            ),
            new OnProxyWritingRequestException()
        );

        $meta->setEventsHandler($eventsHandler);

        try {
            $meta->persist($entity);
        } catch (\Exception $e) {
        }
    }

    public function testCanReturnsTrueOnlyIfRegisteredGatewayReturnsTrue()
    {
        $meta = new MetaGateway();

        $this->assertFalse($meta->can('whatever'));

        /** @var GatewayInterface $gateway */
        $gateway = Stub::makeEmpty(AbstractGateway::class, ['can' => Expected::once(true)]);

        $meta->registerGateway('single', $gateway);

        $meta->can('whatever');
    }

    public function testCanSetAndGetANewId()
    {
        $meta = new MetaGateway();
        $id = 'UUID:123:toto';

        $this->assertNull($meta->getNewId());

        $meta->setNewId($id);

        $this->assertEquals($id, $meta->getNewId());
    }
}
