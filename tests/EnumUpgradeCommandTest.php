<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use PhpCompatible\Enum\Console\EnumUpgradeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class EnumUpgradeCommandTest extends TestCase
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
        $this->application->add(new EnumUpgradeCommand());
        $command = $this->application->find('php-compatible-enum-upgrade-to-php8');
        $this->commandTester = new CommandTester($command);

        // Create temp directory for test files
        $this->tempDir = sys_get_temp_dir() . '/enumupgrade_test_' . uniqid();
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
namespace App\Enums;

use PhpCompatible\Enum\Enum;

class Status extends Enum
{
    protected $draft;
    protected $published = 10;
}
';
        $this->createTestFile('Status.php', $originalContent);

        $this->commandTester->execute([
            'path' => $this->tempDir,
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Dry run mode', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Would convert enum', $this->commandTester->getDisplay());

        // Verify file was not modified
        $actualContent = file_get_contents($this->tempDir . '/Status.php');
        $this->assertEquals($originalContent, $actualContent);
    }

    public function testConvertsEnumClassToPhp8Enum(): void
    {
        $this->createTestFile('Status.php', '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum;

class Status extends Enum
{
    protected $draft;
    protected $pending;
    protected $published = 10;
    protected $archived;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/Status.php');

        // Check enum declaration
        $this->assertStringContainsString('enum Status: int', $content);
        $this->assertStringNotContainsString('class Status extends Enum', $content);

        // Check cases
        $this->assertStringContainsString('case draft = 0;', $content);
        $this->assertStringContainsString('case pending = 1;', $content);
        $this->assertStringContainsString('case published = 10;', $content);
        $this->assertStringContainsString('case archived = 11;', $content);

        // Check use statements removed
        $this->assertStringNotContainsString('use PhpCompatible\Enum\Enum;', $content);
    }

    public function testConvertsStringBackedEnum(): void
    {
        $this->createTestFile('Color.php', '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum;

class Color extends Enum
{
    protected $red = \'red\';
    protected $green = \'green\';
    protected $blue = \'blue\';
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/Color.php');

        // Check enum declaration with string backing
        $this->assertStringContainsString('enum Color: string', $content);

        // Check cases
        $this->assertStringContainsString("case red = 'red';", $content);
        $this->assertStringContainsString("case green = 'green';", $content);
        $this->assertStringContainsString("case blue = 'blue';", $content);
    }

    public function testRemovesMethodAnnotations(): void
    {
        $this->createTestFile('Status.php', '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum;
use PhpCompatible\Enum\Value;

/**
 * @method static Value draft()
 * @method static Value published()
 */
class Status extends Enum
{
    protected $draft;
    protected $published = 10;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/Status.php');

        // Check @method annotations are removed
        $this->assertStringNotContainsString('@method static Value', $content);
    }

    public function testSwapsEnumLabelForPhp8EnumLabel(): void
    {
        $this->createTestFile('Status.php', '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum;
use PhpCompatible\Enum\EnumLabel;

class Status extends Enum
{
    protected $draft;
}
');
        $this->createTestFile('StatusService.php', '<?php
namespace App\Services;

use App\Enums\Status;
use PhpCompatible\Enum\EnumLabel;

class StatusService
{
    public function getLabel()
    {
        return EnumLabel::from(Status::draft());
    }
}
');

        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/StatusService.php');

        // Check EnumLabel replaced with Php8EnumLabel
        $this->assertStringContainsString('use PhpCompatible\Enum\Php8EnumLabel;', $content);
        $this->assertStringContainsString('Php8EnumLabel::fromEnum(Status::draft)', $content);
        // Check the old use statement is gone
        $this->assertStringNotContainsString('use PhpCompatible\Enum\EnumLabel;', $content);
    }

    public function testRemovesParenthesesFromEnumAccess(): void
    {
        $this->createTestFile('Status.php', '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum;

class Status extends Enum
{
    protected $draft;
    protected $published;
}
');
        $this->createTestFile('StatusChecker.php', '<?php
namespace App\Services;

use App\Enums\Status;

class StatusChecker
{
    public function isDraft($status)
    {
        return $status === Status::draft();
    }

    public function isPublished($status)
    {
        return $status === Status::published();
    }
}
');

        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/StatusChecker.php');

        // Check parentheses removed
        $this->assertStringContainsString('Status::draft', $content);
        $this->assertStringContainsString('Status::published', $content);
        $this->assertStringNotContainsString('Status::draft()', $content);
        $this->assertStringNotContainsString('Status::published()', $content);
    }

    public function testFilesWithoutOurNamespaceAreIgnored(): void
    {
        $originalContent = '<?php
use Some\Other\Enum;

class OtherEnum extends Enum
{
    protected $value;
}
';
        $this->createTestFile('OtherEnum.php', $originalContent);

        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Enum definitions converted: 0', $this->commandTester->getDisplay());

        // Verify file was not modified
        $actualContent = file_get_contents($this->tempDir . '/OtherEnum.php');
        $this->assertEquals($originalContent, $actualContent);
    }

    public function testHandlesUnitEnumWithoutValues(): void
    {
        $this->createTestFile('Direction.php', '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum;

class Direction extends Enum
{
    protected $north;
    protected $south;
    protected $east;
    protected $west;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/Direction.php');

        // When no explicit values are set, creates a unit enum (no backing type)
        $this->assertStringContainsString('enum Direction', $content);
        $this->assertStringNotContainsString('enum Direction:', $content);
        $this->assertStringContainsString('case north;', $content);
        $this->assertStringContainsString('case south;', $content);
        $this->assertStringContainsString('case east;', $content);
        $this->assertStringContainsString('case west;', $content);
    }

    public function testRecursiveDirectoryScanning(): void
    {
        mkdir($this->tempDir . '/subdir', 0777, true);

        $this->createTestFile('Status.php', '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum;

class Status extends Enum
{
    protected $draft;
}
');
        $this->createTestFile('subdir/Priority.php', '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum;

class Priority extends Enum
{
    protected $low;
    protected $high;
}
');

        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Enum definitions converted: 2', $this->commandTester->getDisplay());

        // Verify both files were converted
        $this->assertStringContainsString('enum Status', file_get_contents($this->tempDir . '/Status.php'));
        $this->assertStringContainsString('enum Priority', file_get_contents($this->tempDir . '/subdir/Priority.php'));
    }

    public function testConvertsFromMethodToFromEnum(): void
    {
        $this->createTestFile('Status.php', '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum;

class Status extends Enum
{
    protected $draft;
}
');
        $this->createTestFile('LabelFormatter.php', '<?php
namespace App\Services;

use App\Enums\Status;
use PhpCompatible\Enum\EnumLabel;

class LabelFormatter
{
    public function format()
    {
        $label = EnumLabel::from(Status::draft());
        return $label->toString();
    }
}
');

        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/LabelFormatter.php');

        // Check from() is converted to fromEnum()
        $this->assertStringContainsString('Php8EnumLabel::fromEnum(Status::draft)', $content);
        $this->assertStringNotContainsString('EnumLabel::from(', $content);
    }

    public function testEnumWithAliasImportIsIgnored(): void
    {
        // Alias imports are detected by usesOurEnumNamespace but the class pattern
        // requires extending 'Enum' directly, so aliases are not converted
        $originalContent = '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum as BaseEnum;

class Status extends BaseEnum
{
    protected $draft;
}
';
        $this->createTestFile('Status.php', $originalContent);

        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        // Alias use is detected but class pattern doesn't match
        $this->assertStringContainsString('Enum definitions converted: 0', $this->commandTester->getDisplay());
    }

    public function testEnumWithFullyQualifiedClassName(): void
    {
        $this->createTestFile('Status.php', '<?php
namespace App\Enums;

class Status extends \PhpCompatible\Enum\Enum
{
    protected $draft;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Enum definitions converted: 1', $this->commandTester->getDisplay());
    }

    public function testEnumInOurNamespace(): void
    {
        $this->createTestFile('Status.php', '<?php
namespace PhpCompatible\Enum;

class Status extends Enum
{
    protected $draft;
}
');
        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Enum definitions converted: 1', $this->commandTester->getDisplay());
    }

    public function testEnumWithNoPropertiesIsIgnored(): void
    {
        $originalContent = '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum;

class EmptyEnum extends Enum
{
}
';
        $this->createTestFile('EmptyEnum.php', $originalContent);

        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Enum definitions converted: 0', $this->commandTester->getDisplay());

        // Verify file was not modified
        $actualContent = file_get_contents($this->tempDir . '/EmptyEnum.php');
        $this->assertEquals($originalContent, $actualContent);
    }

    public function testDryRunShowsUsageUpdates(): void
    {
        $this->createTestFile('Status.php', '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum;

class Status extends Enum
{
    protected $draft;
}
');
        $this->createTestFile('UsageFile.php', '<?php
namespace App\Services;

use App\Enums\Status;

class Service
{
    public function check()
    {
        return Status::draft();
    }
}
');

        $this->commandTester->execute([
            'path' => $this->tempDir,
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Would update usages:', $this->commandTester->getDisplay());
    }

    public function testEnumWithSnakeCaseProperty(): void
    {
        $this->createTestFile('Status.php', '<?php
namespace App\Enums;

use PhpCompatible\Enum\Enum;

class Status extends Enum
{
    protected $pending_review = 1;
    protected $in_progress = 2;
}
');
        $this->createTestFile('UsageFile.php', '<?php
namespace App\Services;

use App\Enums\Status;

class Service
{
    public function check()
    {
        return Status::pending_review();
    }
}
');

        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $content = file_get_contents($this->tempDir . '/Status.php');
        $this->assertStringContainsString("case pending_review = 1;", $content);

        $usageContent = file_get_contents($this->tempDir . '/UsageFile.php');
        $this->assertStringContainsString('Status::pending_review', $usageContent);
        $this->assertStringNotContainsString('Status::pending_review()', $usageContent);
    }

    private function createTestFile(string $filename, string $content): void
    {
        $dir = dirname($this->tempDir . '/' . $filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->tempDir . '/' . $filename, $content);
    }
}
