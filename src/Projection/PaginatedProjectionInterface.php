<?php

    namespace ObjectivePHP\Gateway\Projection;

    use ObjectivePHP\Gateway\Projection\ProjectionInterface;

    /**
     * Interface PaginatedResultSetInterface
     *
     * @package ObjectivePHP\Gateway\Entity
     */
    interface PaginatedProjectionInterface extends ProjectionInterface
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
