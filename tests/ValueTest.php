<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use PhpCompatible\Enum\Value;

class ValueTest extends TestCase
{
    public function testFromCreatesValueWithName(): void
    {
        $value = Value::from('test');
        $this->assertSame('test', $value->name);
    }

    public function testFromCreatesValueWithIntValue(): void
    {
        $value = Value::from('test', 42);
        $this->assertSame(42, $value->value);
    }

    public function testFromCreatesValueWithStringValue(): void
    {
        $value = Value::from('test', 'hello');
        $this->assertSame('hello', $value->value);
    }

    public function testFromCreatesValueWithNullValue(): void
    {
        $value = Value::from('test', null);
        $this->assertNull($value->value);
    }

    public function testFromThrowsExceptionForInvalidValueType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Enum value must be int, string, or null');
        Value::from('test', 3.14);
    }

    public function testFromThrowsExceptionForArrayValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Value::from('test', []);
    }

    public function testFromThrowsExceptionForObjectValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Value::from('test', new \stdClass());
    }

    public function testGetNameProperty(): void
    {
        $value = Value::from('myName', 123);
        $this->assertSame('myName', $value->name);
    }

    public function testGetValueProperty(): void
    {
        $value = Value::from('test', 456);
        $this->assertSame(456, $value->value);
    }

    public function testGetInvalidPropertyThrowsException(): void
    {
        $value = Value::from('test', 123);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property invalid does not exist');
        $value->invalid;
    }

    public function testIssetNameReturnsTrue(): void
    {
        $value = Value::from('test', 123);
        $this->assertTrue(isset($value->name));
    }

    public function testIssetValueReturnsTrue(): void
    {
        $value = Value::from('test', 123);
        $this->assertTrue(isset($value->value));
    }

    public function testIssetInvalidPropertyReturnsFalse(): void
    {
        $value = Value::from('test', 123);
        $this->assertFalse(isset($value->invalid));
    }
}
