<?php

    namespace ObjectivePHP\Gateway\Entity;


    /**
     * Class AbstractEntity
     *
     * @package ObjectivePHP\Gateway\Entity
     */
    abstract class AbstractEntity implements EntityInterface
    {
        /**
         * @var array DB => property mapping
         */
        protected $mapping = array();

        /**
         * @var array property => DB mapping (auto-generated from $mapping)
         */
        private $mappingTo = array();

        /**
         * AbstractEntity constructor.
         *
         * @param null $data
         */
        public function __construct($data = null)
        {
            if ($data)
            {
                $this->hydrate($data);
            }
        }
    
        /**
         * @param array|\ArrayObject|\Traversable $data
         *
         * @return $this
         * @throws EntityException
         */
        public function hydrate($data)
        {
            if ($data instanceof \ArrayObject)
            {
                $data = $data->getArrayCopy();
            }
            else
            {
                if ($data instanceof \Iterator)
                {
                    $data = iterator_to_array($data);
                }
            }

            if (!is_array($data))
            {
                throw new EntityException(get_class($this) . ' entities must be hydrated using either an array, an ArrayObject or an Iterator');
            }

            foreach ($data as $key => $value)
            {
                $methodName = 'set' . $this->toCamelCase($this->mapFrom($key));

                if (method_exists($this, $methodName))
                {
                    $this->$methodName($value);
                }
            }

            return $this;
        }

        /**
         * @param bool $mapped
         *
         * @return array
         */
        public function toArray($mapped = false)
        {
            $data = array();

            $methods = get_class_methods(get_class($this));

            foreach ($methods as $method)
            {
                if (substr($method, 0, 3) == 'get')
                {
                    $property = $this->toSnakeCase(lcfirst(substr($method, 3)));

                    if ($mapped) $property = $this->mapTo($property);

                    $value = $this->$method();

                    if ($value instanceof \DateTime) {
                        $value = $value->format('c');
                    }

                    $data[$property] = $value;
                }
            }

            return $data;
        }

        /**
         * @param $offset
         *
         * @return string
         */
        public function toCamelCase($offset)
        {
            $parts = explode('_', $offset);
            array_walk($parts, function (&$offset)
            {
                $offset = ucfirst($offset);
            });

            return implode('', $parts);
        }

        /**
         * Map DB field name to property name
         *
         * @param $field
         *
         * @return mixed
         */
        protected function mapFrom($field)
        {
            return isset($this->mapping[$field]) ? $this->mapping[$field] : $field;
        }

        /**
         * @param string $offset
         * @param string $splitter
         *
         * @return string
         */

        public function toSnakeCase($offset, $splitter = '_')
        {
            $offset = preg_replace('/(?!^)[[:upper:]][[:lower:]]/', '$0', preg_replace('/(?!^)[[:upper:]]+/', $splitter . '$0', $offset));

            return strtolower($offset);
        }

        /**
         * Map property name to DB field name
         *
         * @param $property
         *
         * @return mixed
         */
        protected function mapTo($property)
        {
            if (!$this->mappingTo)
            {
                $this->mappingTo = array_flip($this->mapping);
            }

            return isset($this->mappingTo[$property]) ? $this->mappingTo[$property] : $property;
        }

        /**
         * @param mixed $offset
         *
         * @return mixed
         */
        public function offsetExists($offset)
        {
            $method = 'get' . $this->toCamelCase($offset);

            return method_exists($this, $method);
        }

        /**
         * @param mixed $offset
         *
         * @return mixed
         */
        public function offsetGet($offset)
        {
            $method = 'get' . $this->toCamelCase($this->mapFrom($offset));
            if (method_exists($this, $method))
            {
                return $this->$method();
            }

            return null;
        }
    
        /**
         * @param mixed $offset
         * @param mixed $value
         *
         * @return mixed
         * @throws EntityException
         */
        public function offsetSet($offset, $value)
        {
            $method = 'set' . $this->toCamelCase($this->mapFrom($offset));
            if (method_exists($this, $method))
            {
                return $this->$method($value);
            }

            throw new EntityException(sprintf('Undefined property %s', $offset));
        }

        /**
         * @param mixed $offset
         *
         * @return mixed
         */
        public function offsetUnset($offset)
        {
            $property = lcfirst($this->toCamelCase($offset));

            $this->$property = null;
            
            return null;

        }
    
    }
