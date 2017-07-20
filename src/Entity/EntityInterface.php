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
    const DEFAULT_ENTITY_COLLECTION = 'NONE';

    public function getEntityCollection() : string;

    public function getEntityIdentifier() : string;

    public function isNew() : bool;

    public function getEntityFields() : array;
}
