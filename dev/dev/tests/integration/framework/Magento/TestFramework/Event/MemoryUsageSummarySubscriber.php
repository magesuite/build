<?php

declare(strict_types=1);

namespace Magento\TestFramework\Event;

class MemoryUsageSummarySubscriber implements \PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber
{
    public function notify(\PHPUnit\Event\TestRunner\ExecutionFinished $event): void
    {
        $logFile = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . \Magento\TestFramework\Event\MemoryUsageAfterTestSubscriber::PHPUNIT_MEMORY_USAGE_LOG_FILENAME;

        if (!is_file($logFile)) {
            return;
        }

        $content = file_get_contents($logFile);

        if ($content === '' || $content === false) {
            return;
        }

        fwrite(STDERR, "\n\n========== MEMORY USAGE PROFILER REPORT ==========\n");
        fwrite(STDERR, $content);
        fwrite(STDERR, "========== END OF MEMORY USAGE PROFILER REPORT ==========\n\n");
    }
}
