<?php
    
    namespace ObjectivePHP\Gateway\ResultSet;


    /**
     * Interface PaginatedResultSetInterface
     *
     * @package ObjectivePHP\Gateway\Entity
     */
    interface PaginatedResultSetInterface extends ResultSetInterface
    {
        /**
         * @return int
         */
        public function getCurrentPage();

        /**
         * @return int
         */
        public function getTotal();

        /**
         * @return int
         */
        public function getPageSize();
    }
