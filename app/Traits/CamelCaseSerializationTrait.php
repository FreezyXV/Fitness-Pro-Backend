<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait CamelCaseSerializationTrait
{
    /**
     * Convert the model's attributes to an array using camelCase keys.
     *
     * @return array
     */
    public function toArray()
    {
        // Get the original array with all attributes and appends
        $array = parent::toArray();

        $camelCaseArray = [];
        foreach ($array as $key => $value) {
            $camelCaseArray[Str::camel($key)] = $value;
        }

        return $camelCaseArray;
    }
}






