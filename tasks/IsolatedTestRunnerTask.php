<?php

declare(strict_types=1);

class IsolatedTestRunnerTask extends \Phing\Task
{
    protected string $phpunitLocation = '';
    protected string $phpunitXml = '';
    protected string $folder = '';
    protected string $testsuite = '';
    protected int $batchSize = 10;
    protected int $batchIndex = 0;
    protected string $phpunitOptions = '';

    public function setPhpunitLocation(string $value): void
    {
        $this->phpunitLocation = $value;
    }

    public function setPhpunitXml(string $value): void
    {
        $this->phpunitXml = $value;
    }

    public function setFolder(string $value): void
    {
        $this->folder = $value;
    }

    public function setTestsuite(string $value): void
    {
        $this->testsuite = $value;
    }

    public function setBatchSize(int $value): void
    {
        $this->batchSize = $value;
    }

    public function setBatchIndex(int $value): void
    {
        $this->batchIndex = $value;
    }

    public function setPhpunitOptions(string $value): void
    {
        $this->phpunitOptions = $value;
    }

    public function main(): void
    {
        $testFiles = $this->discoverTestFiles();

        if (empty($testFiles)) {
            $this->log('No test files found');
            return;
        }

        $batches = array_chunk($testFiles, $this->batchSize);

        $this->log(sprintf(
            'Found %d test files, running in %d batches of up to %d',
            count($testFiles),
            count($batches),
            $this->batchSize
        ));

        if ($this->batchIndex !== 0) {
            $this->runSingleBatch($batches);
            return;
        }

        $failedBatches = 0;

        foreach ($batches as $index => $batch) {
            $this->log(sprintf(
                'Batch %d/%d (%d files)',
                $index + 1,
                count($batches),
                count($batch)
            ));

            $exitCode = $this->runBatch($batch);

            if ($exitCode !== 0) {
                $failedBatches++;
                $this->log(sprintf('Batch %d failed with exit code %d', $index + 1, $exitCode), \Phing\Project::MSG_ERR);
            }
        }

        if ($failedBatches > 0) {
            throw new \Phing\Exception\BuildException(
                sprintf('%d of %d batches failed', $failedBatches, count($batches))
            );
        }
    }

    protected function runSingleBatch(array $batches): void
    {
        $totalBatches = count($batches);

        if ($this->batchIndex < 1 || $this->batchIndex > $totalBatches) {
            throw new \Phing\Exception\BuildException(
                sprintf('Batch index %d is out of range (1-%d)', $this->batchIndex, $totalBatches)
            );
        }

        $batch = $batches[$this->batchIndex - 1];

        $this->log(sprintf(
            'Running batch %d/%d (%d files)',
            $this->batchIndex,
            $totalBatches,
            count($batch)
        ));

        $exitCode = $this->runBatch($batch);

        if ($exitCode !== 0) {
            throw new \Phing\Exception\BuildException(
                sprintf('Batch %d failed with exit code %d', $this->batchIndex, $exitCode)
            );
        }
    }

    protected function discoverTestFiles(): array
    {
        if ($this->folder) {
            return $this->findTestFilesInDirectory($this->folder);
        }

        if ($this->testsuite) {
            return $this->findTestFilesFromTestsuite();
        }

        throw new \Phing\Exception\BuildException('Either folder or testsuite must be set');
    }

    protected function findTestFilesInDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            $realpath = realpath($directory);

            if (!$realpath) {
                throw new \Phing\Exception\BuildException('File not found: ' . $directory);
            }

            return [$realpath];
        }

        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
                $files[] = $file->getRealPath();
            }
        }

        sort($files);

        return $files;
    }

    protected function findTestFilesFromTestsuite(): array
    {
        $xmlPath = realpath($this->phpunitXml);

        if (!$xmlPath) {
            throw new \Phing\Exception\BuildException('PHPUnit XML not found: ' . $this->phpunitXml);
        }

        $xml = simplexml_load_file($xmlPath);
        $xmlDir = dirname($xmlPath);
        $files = [];
        $excludedPaths = [];

        foreach ($xml->testsuites->testsuite as $suite) {
            if ((string)$suite['name'] !== $this->testsuite) {
                continue;
            }

            foreach ($suite->exclude as $excludePattern) {
                $pattern = $xmlDir . '/' . (string)$excludePattern;
                $matched = glob($pattern);

                foreach ($matched as $path) {
                    $realPath = realpath($path);

                    if ($realPath) {
                        $excludedPaths[] = $realPath;
                    }
                }
            }

            foreach ($suite->directory as $directoryPattern) {
                $pattern = $xmlDir . '/' . (string)$directoryPattern;
                $directories = glob($pattern, GLOB_ONLYDIR);

                foreach ($directories as $directory) {
                    $found = $this->findTestFilesInDirectory($directory);
                    $files = array_merge($files, $found);
                }
            }

            foreach ($suite->file as $fileEntry) {
                $filePath = $xmlDir . '/' . (string)$fileEntry;
                $realPath = realpath($filePath);

                if ($realPath && is_file($realPath)) {
                    $files[] = $realPath;
                }
            }
        }

        $files = array_unique($files);
        $files = $this->applyExclusions($files, $excludedPaths);
        sort($files);

        return $files;
    }

    protected function applyExclusions(array $files, array $excludedPaths): array
    {
        if (empty($excludedPaths)) {
            return $files;
        }

        return array_filter($files, function (string $file) use ($excludedPaths): bool {
            foreach ($excludedPaths as $excluded) {
                if ($file === $excluded || str_starts_with($file, $excluded . '/')) {
                    return false;
                }
            }

            return true;
        });
    }

    protected function runBatch(array $testFiles): int
    {
        $command = escapeshellarg($this->phpunitLocation)
            . ' -c ' . escapeshellarg($this->phpunitXml);

        foreach ($testFiles as $file) {
            $command .= ' ' . escapeshellarg($file);
        }

        if ($this->phpunitOptions) {
            $command .= ' ' . $this->phpunitOptions;
        }

        passthru($command, $exitCode);

        return $exitCode;
    }
}
