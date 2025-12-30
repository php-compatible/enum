<?php

namespace PhpCompatible\Enum;

/**
 * @method static Value __callStatic(string $name, array $arguments)
 */
class Enum
{
    private static $constantsCache = [];
    private static $valuesCache = [];

    protected static function getConstants(): array
    {
        $class = static::class;
        if (!isset(self::$constantsCache[$class])) {
            $constants = [];
            $ref = new \ReflectionClass($class);
            foreach ($ref->getReflectionConstants() as $constant) {
                if ($constant->getDeclaringClass()->getName() === $class) {
                    $constants[$constant->getName()] = $constant->getValue();
                }
            }
            self::$constantsCache[$class] = $constants;
        }
        return self::$constantsCache[$class];
    }

    protected static function getValues(): array
    {
        $class = static::class;
        if (!isset(self::$valuesCache[$class])) {
            $values = [];
            $autoIncrement = 0;
            foreach (static::getConstants() as $name => $value) {
                if ($value === Value::AUTO) {
                    $value = $autoIncrement++;
                } elseif (is_int($value)) {
                    $autoIncrement = $value + 1;
                }
                $values[$name] = Value::from($name, $value);
            }
            self::$valuesCache[$class] = $values;
        }
        return self::$valuesCache[$class];
    }

    public static function __callStatic(string $name, array $arguments): Value
    {
        $values = static::getValues();
        if (!isset($values[$name])) {
            throw new \InvalidArgumentException("Enum case {$name} does not exist in " . static::class);
        }
        return $values[$name];
    }
}
