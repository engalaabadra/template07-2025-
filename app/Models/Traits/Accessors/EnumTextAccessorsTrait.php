<?php

namespace App\Models\Traits\Accessors;

use Illuminate\Support\Str;
/**
 * Trait EnumTextAccessorsTrait
 *
 * getAppends() -> adds support for automatically appending `_text` accessors for enum-casted attributes.
 * __get() -> get for any attribute contain (_text) like accessors 
 * For example, if a model has an enum cast for `status`, this trait will expose `status_text` via getAppends() & get value this attr , via accessors __get()
 * that returns the readable version of the enum (e.g. `Active`, `Inactive`, etc.).
 */
trait EnumTextAccessorsTrait
{
    /**
     * Override getArrayableAppends to add *_text accessors for enum casts.
     *
     * @return array
     */
    public function getAppends(): array
    {
        $appends = parent::getAppends();

        foreach ($this->getCasts() as $key => $cast) {
            $value = $this->{$key};

            // Check for Enum or HasTextRepresentation
            if (
                (enum_exists($cast) && $value instanceof \BackedEnum) ||
                ($value instanceof \App\Interfaces\HasTextRepresentation)
            ) {
                $textKey = $key . '_text';

                if (!in_array($textKey, $appends)) {
                    $appends[] = $textKey;
                }
            }
        }

        return $appends;
    }

    /**
     * MakeAccessor for *_text fields dynamically using Enum.
     */
    public function __get($key)
    {
        if (str_ends_with($key, '_text')) {
            $baseKey = str_replace('_text', '', $key);
            $value = $this->{$baseKey};

            if (
                $value instanceof \BackedEnum ||
                $value instanceof \App\Interfaces\HasTextRepresentation
            ) {
                return $value->getTrans();
            }
        }

        return parent::__get($key);
    }

}
