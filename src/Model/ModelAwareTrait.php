<?php

namespace ObjectivePHP\Gateway\Model;

/**
 * Trait ModelAwareTrait
 *
 * @package ObjectivePHP\Gateway\Model
 */
trait ModelAwareTrait
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * Get Model
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Set Model
     *
     * @param Model $model
     *
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }
}
