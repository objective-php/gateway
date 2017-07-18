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
use ObjectivePHP\Gateway\Exception\MetaGatewayException;
use ObjectivePHP\Gateway\GatewayInterface;
use ObjectivePHP\Gateway\MetaGateway;
use ObjectivePHP\Gateway\Projection\ProjectionInterface;
use ObjectivePHP\Gateway\ResultSet\Descriptor\ResultSetDescriptor;
use ObjectivePHP\Gateway\ResultSet\Descriptor\ResultSetDescriptorInterface;
use ObjectivePHP\Gateway\ResultSet\ResultSetInterface;
use PHPUnit\Framework\TestCase;

class MetaGatewayTest extends TestCase
{
    public function testGatewaysRegistration()
    {
        /** @var AbstractGateway $gateway */
        $meta = new MetaGateway();
        
        $firstGateway = $this->createMock(AbstractGateway::class);
        $secondGateway = $this->createMock(AbstractGateway::class);
        
        $meta->registerGateway('first', $firstGateway);
        $meta->registerGateway('second', $firstGateway);
        
        $this->assertEquals(array('first' => $firstGateway, 'second' => $secondGateway), $meta->getGateways());
        $this->assertEquals(array(0 => 'first', -1 => 'second'), $meta->getReadingPriorities());
    
        $thirdGateway = $this->createMock(AbstractGateway::class);
        $meta->registerGateway('third', $thirdGateway, 10);
        $this->assertEquals(array(10 => 'third', 0 => 'first', -1 => 'second'), $meta->getReadingPriorities());
        
        $this->assertEquals(array(0 => 'first', -1 => 'second', -2 => 'third'), $meta->getWritingPriorities());
        
        // writing master registration
        $fourthGateway = $this->createMock(AbstractGateway::class);
        $meta->registerGateway('fourth', $fourthGateway, 0, 0 /* will be ignored due to the WRITING_MASTER flag) */, MetaGateway::WRITING_MASTER);
        $this->assertEquals(array(PHP_INT_MAX => 'fourth', 0 => 'first', -1 => 'second', -2 => 'third'), $meta->getWritingPriorities());
        
        // registering two writing master is forbidden
        $fifthGateway = $this->createMock(AbstractGateway::class);
        
        $this->expectException(MetaGatewayException::class);
        $meta->registerGateway('fourth', $fifthGateway, 0, 0, MetaGateway::WRITING_MASTER);
    }
    
    public function testSettingWritingPriorityToPHP_INT_MAXIsForbiddenWhenNotRegisteringWritingMaster()
    {
        $meta = new MetaGateway();
        
        // registering two writing master is forbidden
        $gateway = $this->createMock(AbstractGateway::class);
    
        $this->expectException(MetaGatewayException::class);
        $meta->registerGateway('first', $gateway, 0, PHP_INT_MAX);
    }
    
    public function testSettingUnknownFlagsOnRegistrationIsForbidden()
    {
        $meta = new MetaGateway();
        
        // registering two writing master is forbidden
        $gateway = $this->createMock(AbstractGateway::class);
    
        $this->expectException(MetaGatewayException::class);
        
        $meta->registerGateway('first', $gateway, 0, 0, PHP_INT_MAX);
    }
    
    public function testFetchRouting()
    {
        $meta = new MetaGateway();
        $resultSetDescriptor = $this->createMock(ResultSetDescriptorInterface::class);
        $projection = $this->createMock(ProjectionInterface::class);
        
        $gateway = Stub::make(AbstractGateway::class, ['fetch' => $projection]);
        
        $meta->registerGateway('gw', $gateway);
        $this->assertSame($projection, $meta->fetch($resultSetDescriptor));
    }
    
    public function testFetchAllRouting()
    {
        $meta = new MetaGateway();
        $resultSetDescriptor = $this->createMock(ResultSetDescriptorInterface::class);
        $resultSet = $this->createMock(ResultSetInterface::class);
        
        $gateway = Stub::make(AbstractGateway::class, ['fetchAll' => $resultSet]);
        
        $meta->registerGateway('gw', $gateway);
        $this->assertSame($resultSet, $meta->fetchAll($resultSetDescriptor));
    }
    
    public function testFetchOneRouting()
    {
        $meta = new MetaGateway();
        $resultSetDescriptor = $this->createMock(ResultSetDescriptorInterface::class);
        $entity = $this->createMock(EntityInterface::class);
        
        $gateway = Stub::make(AbstractGateway::class, ['fetchOne' => $entity]);
        
        $meta->registerGateway('gw', $gateway);
        $this->assertSame($entity, $meta->fetchOne($resultSetDescriptor));
    }
}
