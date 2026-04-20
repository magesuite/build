<?php

declare(strict_types=1);

namespace Magento\TestFramework\Event;

class MemoryUsageMonitor implements \PHPUnit\Runner\Extension\Extension
{
    public function bootstrap(
        \PHPUnit\TextUI\Configuration\Configuration $configuration,
        \PHPUnit\Runner\Extension\Facade $facade,
        \PHPUnit\Runner\Extension\ParameterCollection $parameters
    ): void {
        if (!$parameters->has('enabled') || !$parameters->get('enabled')) {
            return;
        }

        $facade->registerSubscribers(
            new MemoryUsageAfterTestSubscriber(),
            new MemoryUsageSummarySubscriber()
        );
    }
}
