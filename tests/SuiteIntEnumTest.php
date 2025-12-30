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

    public function testCasesReturnsAllValues(): void
    {
        $cases = SuiteIntEnum::cases();
        $this->assertCount(5, $cases);
    }

    public function testCasesReturnsValueInstances(): void
    {
        $cases = SuiteIntEnum::cases();
        foreach ($cases as $case) {
            $this->assertInstanceOf(Value::class, $case);
        }
    }

    public function testCasesContainsAllEnumValues(): void
    {
        $cases = SuiteIntEnum::cases();
        $names = array_map(function ($case) {
            return $case->name;
        }, $cases);

        $this->assertContains('Hearts', $names);
        $this->assertContains('Diamonds', $names);
        $this->assertContains('Clubs', $names);
        $this->assertContains('Spades', $names);
        $this->assertContains('Joker', $names);
    }

    public function testCaseInsensitivePascalCase(): void
    {
        $this->assertSame(0, SuiteIntEnum::Hearts()->value);
    }

    public function testCaseInsensitiveLowerCase(): void
    {
        $this->assertSame(0, SuiteIntEnum::hearts()->value);
    }

    public function testCaseInsensitiveUpperCase(): void
    {
        $this->assertSame(0, SuiteIntEnum::HEARTS()->value);
    }

    public function testCaseInsensitiveSnakeCase(): void
    {
        $this->assertSame(0, SuiteIntEnum::HEAR_TS()->value);
    }

    public function testCaseInsensitiveSameInstance(): void
    {
        $this->assertSame(SuiteIntEnum::Hearts(), SuiteIntEnum::hearts());
        $this->assertSame(SuiteIntEnum::Hearts(), SuiteIntEnum::HEARTS());
    }

    public function testValueNamePreservesOriginalCase(): void
    {
        $this->assertSame('Hearts', SuiteIntEnum::hearts()->name);
        $this->assertSame('Hearts', SuiteIntEnum::HEARTS()->name);
    }
}
