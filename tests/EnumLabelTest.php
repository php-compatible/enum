<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use PhpCompatible\Enum\Enum;
use PhpCompatible\Enum\EnumLabel;
use PhpCompatible\Enum\Value;

/**
 * Tests the different types of labels
 *
 * @method static Value A_VALUE()
 * @method static Value a_value()
 * @method static Value AValue()
 * @method static Value ABigValue()
 * @method static Value ABCValue()
 * @method static Value aValue()
 * @method static Value Hearts()
 */
class LabelTestEnum extends Enum
{
    protected $A_VALUE = 1;
    protected $a_value = 2;
    protected $AValue = 3;
    protected $ABigValue = 4;
    protected $ABCValue = 5;
    protected $aValue = 6;
    protected $Hearts = 7;
}

class EnumLabelTest extends TestCase
{
    public function testUpperSnakeCase(): void
    {
        $label = EnumLabel::from(LabelTestEnum::A_VALUE());
        $this->assertSame('A Value', (string) $label);
    }

    public function testLowerSnakeCase(): void
    {
        $label = EnumLabel::from(LabelTestEnum::a_value());
        $this->assertSame('A Value', (string) $label);
    }

    public function testPascalCase(): void
    {
        $label = EnumLabel::from(LabelTestEnum::AValue());
        $this->assertSame('A Value', (string) $label);
    }

    public function testPascalCaseMultipleWords(): void
    {
        $label = EnumLabel::from(LabelTestEnum::ABigValue());
        $this->assertSame('A Big Value', (string) $label);
    }

    public function testConsecutiveUppercase(): void
    {
        $label = EnumLabel::from(LabelTestEnum::ABCValue());
        $this->assertSame('ABC Value', (string) $label);
    }

    public function testCamelCase(): void
    {
        $label = EnumLabel::from(LabelTestEnum::aValue());
        $this->assertSame('A Value', (string) $label);
    }

    public function testSimpleName(): void
    {
        $label = EnumLabel::from(LabelTestEnum::Hearts());
        $this->assertSame('Hearts', (string) $label);
    }

    public function testToStringMethod(): void
    {
        $label = EnumLabel::from(LabelTestEnum::Hearts());
        $this->assertSame('Hearts', $label->toString());
    }

    public function testAutoStringConversion(): void
    {
        $label = EnumLabel::from(LabelTestEnum::Hearts());
        $this->assertSame('Label: Hearts', 'Label: ' . $label);
    }

    public function testEchoOutput(): void
    {
        $this->expectOutputString('Spades');
        echo EnumLabel::from(SuiteIntEnum::Spades());
    }

    public function testEchoOutputPascalCase(): void
    {
        $this->expectOutputString('A Big Value');
        echo EnumLabel::from(LabelTestEnum::ABigValue());
    }

    public function testSnakeCaseValueName(): void
    {
        // Explicitly test with a Value that has underscore in name
        $value = Value::from('SCREAMING_SNAKE', 1);
        $label = EnumLabel::from($value);
        $this->assertSame('Screaming Snake', $label->toString());
    }

    public function testLowerSnakeCaseValueName(): void
    {
        $value = Value::from('lower_snake_case', 2);
        $label = EnumLabel::from($value);
        $this->assertSame('Lower Snake Case', $label->toString());
    }
}
