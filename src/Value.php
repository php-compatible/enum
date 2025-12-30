<?php

namespace PhpCompatible\Enum;

class Value
{
    /** @var int tells Enum to auto increment value */
    const AUTO = PHP_INT_MIN;

    private $name;
    private $value;

    protected function __construct(string $name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public static function from(string $name, $value = null): self
    {
        return new static($name, $value);
    }

    public function __get(string $property)
    {
        if ($property === 'name') {
            return $this->name;
        }

        if ($property === 'value') {
            return $this->value;
        }

        throw new \InvalidArgumentException("Property {$property} does not exist");
    }

    public function __isset(string $property): bool
    {
        return $property === 'name' || $property === 'value';
    }
}
