<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use PhpCompatible\Enum\Console\EnumAutoDocCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class EnumAutoDocCommandTest extends TestCase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * @var string
     */
    private $tempDir;

    protected function setUp(): void
    {
        $this->application = new Application();
        $this->application->add(new EnumAutoDocCommand());
        $command = $this->application->find('php-compatible-enum-auto-doc');
        $this->commandTester = new CommandTester($command);

        // Create temp directory for test files
        $this->tempDir = sys_get_temp_dir() . '/enumautodoc_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
        if (is_dir($this->tempDir)) {
            $this->recursiveDelete($this->tempDir);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testFilesWithoutOurNamespaceAreIgnored(): void
    {
        $this->createTestEnumFile('OtherEnum.php', '<?php
use Some\\Other\\Enum;

class OtherEnum extends Enum
{
    protected $value;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('0 file(s) updated', $this->commandTester->getDisplay());
    }

    public function testInvalidPathReturnsFailure(): void
    {
        $this->commandTester->execute([
            'path' => '/nonexistent/path/12345',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Path not found', $this->commandTester->getDisplay());
    }

    public function testDryRunDoesNotModifyFiles(): void
    {
        $originalContent = '<?php
use PhpCompatible\\Enum\\Enum;

class TestEnum extends Enum
{
    protected $Hearts;
    protected $Diamonds;
}
';
        $this->createTestEnumFile('TestEnum.php', $originalContent);

        $this->commandTester->execute([
            'path' => $this->tempDir,
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Dry run mode', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Would update', $this->commandTester->getDisplay());

        // Verify file was not modified
        $actualContent = file_get_contents($this->tempDir . '/TestEnum.php');
        $this->assertEquals($originalContent, $actualContent);
    }

    public function testCamelCaseConversion(): void
    {
        $this->createTestEnumFile('TestEnum.php', '<?php
use PhpCompatible\\Enum\\Enum;

class TestEnum extends Enum
{
    protected $Hearts;
    protected $Diamonds;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
            '--camel-case' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/TestEnum.php');
        $this->assertStringContainsString('@method static Value hearts()', $content);
        $this->assertStringContainsString('@method static Value diamonds()', $content);
    }

    public function testPascalCaseConversion(): void
    {
        $this->createTestEnumFile('TestEnum.php', '<?php
use PhpCompatible\\Enum\\Enum;

class TestEnum extends Enum
{
    protected $hearts;
    protected $diamonds;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
            '--pascal-case' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/TestEnum.php');
        $this->assertStringContainsString('@method static Value Hearts()', $content);
        $this->assertStringContainsString('@method static Value Diamonds()', $content);
    }

    public function testSnakeCaseConversion(): void
    {
        $this->createTestEnumFile('TestEnum.php', '<?php
use PhpCompatible\\Enum\\Enum;

class TestEnum extends Enum
{
    protected $pendingReview;
    protected $inProgress;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
            '--snake-case' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/TestEnum.php');
        $this->assertStringContainsString('@method static Value pending_review()', $content);
        $this->assertStringContainsString('@method static Value in_progress()', $content);
    }

    public function testScreamingSnakeCaseConversion(): void
    {
        $this->createTestEnumFile('TestEnum.php', '<?php
use PhpCompatible\\Enum\\Enum;

class TestEnum extends Enum
{
    protected $pendingReview;
    protected $inProgress;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
            '--screaming-snake-case' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/TestEnum.php');
        $this->assertStringContainsString('@method static Value PENDING_REVIEW()', $content);
        $this->assertStringContainsString('@method static Value IN_PROGRESS()', $content);
    }

    public function testNonEnumFilesAreIgnored(): void
    {
        $this->createTestEnumFile('RegularClass.php', '<?php
class RegularClass
{
    protected $value;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('0 file(s) updated', $this->commandTester->getDisplay());
    }

    public function testEnumWithNoPropertiesIsIgnored(): void
    {
        $this->createTestEnumFile('EmptyEnum.php', '<?php
class EmptyEnum extends Enum
{
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('0 file(s) updated', $this->commandTester->getDisplay());
    }

    public function testExistingDocBlockIsPreserved(): void
    {
        $this->createTestEnumFile('TestEnum.php', '<?php
use PhpCompatible\\Enum\\Enum;

/**
 * My custom description.
 */
class TestEnum extends Enum
{
    protected $Hearts;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/TestEnum.php');
        $this->assertStringContainsString('My custom description', $content);
        $this->assertStringContainsString('@method static Value hearts()', $content);
    }

    public function testRecursiveDirectoryScanning(): void
    {
        // Create subdirectory
        mkdir($this->tempDir . '/subdir', 0777, true);

        $this->createTestEnumFile('TestEnum.php', '<?php
use PhpCompatible\\Enum\\Enum;

class TestEnum extends Enum
{
    protected $value1;
}
');
        $this->createTestEnumFile('subdir/SubEnum.php', '<?php
use PhpCompatible\\Enum\\Enum;

class SubEnum extends Enum
{
    protected $value2;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('2 file(s) updated', $this->commandTester->getDisplay());
    }

    private function createTestEnumFile(string $filename, string $content): void
    {
        $dir = dirname($this->tempDir . '/' . $filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->tempDir . '/' . $filename, $content);
    }
}
