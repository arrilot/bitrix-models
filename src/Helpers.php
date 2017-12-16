<?php

namespace Arrilot\BitrixModels;

class Helpers
{
    /**
     * Does the $haystack starts with $needle
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
