<?php

    namespace ObjectivePHP\Gateway\ResultSet;

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
    }
