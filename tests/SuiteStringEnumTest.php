<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use PhpCompatible\Enum\Value;

class SuiteStringEnumTest extends TestCase
{
    public function testHeartsName(): void
    {
        $this->assertSame('Hearts', SuiteStringEnum::Hearts()->name);
    }

    public function testHeartsValue(): void
    {
        $this->assertSame('Hearts', SuiteStringEnum::Hearts()->value);
    }

    public function testDiamondsName(): void
    {
        $this->assertSame('Diamonds', SuiteStringEnum::Diamonds()->name);
    }

    public function testDiamondsValue(): void
    {
        $this->assertSame('Diamonds', SuiteStringEnum::Diamonds()->value);
    }

    public function testClubsValue(): void
    {
        $this->assertSame('Clubs', SuiteStringEnum::Clubs()->value);
    }

    public function testSpadesValue(): void
    {
        $this->assertSame('Spades', SuiteStringEnum::Spades()->value);
    }

    public function testReturnsValueInstance(): void
    {
        $this->assertInstanceOf(Value::class, SuiteStringEnum::Hearts());
    }

    public function testSameInstanceReturned(): void
    {
        $this->assertSame(SuiteStringEnum::Hearts(), SuiteStringEnum::Hearts());
    }

    public function testInvalidCaseThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SuiteStringEnum::Invalid();
    }

    public function testFromWithValidValue(): void
    {
        $case = SuiteStringEnum::from('Hearts');
        $this->assertSame('Hearts', $case->name);
        $this->assertSame('Hearts', $case->value);
    }

    public function testFromWithInvalidValueThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SuiteStringEnum::from('Invalid');
    }

    public function testFromReturnsSameInstance(): void
    {
        $this->assertSame(SuiteStringEnum::Hearts(), SuiteStringEnum::from('Hearts'));
    }

    public function testTryFromWithValidValue(): void
    {
        $case = SuiteStringEnum::tryFrom('Hearts');
        $this->assertNotNull($case);
        $this->assertSame('Hearts', $case->name);
    }

    public function testTryFromWithInvalidValueReturnsNull(): void
    {
        $case = SuiteStringEnum::tryFrom('Invalid');
        $this->assertNull($case);
    }

    public function testFromIsTypeSensitive(): void
    {
        // String 'Hearts' should match, integer should not
        $case = SuiteStringEnum::tryFrom('Hearts');
        $this->assertNotNull($case);

        $case = SuiteStringEnum::tryFrom(0);
        $this->assertNull($case);
    }
}
