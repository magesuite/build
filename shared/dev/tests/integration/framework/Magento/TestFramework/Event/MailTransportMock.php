<?php

declare(strict_types=1);

namespace Magento\TestFramework\Event;

class MailTransportMock implements \PHPUnit\Runner\Extension\Extension
{
    public function bootstrap(
        \PHPUnit\TextUI\Configuration\Configuration $configuration,
        \PHPUnit\Runner\Extension\Facade $facade,
        \PHPUnit\Runner\Extension\ParameterCollection $parameters
    ): void {
        $facade->registerSubscribers(new \Magento\TestFramework\Event\MailTransportMockSubscriber());
    }
}
