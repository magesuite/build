<?php

declare(strict_types=1);

namespace Magento\TestFramework\Event;

class MailTransportMockSubscriber implements \PHPUnit\Event\Test\PreparationStartedSubscriber
{
    public function notify(\PHPUnit\Event\Test\PreparationStarted $event): void
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        if (!$objectManager instanceof \Magento\TestFramework\ObjectManager) {
            return;
        }

        $transportBuilder = $objectManager->get(\Magento\TestFramework\Mail\Template\TransportBuilderMock::class);
        $transportBuilder->clean();

        $objectManager->addSharedInstance(
            $transportBuilder,
            \Magento\Framework\Mail\Template\TransportBuilder::class,
            true
        );
    }
}
