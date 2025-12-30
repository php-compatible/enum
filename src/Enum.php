<?php

namespace PhpCompatible\Enum;

/**
 * Base class for creating PHP 8-style enums compatible with PHP 7.2+.
 *
 * Extend this class and define protected properties to create an enum:
 *
 * ```php
 * class Status extends Enum
 * {
 *     protected $active;      // 0 (auto)
 *     protected $inactive;    // 1 (auto)
 *     protected $archived = 10;
 *     protected $deleted;     // 11 (auto)
 * }
 *
 * // All of these work:
 * echo Status::active()->value;   // 0
 * echo Status::Active()->value;   // 0
 * echo Status::ACTIVE()->value;   // 0
 * ```
 */
class Enum
{
    /**
     * Singleton instances per enum class.
     *
     * @var array<string, static>
     */
    private static $instances = [];

    /**
     * Cached Value instances on this enum instance.
     *
     * @var array<string, Value>
     */
    private $values = [];

    /**
     * Mapping of normalized names to actual property names.
     *
     * @var array<string, string>
     */
    private $nameMap = [];

    /**
     * Whether all values have been loaded (for auto-increment).
     *
     * @var bool
     */
    private $allLoaded = false;

    /**
     * Reverse lookup map from value to property name.
     *
     * @var array<int|string, string>
     */
    private $valueMap = [];

    /**
     * Get singleton instance of the enum class.
     *
     * @return static
     */
    private static function getInstance(): self
    {
        $class = static::class;
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static();
        }
        return self::$instances[$class];
    }

    /**
     * Normalize a name for case-insensitive matching.
     *
     * Converts camelCase, PascalCase, snake_case, SCREAMING_SNAKE_CASE
     * all to the same normalized form (lowercase, no separators).
     *
     * @param string $name
     * @return string
     */
    private static function normalize(string $name): string
    {
        // Remove underscores and convert to lowercase
        return strtolower(str_replace('_', '', $name));
    }

    /**
     * Build the name mapping for case-insensitive lookups.
     *
     * @return void
     */
    private function buildNameMap(): void
    {
        if (!empty($this->nameMap)) {
            return;
        }

        $class = static::class;
        $ref = new \ReflectionClass($this);

        foreach ($ref->getProperties() as $prop) {
            if ($prop->getDeclaringClass()->getName() === $class && !$prop->isStatic()) {
                $name = $prop->getName();
                $normalized = self::normalize($name);
                $this->nameMap[$normalized] = $name;
            }
        }
    }

    /**
     * Find the actual property name from any case variation.
     *
     * @param string $name
     * @return string|null
     */
    private function findPropertyName(string $name): ?string
    {
        $this->buildNameMap();
        $normalized = self::normalize($name);
        return $this->nameMap[$normalized] ?? null;
    }

    /**
     * Load all enum cases with auto-increment support.
     *
     * @return void
     */
    private function loadAll(): void
    {
        if ($this->allLoaded) {
            return;
        }

        $class = static::class;
        $ref = new \ReflectionClass($this);
        $autoIncrement = 0;

        foreach ($ref->getProperties() as $prop) {
            if ($prop->getDeclaringClass()->getName() === $class && !$prop->isStatic()) {
                $name = $prop->getName();
                $prop->setAccessible(true);
                $value = $prop->getValue($this);

                if ($value === null) {
                    $value = $autoIncrement++;
                } elseif (is_int($value)) {
                    $autoIncrement = $value + 1;
                }

                $this->values[$name] = Value::from($name, $value);

                // Build reverse lookup - duplicate values not allowed (PHP 8 behavior)
                if (array_key_exists($value, $this->valueMap)) {
                    throw new \LogicException(
                        "Duplicate value {$value} in enum " . static::class .
                        " (cases: {$this->valueMap[$value]}, {$name})"
                    );
                }
                $this->valueMap[$value] = $name;
            }
        }

        $this->allLoaded = true;
    }

    /**
     * Magic method to access enum cases as static methods.
     *
     * Supports case-insensitive access:
     * - MyEnum::camelCase()
     * - MyEnum::CamelCase()
     * - MyEnum::CAMEL_CASE()
     *
     * @param string $name The enum case name (any case style)
     * @param array $arguments Unused
     * @return Value
     * @throws \InvalidArgumentException If case does not exist
     */
    public static function __callStatic(string $name, array $arguments): Value
    {
        $instance = static::getInstance();
        $propertyName = $instance->findPropertyName($name);

        if ($propertyName === null) {
            throw new \InvalidArgumentException("Enum case {$name} does not exist in " . static::class);
        }

        if (!isset($instance->values[$propertyName])) {
            $ref = new \ReflectionClass($instance);
            $prop = $ref->getProperty($propertyName);
            $prop->setAccessible(true);
            $value = $prop->getValue($instance);

            // If null, need to load all to calculate auto-increment
            if ($value === null) {
                $instance->loadAll();
            } else {
                $instance->values[$propertyName] = Value::from($propertyName, $value);
            }
        }

        return $instance->values[$propertyName];
    }

    /**
     * Get all enum cases.
     *
     * @return Value[]
     */
    public static function cases(): array
    {
        $instance = static::getInstance();
        $instance->loadAll();
        return array_values($instance->values);
    }

    /**
     * Find case name by value with strict type comparison.
     *
     * @param mixed $value
     * @return string|null
     */
    private function findCaseByValue($value): ?string
    {
        foreach ($this->values as $name => $case) {
            if ($case->value === $value) {
                return $name;
            }
        }
        return null;
    }

    /**
     * Get an enum case from its backing value.
     *
     * @param int|string $value The backing value to look up
     * @return Value
     * @throws \ValueError If the value does not match any case (PHP 8+)
     * @throws \InvalidArgumentException If the value does not match any case (PHP 7)
     */
    public static function from($value): Value
    {
        $instance = static::getInstance();
        $instance->loadAll();

        $name = $instance->findCaseByValue($value);
        if ($name === null) {
            $message = "{$value} is not a valid backing value for enum " . static::class;
            // Use ValueError in PHP 8+, InvalidArgumentException in PHP 7
            if (PHP_VERSION_ID >= 80000) {
                throw new \ValueError($message);
            }
            throw new \InvalidArgumentException($message);
        }

        return $instance->values[$name];
    }

    /**
     * Try to get an enum case from its backing value.
     *
     * @param int|string $value The backing value to look up
     * @return Value|null The enum case or null if not found
     */
    public static function tryFrom($value): ?Value
    {
        $instance = static::getInstance();
        $instance->loadAll();

        $name = $instance->findCaseByValue($value);
        if ($name === null) {
            return null;
        }

        return $instance->values[$name];
    }
}
