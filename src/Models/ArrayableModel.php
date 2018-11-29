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
     * Array of original model fields.
     *
     * @var null|array
     */
    protected $original;

    /**
     * Array of accessors to append during array transformation.
     *
     * @var array
     */
    protected $appends = [];
    
    /**
     * Array of language fields with auto accessors.
     *
     * @var array
     */
    protected $languageAccessors = [];

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
        return $this->getAccessor($offset) || $this->getAccessorForLanguageField($offset)
            ? true : isset($this->fields[$offset]);
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
        if ($accessor) {
            return $this->$accessor($fieldValue);
        }

        $accessorForLanguageField = $this->getAccessorForLanguageField($offset);
        if ($accessorForLanguageField) {
            return $this->$accessorForLanguageField($offset);
        }

        return $fieldValue;
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
     * Get accessor for language field method name if it exists.
     *
     * @param string $field
     *
     * @return string|false
     */
    private function getAccessorForLanguageField($field)
    {
        $method = 'getValueFromLanguageField';

        return in_array($field, $this->languageAccessors) && method_exists($this, $method) ? $method : false;
    }

    /**
     * Add value to append.
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function append($attributes)
    {
        $this->appends = array_unique(
            array_merge($this->appends, is_string($attributes) ? func_get_args() : $attributes)
        );

        return $this;
    }

    /**
     * Setter for appends.
     *
     * @param  array  $appends
     * @return $this
     */
    public function setAppends(array $appends)
    {
        $this->appends = $appends;

        return $this;
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
