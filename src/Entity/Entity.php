<?php

namespace ObjectivePHP\Gateway\Entity;

use ObjectivePHP\Primitives\String\Camel;
use ObjectivePHP\Primitives\String\Snake;

class Entity extends \ArrayObject implements EntityInterface
{
    protected $entityCollection = self::DEFAULT_ENTITY_COLLECTION;

    protected $entityIdentifier = 'id';

    public function getEntityCollection(): string
    {
        return $this->entityCollection;
    }

    public function getEntityIdentifier(): string
    {
        return $this->entityIdentifier;
    }

    public function isNew(): bool
    {
        return !$this[$this->entityIdentifier] ?? true;
    }

    public function offsetGet($index)
    {
        $getter = 'get' . Camel::case($index);
        if(method_exists($this, $getter)) return $this->$getter();
        else return parent::offsetGet($index);
    }

    public function offsetSet($index, $value)
    {
        $setter = 'set' . Camel::case($index);
        if(method_exists($this, $setter)) return $this->$setter($value);
        else return parent::offsetGet($index);
    }

    public function offsetUnset($index)
    {
        $setter = 'set' . Camel::case($index);
        if(method_exists($this, $setter)) return $this->$setter(null);
        else parent::offsetUnset($index);
    }

    public function offsetExists($index)
    {
        if(method_exists($this, 'get' . Camel::case($index))) return true;
        else return parent::offsetExists($index);
    }

    public function getEntityFields(): array
    {
        if($this->getArrayCopy())
        {
            return array_keys($this->getArrayCopy());
        }
        else {
            $fields = [];
            foreach(get_class_methods($this) as $method)
            {
                if (in_array(
                    $method,
                    [
                        'getEntityFields',
                        'getEntityIdentifier',
                        'getEntityCollection',
                        'getArrayCopy',
                        'getFlags',
                        'getIterator',
                        'getIteratorClass'
                    ]
                )) {
                    continue;
                }

                if(strpos($method, 'get') === 0)
                {
                    $fields[] = Snake::case(substr($method, 3));
                }
            }

            return $fields;
        }
    }
}
