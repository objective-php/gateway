<?php

namespace ObjectivePHP\Gateway\Model;

/**
 * Class Model
 *
 * @package ObjectivePHP\Gateway\Model
 */
class Model
{
    /**
     * @var array
     */
    protected $properties = [];

    /**
     * Register a property
     *
     * @param string $entityClass
     * @param string $property
     * @param mixed  $type
     *
     * @return $this
     */
    public function registerProperty(string $entityClass, string $property, $type)
    {
        $this->properties[$entityClass][$property] = $type;

        return $this;
    }

    /**
     * Get entity properties
     *
     * @param string      $entityClass
     * @param string|null $typeClass
     *
     * @return array
     */
    public function getPropertiesFor(string $entityClass, $typeClass = null): array
    {
        $properties = array_filter($this->properties, function ($property, $key) use ($entityClass, $typeClass) {
            if ($key === $entityClass) {
                if (!is_null($typeClass) && !current($property) instanceof $typeClass) {
                    return false;
                }

                return true;
            }

            return false;
        }, ARRAY_FILTER_USE_BOTH);

        return isset($properties[$entityClass]) ? $properties[$entityClass] : [];
    }
}
