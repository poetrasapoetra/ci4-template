<?php

namespace App\Models\DataModels;

use Ramsey\Uuid\Uuid;

abstract class DataUuid extends DataModel
{
    public abstract function getUuidFields(): array;

    public function ensureUuid()
    {
        foreach ($this->getUuidFields() as $f) {
            $this->{$f} = $this->getUuidString($this->{$f});
        }
    }

    static function getUuidString($source): string
    {
        if (is_string($source)) {
            if (Uuid::isValid($source)) return $source;
            if (strlen($source) == 16) return Uuid::fromBytes($source)->toString();
        }
        return Uuid::uuid4()->toString();
    }

    function getByteUuid(string $field): string
    {
        if (in_array($field, $this->getUuidFields())) {
            if (Uuid::isValid($this->{$field})) return Uuid::fromString($this->{$field})->getBytes();
        }
        return "";
    }


    public function toArrayUuidBytes(bool $ensure = false): array
    {
        $dup = $this;
        if ($ensure) {
            // clone to make original object unchanged;
            $dup = $this->clone();
            $dup->ensureUuid();
        }
        $data = $dup->toArray();
        foreach ($dup->getUuidFields() as $f) {
            $data[$f] = Uuid::fromString($dup->{$f})->getBytes();
        }
        return $data;
    }

    public static function fromArray(array $data): static
    {
        $instance = parent::fromArray($data);
        $instance->ensureUuid();
        return $instance;
    }
}
