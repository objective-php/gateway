<?php

namespace ObjectivePHP\Gateway\Entity;


interface EntityInterface extends \ArrayAccess
{
    /**
     * @return array
     */
    public function toArray();
}
