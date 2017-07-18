<?php

    namespace ObjectivePHP\Gateway\ResultSet;

use ObjectivePHP\Gateway\Entity\EntityInterface;

/**
     * Interface ResultSetInterface
     *
     * @package ObjectivePHP\Gateway\Entity
     */
    interface ResultSetInterface extends \Traversable, \Countable
    {
        /**
         * @return array
         */
        public function toArray();

        /**
         * @return bool
         */
        public function isEmpty();

        /**
         * @param EntityInterface[] ...$entities
         * @return mixed
         */
        public function addEntities(EntityInterface ...$entities) : ResultSetInterface;
    }
