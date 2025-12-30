<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use PhpCompatible\Enum\Value;

class SuiteIntEnumTest extends TestCase
{
    public function testHeartsName(): void
    {
        $this->assertSame('Hearts', SuiteIntEnum::Hearts()->name);
    }

    public function testHeartsValue(): void
    {
        $this->assertSame(0, SuiteIntEnum::Hearts()->value);
    }

    public function testDiamondsValue(): void
    {
        $this->assertSame(1, SuiteIntEnum::Diamonds()->value);
    }

    public function testClubsValue(): void
    {
        $this->assertSame(2, SuiteIntEnum::Clubs()->value);
    }

    public function testSpadesValue(): void
    {
        $this->assertSame(3, SuiteIntEnum::Spades()->value);
    }

    public function testJokerName(): void
    {
        $this->assertSame('Joker', SuiteIntEnum::Joker()->name);
    }

    public function testJokerValue(): void
    {
        $this->assertSame(100, SuiteIntEnum::Joker()->value);
    }

    public function testReturnsValueInstance(): void
    {
        $this->assertInstanceOf(Value::class, SuiteIntEnum::Hearts());
    }

    public function testSameInstanceReturned(): void
    {
        $this->assertSame(SuiteIntEnum::Hearts(), SuiteIntEnum::Hearts());
    }

    public function testInvalidCaseThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SuiteIntEnum::Invalid();
    }
}
