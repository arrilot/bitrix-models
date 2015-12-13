<?php

use Arrilot\BitrixModels\Collection;

if (! function_exists('collect')) {
    /**
     * Create a collection from the given value.
     *
     * @param  mixed  $value
     * @return \Arrilot\BitrixModels\Collection
     */
    function collect2($value = null)
    {
        return new Collection($value);
    }
}