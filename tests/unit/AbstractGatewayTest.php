<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 18/07/2017
 * Time: 13:42
 */

namespace Tests\ObjectivePHP\Gateway;

use ObjectivePHP\Gateway\AbstractGateway;
use ObjectivePHP\Gateway\Exception\GatewayException;
use ObjectivePHP\Gateway\GatewayInterface;
use ObjectivePHP\Gateway\Projection\ProjectionInterface;
use ObjectivePHP\Gateway\ResultSet\Descriptor\ResultSetDescriptorInterface;
use ObjectivePHP\Gateway\ResultSet\ResultSetInterface;
use PHPUnit\Framework\TestCase;
use Zend\Hydrator\ClassMethods;

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

    public function testAllowedMethodsAccessors()
    {
        $gateway = new SomeGateway();

        // check default value
        $this->assertEquals(GatewayInterface::ALL, $gateway->getAllowedMethods());

        // test setter
        $gateway->setAllowedMethods(GatewayInterface::WRITE);
        $this->assertEquals(GatewayInterface::WRITE, $gateway->getAllowedMethods());
    }

    public function testDefaultHydratorIsArraySerializableWhenDefaultEntityClassIsObjectiveEntity()
    {
        $gateway = new SomeGateway();
        $this->assertInstanceOf(ClassMethods::class, $gateway->getHydrator());
    }


    public function testDefaultHydratorIsClassMEthodsWhenDefaultEntityClassIsDifferentThanBaseEntity()
    {
        $gateway = new OtherGateway();

        $this->assertInstanceOf(ClassMethods::class, $gateway->getHydrator());
    }

    public function testBaseEntityFactory()
    {
        $gateway = new SomeGateway();
        $entity = $gateway->fetchOne('whatever');

        $this->assertInstanceOf(OtherEntity::class, $entity);

        $this->assertObjectHasAttribute('field', $entity);
    }

    public function testCustomEntityFactory()
    {
        $gateway = new OtherGateway();
        $entity = $gateway->fetchOne('whatever');

        $this->assertInstanceOf(OtherEntity::class, $entity);

        $this->assertEquals('value', $entity->getField());
    }

    public function testUnknownEntityClassMakesEntityFactoryFail()
    {
        $gateway = new InvalidGateway();
        $gateway->setEntityClass('NotExistingClass');
        $this->expectException(GatewayException::class);
        $this->expectExceptionCode(GatewayException::ENTITY_NOT_FOUND);

        $gateway->fetchOne('whatever');
    }
}

class SomeGateway extends AbstractGateway
{
    protected $entityClass = OtherEntity::class;

    public function existingMethod()
    {
    }

    public function fetch(ResultSetDescriptorInterface $resultSetDescriptor): ProjectionInterface
    {
        // TODO: Implement fetch() method.
    }

    public function fetchAll(ResultSetDescriptorInterface $descriptor): ResultSetInterface
    {
    }

    public function fetchOne($key)
    {
        return $this->entityFactory(array('id' => 1, 'field' => 'value'));
    }

    public function persist(...$entities)
    {
        // TODO: Implement persist() method.
    }

    public function update(ResultSetDescriptorInterface $descriptor, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete(...$entities)
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

class OtherGateway extends AbstractGateway
{
    protected $entityClass = OtherEntity::class;

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

    public function fetchOne($key)
    {
        return $this->entityFactory(array('id' => 1, 'field' => 'value'));
    }

    public function persist(...$entities)
    {
        // TODO: Implement persist() method.
    }

    public function update(ResultSetDescriptorInterface $descriptor, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete(...$entities)
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

class OtherEntity
{
    protected $field;

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param mixed $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }
}

class InvalidGateway extends AbstractGateway
{
    protected $entityClass = InvalidEntity::class;

    public function fetch(ResultSetDescriptorInterface $resultSetDescriptor): ProjectionInterface
    {
        // TODO: Implement fetch() method.
    }

    public function fetchAll(ResultSetDescriptorInterface $descriptor): ResultSetInterface
    {
        // TODO: Implement fetchAll() method.
    }

    public function fetchOne($key)
    {
        return $this->entityFactory(array('id' => 1, 'field' => 'value'));
    }

    public function persist(...$entities)
    {
        // TODO: Implement persist() method.
    }

    public function update(ResultSetDescriptorInterface $descriptor, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete(...$entities)
    {
        // TODO: Implement delete() method.
    }

    public function purge(ResultSetDescriptorInterface $descriptor)
    {
        // TODO: Implement purge() method.
    }
}

class InvalidEntity
{
}
