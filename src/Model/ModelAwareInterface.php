<?php

namespace ObjectivePHP\Gateway\Model;

/**
 * Interface ModelAwareInterface
 *
 * @package ObjectivePHP\Gateway\Model
 */
interface ModelAwareInterface
{
    /**
     * Set a model
     *
     * @param Model $model
     */
    public function setModel(Model $model);

    /**
     * Get a model
     *
     * @return Model
     */
    public function getModel(): Model;
}
