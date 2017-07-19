<?php
/**
 * Created by PhpStorm.
 * User: gde
 * Date: 19/07/2017
 * Time: 09:47
 */

namespace ObjectivePHP\Gateway\Entity;

class Entity extends \ArrayObject implements EntityInterface
{
    protected $collection = '';

    protected $key = 'id';

    public function getCollection(): string
    {
        return $this->collection;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function isNew(): bool
    {
        return !$this[$this->key] ?? true;
    }
}
