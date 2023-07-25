<?php

/**
 * Author @poetrasapoetra
 * https://github.com/poetrasapoetra
 */

namespace App\Models\Data;


class Data
{

    /**
     * Make array based of Data object
     * @param bool $removeEmpty should empty value is removed from array. This is good for inserting array to database
     * @return array associative of object field and its value.
     */

    function toArray(bool $removeEmpty = false): array
    {
        $data = (array) $this;
        if ($removeEmpty) {
            foreach ($data as $key => $value) {
                if ($value == null || $value == "") {
                    unset($data[$key]);
                }
            }
        }
        return $data;
    }

    /**
     * Make object based on array associative
     * @param array $data is associative array to put into object
     * @param bool $checkKey check if object has field of array key
     * @return Data or its child
     */
    function fromArray(array $data, bool $checkKey = true)
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value, $checkKey);
        }
        return $this;
    }

    function get(string $key)
    {
        if (sizeof($this->getFields($key)) > 0) {
            return $this->{$key};
        }
        return null;
    }

    /**
     * Set object field value
     * @param string $key is object field will be set
     * @param any $value is value to set
     * @param bool $checkKey is object has the key
     * @return Data or its child
     */
    function set(string $key, $value, bool $checkKey = true)
    {
        if (!$checkKey || sizeof($this->getFields($key)) > 0) {
            $this->{$key} = $value;
        }
        return $this;
    }

    /**
     * Unset object field value
     * @param string $key is object field will be unset
     * @param bool $full should key removed
     * @return Data or its child
     */
    function unset(string $key, bool $full = false)
    {
        if ($full) {
            unset($this->{$key});
        } else {
            $this->{$key} = null;
        }
        return $this;
    }

    /**
     * Serialize object
     * @return string serial of object
     */
    function serialize(): string
    {
        return serialize($this);
    }

    /**
     * Get object field keys
     * @param ?string $key Specific key to search or null to get all fields
     * @return array of keys
     */
    function getFields(?string $key = null): array
    {
        $a = $this->toArray();
        $r = array();
        foreach ($a as $k => $value) {
            if ($key == null || $k == $key) {
                array_push($r, $k);
            }
        }
        return $r;
    }

    /**
     * check if all key of object is not null or empty
     * @param array $exceptionKeys not to check
     * @return bool true if there is no null or empty value of keys, otherwise false
     */
    function validateValue(array $exceptionKeys = []): bool
    {
        $keys = $this->getFields();
        foreach ($keys as $key) {
            if (!in_array($key, $exceptionKeys)) { //check if key not included in $exceptionKeys
                if ($this->{$key} === "" || $this->{$key} === null) {
                    return false;
                }
            }
        }
        return true;
    }

    function clone()
    {
        $clone = new ($this::class)();
        $clone->fromArray($this->toArray(), false);
        return $clone;
    }
}
