<?php

namespace Arrilot\BitrixModels\Models;

use ArrayAccess;
use ArrayIterator;
use Arrilot\BitrixModels\Models\Traits\HidesAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;

abstract class ArrayableModel implements ArrayAccess, Arrayable, Jsonable, IteratorAggregate
{
    use HidesAttributes;

    /**
     * ID of the model.
     *
     * @var null|int
     */
    public $id;

    /**
     * Array of model fields.
     *
     * @var null|array
     */
    public $fields;

    /**
     * Array of accessors to append during array transformation.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * Array related models indexed by the relation names.
     *
     * @var array
     */
    public $related = [];

    /**
     * Set method for ArrayIterator.
     *
     * @param $offset
     * @param $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->fields[] = $value;
        } else {
            $this->fields[$offset] = $value;
        }
    }

    /**
     * Exists method for ArrayIterator.
     *
     * @param $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->getAccessor($offset) ? true : isset($this->fields[$offset]);
    }

    /**
     * Unset method for ArrayIterator.
     *
     * @param $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->fields[$offset]);
    }

    /**
     * Get method for ArrayIterator.
     *
     * @param $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $fieldValue = isset($this->fields[$offset]) ? $this->fields[$offset] : null;
        $accessor = $this->getAccessor($offset);

        return $accessor ? $this->$accessor($fieldValue) : $fieldValue;
    }

    /**
     * Get an iterator for fields.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->fields);
    }

    /**
     * Get accessor method name if it exists.
     *
     * @param string $field
     *
     * @return string|false
     */
    private function getAccessor($field)
    {
        $method = 'get'.camel_case($field).'Attribute';

        return method_exists($this, $method) ? $method : false;
    }

    /**
     * Cast model to array.
     *
     * @return array
     */
    public function toArray()
    {
        $array = $this->fields;

        foreach ($this->appends as $accessor) {
            if (isset($this[$accessor])) {
                $array[$accessor] = $this[$accessor];
            }
        }

        foreach ($this->related as $key => $value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $array[$key] = $value->toArray();
            } elseif (is_null($value) || $value === false) {
                $array[$key] = $value;
            }
        }

        if (count($this->getVisible()) > 0) {
            $array = array_intersect_key($array, array_flip($this->getVisible()));
        }

        if (count($this->getHidden()) > 0) {
            $array = array_diff_key($array, array_flip($this->getHidden()));
        }

        return $array;
    }

    /**
     * Convert model to json.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}
