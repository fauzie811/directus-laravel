<?php

namespace App\Directus;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Concerns;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DirectusModel implements Arrayable, ArrayAccess
{
    use Concerns\HasAttributes,
        Concerns\HidesAttributes;

    protected $collectionName;

    public static function all()
    {
        return collect(app('directus')->getItems(
            Auth::token(),
            (new static())->getCollectionName()
        ))->map(function ($item) {
            return static::fromArray($item);
        });
    }

    public static function find($id)
    {
        try {
            return static::fromArray(app('directus')->getItem(
                Auth::token(),
                (new static())->getCollectionName(),
                $id
            ));
        } catch (\Exception $e) {
            throw new ModelNotFoundException;
        }
    }

    public static function fromArray(array $attributes)
    {
        $user = new static();
        $user->attributes = $attributes;

        return $user;
    }

    public function getCollectionName()
    {
        return $this->collectionName ?? Str::snake(Str::pluralStudly(class_basename($this)));
    }

    public function getIncrementing()
    {
        return false;
    }

    public function usesTimestamps()
    {
        return false;
    }

    public function toArray()
    {
        return $this->attributesToArray();
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     *
     * @throws \Illuminate\Database\Eloquent\JsonEncodingException
     */
    public function toJson($options = 0)
    {
        $json = json_encode($this->jsonSerialize(), $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw JsonEncodingException::forModel($this, json_last_error_msg());
        }

        return $json;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return ! is_null($this->getAttribute($offset));
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset], $this->relations[$offset]);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }
}
