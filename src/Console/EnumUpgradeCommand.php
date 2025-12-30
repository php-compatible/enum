<?php

namespace PhpCompatible\Enum\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to upgrade PhpCompatible Enum classes to PHP 8 native enums.
 */
class EnumUpgradeCommand extends Command
{
    protected static $defaultName = 'php-compatible-enum-upgrade-to-php8';

    /**
     * @var array<string, array{className: string, cases: array<string, mixed>, isStringBacked: bool}>
     */
    private $enumDefinitions = [];

    protected function configure(): void
    {
        $this
            ->setDescription('Upgrade PhpCompatible Enum classes to PHP 8 native enums')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to scan for Enum classes',
                'src'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show what would be changed without modifying files'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $dryRun = $input->getOption('dry-run');

        if (!is_dir($path)) {
            $output->writeln("<error>Path not found: {$path}</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Scanning {$path} for PhpCompatible Enum classes...</info>");
        if ($dryRun) {
            $output->writeln("<comment>Dry run mode - no files will be modified</comment>");
        }
        $output->writeln("");

        $files = $this->findPhpFiles($path);

        // Phase 1: Find and convert enum definitions
        $enumFiles = 0;
        foreach ($files as $file) {
            if ($this->processEnumDefinition($file, $dryRun, $output)) {
                $enumFiles++;
            }
        }

        // Phase 2: Update usages across all files
        $usageFiles = 0;
        foreach ($files as $file) {
            if ($this->processUsages($file, $dryRun, $output)) {
                $usageFiles++;
            }
        }

        $output->writeln("");
        $output->writeln("<info>Done.</info>");
        $output->writeln("  Enum definitions " . ($dryRun ? "to be " : "") . "converted: {$enumFiles}");
        $output->writeln("  Files with usage updates " . ($dryRun ? "to be " : "") . "modified: {$usageFiles}");

        return Command::SUCCESS;
    }

    /**
     * Find all PHP files in a directory recursively.
     *
     * @param string $path
     * @return array<string>
     */
    private function findPhpFiles(string $path): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Process enum definition and convert to PHP 8 syntax.
     *
     * @param string $file
     * @param bool $dryRun
     * @param OutputInterface $output
     * @return bool
     */
    private function processEnumDefinition(string $file, bool $dryRun, OutputInterface $output): bool
    {
        $content = file_get_contents($file);

        // Check if this file uses our Enum namespace
        if (!$this->usesOurEnumNamespace($content)) {
            return false;
        }

        // Check if this file contains a class that extends Enum (not in comments)
        // Look for class declaration at start of line (after possible whitespace)
        if (!preg_match('/^[ \t]*class\s+(\w+)\s+extends\s+(?:\\\\?PhpCompatible\\\\Enum\\\\)?Enum\b/m', $content, $classMatch)) {
            return false;
        }

        $className = $classMatch[1];

        // Extract namespace
        $namespace = '';
        if (preg_match('/namespace\s+([\w\\\\]+)\s*;/', $content, $nsMatch)) {
            $namespace = $nsMatch[1];
        }

        // Find all protected properties (enum cases)
        preg_match_all('/protected\s+\$(\w+)(?:\s*=\s*([^;]+))?;/', $content, $propMatches, PREG_SET_ORDER);

        if (empty($propMatches)) {
            return false;
        }

        // Determine if this is a string-backed or int-backed enum
        $cases = [];
        $isStringBacked = false;
        $hasExplicitValues = false;
        $autoIncrement = 0;

        foreach ($propMatches as $match) {
            $propName = $match[1];
            $value = isset($match[2]) ? trim($match[2]) : null;

            if ($value !== null) {
                $hasExplicitValues = true;
                // Check if it's a string value
                if (preg_match('/^[\'"]/', $value)) {
                    $isStringBacked = true;
                    $cases[$propName] = $value;
                } else {
                    // Integer value
                    $intVal = (int)$value;
                    $cases[$propName] = $intVal;
                    $autoIncrement = $intVal + 1;
                }
            } else {
                // Auto-increment for null values
                $cases[$propName] = $autoIncrement;
                $autoIncrement++;
            }
        }

        // Store enum info for usage updates
        $fqcn = $namespace ? "{$namespace}\\{$className}" : $className;
        $this->enumDefinitions[$fqcn] = [
            'className' => $className,
            'cases' => $cases,
            'isStringBacked' => $isStringBacked,
        ];

        // Generate PHP 8 enum syntax
        $newContent = $this->convertToPhp8Enum($content, $className, $cases, $isStringBacked, $hasExplicitValues);

        if ($newContent === $content) {
            return false; // @codeCoverageIgnore
        }

        if (!$dryRun) {
            file_put_contents($file, $newContent);
            $output->writeln("<info>Converted enum:</info> {$file}");
        } else {
            $output->writeln("<info>Would convert enum:</info> {$file}");
        }

        return true;
    }

    /**
     * Convert class-based enum to PHP 8 native enum syntax.
     *
     * @param string $content
     * @param string $className
     * @param array<string, mixed> $cases
     * @param bool $isStringBacked
     * @param bool $hasExplicitValues
     * @return string
     */
    private function convertToPhp8Enum(
        string $content,
        string $className,
        array $cases,
        bool $isStringBacked,
        bool $hasExplicitValues
    ): string {
        // Remove the use statement for our Enum
        $content = preg_replace('/use\s+PhpCompatible\\\\Enum\\\\Enum\s*;\s*\n?/', '', $content);
        $content = preg_replace('/use\s+PhpCompatible\\\\Enum\\\\Value\s*;\s*\n?/', '', $content);

        // Replace EnumLabel with Php8EnumLabel in use statements
        $content = preg_replace(
            '/use\s+PhpCompatible\\\\Enum\\\\EnumLabel\s*;/',
            'use PhpCompatible\\Enum\\Php8EnumLabel;',
            $content
        );

        // Build the enum declaration
        if ($hasExplicitValues) {
            $backingType = $isStringBacked ? 'string' : 'int';
            $enumDecl = "enum {$className}: {$backingType}";
        } else {
            $enumDecl = "enum {$className}";
        }

        // Replace class declaration with enum declaration
        $content = preg_replace(
            '/^([ \t]*)class\s+' . preg_quote($className, '/') . '\s+extends\s+(?:\\\\?PhpCompatible\\\\Enum\\\\)?Enum\s*\{/m',
            '$1' . $enumDecl . "\n" . '$1{',
            $content
        );

        // Remove any @method annotations from docblock (they're no longer needed)
        // Match whole lines containing @method static Value
        $content = preg_replace('/^[ \t]*\*[ \t]*@method\s+static\s+Value\s+\w+\(\)[ \t]*\r?\n/m', '', $content);

        // Clean up empty docblocks (with only whitespace and asterisks)
        $content = preg_replace('/\/\*\*\s*(?:\*\s*)*\*\/\s*\n/', '', $content);

        // Convert protected properties to cases
        foreach ($cases as $caseName => $value) {
            // Match the property with its leading whitespace
            $pattern = '/^([ \t]*)protected\s+\$' . preg_quote($caseName, '/') . '(?:\s*=\s*[^;]+)?;/m';

            if ($hasExplicitValues) {
                $caseDecl = '$1case ' . $caseName . ' = ' . $value . ';';
            } else {
                $caseDecl = '$1case ' . $caseName . ';';
            }

            $content = preg_replace($pattern, $caseDecl, $content);
        }

        return $content;
    }

    /**
     * Process usages of enum cases and EnumLabel.
     *
     * @param string $file
     * @param bool $dryRun
     * @param OutputInterface $output
     * @return bool
     */
    private function processUsages(string $file, bool $dryRun, OutputInterface $output): bool
    {
        $content = file_get_contents($file);
        $originalContent = $content;

        // Replace use statement for EnumLabel (must be done before usage replacement)
        $content = preg_replace(
            '/use\s+PhpCompatible\\\\Enum\\\\EnumLabel\s*;/',
            'use PhpCompatible\\Enum\\Php8EnumLabel;',
            $content
        );

        // Replace EnumLabel::from() with Php8EnumLabel::fromEnum()
        // Use word boundary to avoid matching Php8EnumLabel
        $content = preg_replace('/\bEnumLabel::from\(/', 'Php8EnumLabel::fromEnum(', $content);

        // For each known enum, update case access to remove parentheses
        foreach ($this->enumDefinitions as $fqcn => $info) {
            $className = $info['className'];
            $cases = $info['cases'];

            foreach (array_keys($cases) as $caseName) {
                // Pattern to match ClassName::caseName() and convert to ClassName::caseName
                // Handle various case styles (camelCase, PascalCase, snake_case, SCREAMING_SNAKE)
                $normalizedPattern = $this->buildCasePattern($caseName);

                // Match ClassName::caseName() with parentheses
                $pattern = '/' . preg_quote($className, '/') . '::(' . $normalizedPattern . ')\(\)/';
                $content = preg_replace($pattern, $className . '::' . $caseName, $content);
            }
        }

        if ($content === $originalContent) {
            return false;
        }

        if (!$dryRun) {
            file_put_contents($file, $content);
            $output->writeln("<info>Updated usages:</info> {$file}");
        } else {
            $output->writeln("<info>Would update usages:</info> {$file}");
        }

        return true;
    }

    /**
     * Build a regex pattern that matches different case styles of a name.
     *
     * @param string $caseName
     * @return string
     */
    private function buildCasePattern(string $caseName): string
    {
        // Normalize to detect different case styles
        $lower = strtolower($caseName);

        // Convert camelCase to parts
        $parts = preg_split('/(?=[A-Z])/', $caseName, -1, PREG_SPLIT_NO_EMPTY);
        $parts = array_map('strtolower', $parts);

        // Also handle snake_case input
        if (strpos($caseName, '_') !== false) {
            $parts = explode('_', strtolower($caseName));
        }

        // Build pattern that matches camelCase, PascalCase, snake_case, SCREAMING_SNAKE_CASE
        $patterns = [];

        // camelCase: first part lower, rest capitalized
        $camel = $parts[0];
        for ($i = 1; $i < count($parts); $i++) {
            $camel .= ucfirst($parts[$i]);
        }
        $patterns[] = preg_quote($camel, '/');

        // PascalCase: all parts capitalized
        $pascal = implode('', array_map('ucfirst', $parts));
        $patterns[] = preg_quote($pascal, '/');

        // snake_case
        $snake = implode('_', $parts);
        $patterns[] = preg_quote($snake, '/');

        // SCREAMING_SNAKE_CASE
        $screaming = strtoupper($snake);
        $patterns[] = preg_quote($screaming, '/');

        return '(?:' . implode('|', array_unique($patterns)) . ')';
    }

    /**
     * Check if file uses our PhpCompatible\Enum namespace.
     *
     * @param string $content
     * @return bool
     */
    private function usesOurEnumNamespace(string $content): bool
    {
        if (preg_match('/use\s+PhpCompatible\\\\Enum\\\\Enum\s*;/', $content)) {
            return true;
        }

        if (preg_match('/use\s+PhpCompatible\\\\Enum\\\\Enum\s+as\s+\w+\s*;/', $content)) {
            return true;
        }

        if (preg_match('/extends\s+\\\\?PhpCompatible\\\\Enum\\\\Enum/', $content)) {
            return true;
        }

        if (preg_match('/namespace\s+PhpCompatible\\\\Enum/', $content) &&
            preg_match('/class\s+\w+\s+extends\s+Enum/', $content)) {
            return true;
        }

        return false;
    }
}
