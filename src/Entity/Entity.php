<?php

namespace ObjectivePHP\Gateway\Entity;

use ObjectivePHP\Primitives\String\Camel;

class Entity extends \ArrayObject implements EntityInterface
{
    protected $entityCollection = 'NONE';

    protected $entityIdentifier = 'id';

    public function getEntityCollection(): string
    {
        return $this->entityCollection;
    }

    public function getEntityIdentifier(): string
    {
        return $this[$this->entityIdentifier];
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
}
