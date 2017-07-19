<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 18/07/2017
 * Time: 10:03
 */

namespace ObjectivePHP\Gateway\Entity;

interface EntityInterface extends \ArrayAccess
{
    public function getEntityCollection() : string;

    public function getEntityIdentifier() : string;

    public function isNew() : bool;
}
