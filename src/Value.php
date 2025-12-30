<?php

namespace PhpCompatible\Enum;

/**
 * Represents an enum case value with name and value properties.
 *
 * @property-read string $name The name of the enum case
 * @property-read int|string|null $value The value of the enum case
 */
class Value
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int|string|null
     */
    private $value;

    /**
     * @param string $name The enum case name
     * @param int|string|null $value The enum case value
     * @throws \InvalidArgumentException If value is not int, string, or null
     */
    protected function __construct(string $name, $value = null)
    {
        if ($value !== null && !is_int($value) && !is_string($value)) {
            throw new \InvalidArgumentException(
                "Enum value must be int, string, or null. Got: " . gettype($value)
            );
        }

        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Create a new Value instance.
     *
     * @param string $name The enum case name
     * @param int|string|null $value The enum case value
     * @return self
     * @throws \InvalidArgumentException If value is not int, string, or null
     */
    public static function from(string $name, $value = null): self
    {
        return new static($name, $value);
    }

    /**
     * Magic getter for name and value properties.
     *
     * @param string $property
     * @return mixed
     * @throws \InvalidArgumentException
     */
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

    /**
     * Magic isset for name and value properties.
     *
     * @param string $property
     * @return bool
     */
    public function __isset(string $property): bool
    {
        return $property === 'name' || $property === 'value';
    }
}
