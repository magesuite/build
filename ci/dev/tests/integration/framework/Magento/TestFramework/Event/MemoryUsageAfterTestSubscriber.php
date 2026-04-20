<?php

declare(strict_types=1);

namespace Magento\TestFramework\Event;

class MemoryUsageAfterTestSubscriber implements \PHPUnit\Event\Test\FinishedSubscriber
{
    public const string PHPUNIT_MEMORY_USAGE_LOG_FILENAME = 'phpunit_memory_usage.log';

    protected static float $previousMemory = 0;
    protected static string $logFile = '';

    public function notify(\PHPUnit\Event\Test\Finished $event): void
    {
        if (static::$logFile === '') {
            static::$logFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::PHPUNIT_MEMORY_USAGE_LOG_FILENAME;
            file_put_contents(static::$logFile, '');
        }

        gc_collect_cycles();
        $realMb = memory_get_usage(true) / 1024 / 1024;
        $usedMb = memory_get_usage(false) / 1024 / 1024;
        $delta = $realMb - static::$previousMemory;
        $classCount = count(get_declared_classes());
        $testName = $event->test()->id();

        $line = sprintf(
            "delta: %+8.1f MB | real: %8.1f MB | used: %8.1f MB | classes: %d | %s\n",
            $delta,
            $realMb,
            $usedMb,
            $classCount,
            $testName
        );

        file_put_contents(static::$logFile, $line, FILE_APPEND);

        if (static::$previousMemory === 0.0) {
            fwrite(STDERR, sprintf("\n[MemoryMonitor] Logging to: %s\n", static::$logFile));
        }

        static::$previousMemory = $realMb;
    }
}
