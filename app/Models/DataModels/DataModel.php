<?php

namespace App\Models\DataModels;

use ReflectionClass;

class DataModel
{
    /**
     * Check if object has property
     * @param string $prop the property to check
     */
    public function hasProperty(string $prop): bool
    {
        return property_exists(static::class, $prop);
    }

    /**
     * Set object property. This methid will check if class has `$key` property
     * @param string $key the property to set
     * @param mixed $value the value 
     */
    public function set(string $key, mixed $value)
    {
        if ($this->hasProperty($key)) {
            $this->{$key} = $value;
        }
        return $this;
    }

    public function setAll(array $data)
    {
        foreach ($data as $k => $v) {
            $this->set($k, $v);
        }
        return $this;
    }

    /**
     * Get array representation of object
     */
    public function toArray(): array
    {
        return (array) $this;
    }

    /**
     * Create object from assoc array
     * @param array $data associative array
     */
    public static function fromArray(array $data): static
    {
        $object = new (static::class)();
        foreach ($data as $k => $v) {
            $object->set($k, $v);
        }
        return $object;
    }

    /**
     * Clone object
     */
    public function clone(): static
    {
        return $this::fromArray($this->toArray());
    }

    /**
     * Get class constant
     * @param ?string $prefix
     * @param ?bool $asList return list array if `true`, otherwise assoc array will returned
     */
    public static function getConstant(string $prefix = '', bool $asList = true): array
    {
        $const = [];
        $reflect = new ReflectionClass(static::class);
        $bucket = $reflect->getConstants();
        foreach ($bucket as $key => $value) {
            if (substr($key, 0, strlen($prefix)) == $prefix) {
                if ($asList) $const[] = $value;
                else $const[$key] = $value;
            }
        }
        return $const;
    }

    /**
     * Get class properties that visible to the scope
     */
    public static function getFields(): array
    {
        return array_keys(get_class_vars(static::class));
    }

    /**
     * Get table columns. Please override this function in sub class
     */
    public static function getTableFields(): array
    {
        return static::getFields();
    }

    /**
     * Get data to insert to database
     */
    public function getInsertData(): array
    {
        $fields = $this::getTableFields();
        $data = $this->toArray();
        $returnData = [];
        foreach ($fields as $k) {
            $returnData[$k] = $data[$k];
        }
        return $returnData;
    }

    /**
     * Generate enum schema from string constants
     * @param ?bool $allowNull wether enums allow null item.
     */
    static function generateEnumSchemaFromStringConstant(string $prefix, bool $allowNull = false): array
    {
        $const = static::getConstant($prefix);

        $oneOf = [
            ['type' => 'string', 'enum' => $const],
            ['type' => 'integer', 'enum' => range(0, sizeof($const) - 1)]
        ];
        if ($allowNull) {
            $oneOf[] = ['type' => 'null'];
        }
        return ['oneOf' => $oneOf];
    }
}
