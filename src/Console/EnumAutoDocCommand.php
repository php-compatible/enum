<?php

namespace PhpCompatible\Enum\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to automatically generate PHPDoc @method annotations for Enum classes.
 */
class EnumAutoDocCommand extends Command
{
    protected static $defaultName = 'enumautodoc';

    /**
     * @var array<string, callable>
     */
    private $caseConverters = [];

    public function __construct()
    {
        parent::__construct();
        $this->initCaseConverters();
    }

    private function initCaseConverters(): void
    {
        $this->caseConverters = [
            'camelCase' => function (string $name): string {
                return lcfirst($name);
            },
            'PascalCase' => function (string $name): string {
                return ucfirst($name);
            },
            'snake_case' => function (string $name): string {
                return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
            },
            'SCREAMING_SNAKE_CASE' => function (string $name): string {
                return strtoupper(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
            },
        ];
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate PHPDoc @method annotations for Enum classes')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to scan for Enum classes',
                'src'
            )
            ->addOption(
                'camel-case',
                null,
                InputOption::VALUE_NONE,
                'Use camelCase for method names (default)'
            )
            ->addOption(
                'pascal-case',
                null,
                InputOption::VALUE_NONE,
                'Use PascalCase for method names'
            )
            ->addOption(
                'snake-case',
                null,
                InputOption::VALUE_NONE,
                'Use snake_case for method names'
            )
            ->addOption(
                'screaming-snake-case',
                null,
                InputOption::VALUE_NONE,
                'Use SCREAMING_SNAKE_CASE for method names'
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

        // Determine case style from options
        $case = $this->determineCaseStyle($input);

        if (!is_dir($path)) {
            $output->writeln("<error>Path not found: {$path}</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Scanning {$path} for Enum classes...</info>");
        $output->writeln("Case style: <comment>{$case}</comment>");
        if ($dryRun) {
            $output->writeln("<comment>Dry run mode - no files will be modified</comment>");
        }
        $output->writeln("");

        $files = $this->findPhpFiles($path);
        $updated = 0;

        foreach ($files as $file) {
            if ($this->processFile($file, $case, $dryRun, $output)) {
                $updated++;
            }
        }

        $output->writeln("");
        $output->writeln("<info>Done. {$updated} file(s) " . ($dryRun ? "would be " : "") . "updated.</info>");

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
     * Process a single PHP file.
     *
     * @param string $file
     * @param string $case
     * @param bool $dryRun
     * @param OutputInterface $output
     * @return bool True if file was updated
     */
    private function processFile(string $file, string $case, bool $dryRun, OutputInterface $output): bool
    {
        $content = file_get_contents($file);

        // Check if this file uses our Enum namespace
        if (!$this->usesOurEnumNamespace($content)) {
            return false;
        }

        // Check if this file contains a class that extends Enum
        if (!preg_match('/class\s+(\w+)\s+extends\s+.*Enum/i', $content)) {
            return false;
        }

        // Get the class name
        preg_match('/class\s+(\w+)\s+extends/', $content, $matches);
        $className = $matches[1];

        // Find protected properties (enum cases)
        preg_match_all('/protected\s+\$(\w+)/', $content, $propMatches);
        $properties = $propMatches[1];

        if (empty($properties)) {
            return false;
        }

        if ($output->isVerbose()) {
            $output->writeln("Processing: <comment>{$file}</comment>");
            $output->writeln("  Class: {$className}");
            $output->writeln("  Properties: " . implode(', ', $properties));
        }

        // Generate @method annotations
        $converter = $this->caseConverters[$case];
        $methods = [];
        foreach ($properties as $prop) {
            $methodName = $converter($prop);
            $methods[] = " * @method static Value {$methodName}()";
        }

        $newDocBlock = $this->updateDocBlock($content, $methods);

        if ($newDocBlock === $content) {
            if ($output->isVerbose()) {
                $output->writeln("  <comment>No changes needed</comment>");
            }
            return false;
        }

        if (!$dryRun) {
            file_put_contents($file, $newDocBlock);
            $output->writeln("<info>Updated:</info> {$file}");
        } else {
            $output->writeln("<info>Would update:</info> {$file}");
        }

        return true;
    }

    /**
     * Update or create the docblock with @method annotations.
     *
     * @param string $content
     * @param array<string> $methods
     * @return string
     */
    private function updateDocBlock(string $content, array $methods): string
    {
        // Pattern to match existing docblock before class declaration
        $classPattern = '/^(.*?)(\/\*\*.*?\*\/\s*)?(class\s+\w+\s+extends\s+.*Enum)/ms';

        if (preg_match($classPattern, $content, $matches)) {
            $before = $matches[1];
            $existingDoc = $matches[2] ?? '';
            $classDecl = $matches[3];

            // Remove existing @method annotations that match our properties
            $existingDoc = preg_replace('/^\s*\*\s*@method\s+static\s+Value\s+\w+\(\)\s*$/m', '', $existingDoc);

            // Clean up empty lines in docblock
            $existingDoc = preg_replace('/(\*\s*\n)+/', "*\n", $existingDoc);

            if (empty(trim($existingDoc)) || $existingDoc === '/** */') {
                // Create new docblock
                $newDoc = "/**\n" . implode("\n", $methods) . "\n */\n";
            } else {
                // Insert methods before closing */
                $existingDoc = preg_replace('/\s*\*\/\s*$/', '', $existingDoc);
                $existingDoc = rtrim($existingDoc) . "\n";
                $newDoc = $existingDoc . implode("\n", $methods) . "\n */\n";
            }

            return $before . $newDoc . $classDecl . substr($content, strlen($matches[0]));
        }

        return $content;
    }

    /**
     * Determine the case style from input options.
     *
     * @param InputInterface $input
     * @return string
     */
    private function determineCaseStyle(InputInterface $input): string
    {
        if ($input->getOption('pascal-case')) {
            return 'PascalCase';
        }
        if ($input->getOption('snake-case')) {
            return 'snake_case';
        }
        if ($input->getOption('screaming-snake-case')) {
            return 'SCREAMING_SNAKE_CASE';
        }
        // Default to camelCase
        return 'camelCase';
    }

    /**
     * Check if file uses our PhpCompatible\Enum namespace.
     *
     * @param string $content
     * @return bool
     */
    private function usesOurEnumNamespace(string $content): bool
    {
        // Check for use statement importing our Enum class
        if (preg_match('/use\s+PhpCompatible\\\\Enum\\\\Enum\s*;/', $content)) {
            return true;
        }

        // Check for use statement with alias
        if (preg_match('/use\s+PhpCompatible\\\\Enum\\\\Enum\s+as\s+\w+\s*;/', $content)) {
            return true;
        }

        // Check for fully qualified class name in extends
        if (preg_match('/extends\s+\\\\?PhpCompatible\\\\Enum\\\\Enum/', $content)) {
            return true;
        }

        // Check if file is in our namespace and extends Enum
        if (preg_match('/namespace\s+PhpCompatible\\\\Enum/', $content) &&
            preg_match('/class\s+\w+\s+extends\s+Enum/', $content)) {
            return true;
        }

        return false;
    }
}
