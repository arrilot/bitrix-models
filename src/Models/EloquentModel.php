<?php

namespace Arrilot\BitrixModels\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentModel extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'ID';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'UF_CREATED_AT';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'UF_UPDATED_AT';

    /**
     * @var array
     */
    public $multipleHighloadBlockFields = [];

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (in_array($key, $this->multipleHighloadBlockFields)) {
            return unserialize($this->attributes[$key]);
        }

        return parent::getAttribute($key);
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->multipleHighloadBlockFields)) {
            $this->attributes[$key] = serialize($value);

            return $this;
        }

        parent::setAttribute($key, $value);

        return $this;
    }
}
