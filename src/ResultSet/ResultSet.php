<?php

namespace ObjectivePHP\Gateway\ResultSet;


/**
 * Class EntitySet
 * @package Pricer\Entity
 */
class ResultSet extends \ArrayObject implements ResultSetInterface
{
    
    /**
     * @return array
     */
    public function toArray()
    {
        return $this->getArrayCopy();
    }

    public function isEmpty()
    {
        return count($this) === 0;
    }

}
