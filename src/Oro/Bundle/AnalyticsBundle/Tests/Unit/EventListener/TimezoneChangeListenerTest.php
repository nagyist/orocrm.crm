<?php

namespace Oro\Bundle\AnalyticsBundle\Tests\Unit\EventListener;

use Oro\Bundle\AnalyticsBundle\EventListener\TimezoneChangeListener;
use Oro\Bundle\AnalyticsBundle\Model\RFMMetricStateManager;
use Oro\Bundle\AnalyticsBundle\Service\CalculateAnalyticsScheduler;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;

class TimezoneChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RFMMetricStateManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var CalculateAnalyticsScheduler|\PHPUnit\Framework\MockObject\MockObject */
    private $scheduler;

    /** @var TimezoneChangeListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->manager = $this->createMock(RFMMetricStateManager::class);
        $this->scheduler = $this->createMock(CalculateAnalyticsScheduler::class);

        $this->listener = new TimezoneChangeListener($this->manager, $this->scheduler);
    }

    public function testWasNotChanged()
    {
        $this->manager->expects($this->never())
            ->method('resetMetrics');

        $this->scheduler->expects($this->never())
            ->method('scheduleForAllChannels');

        $this->listener->onConfigUpdate(new ConfigUpdateEvent([], 'global', 0));
    }

    public function testSuccessChange()
    {
        $this->manager->expects($this->once())
            ->method('resetMetrics');

        $this->scheduler->expects($this->once())
            ->method('scheduleForAllChannels');

        $this->manager->expects($this->once())
            ->method('resetMetrics');

        $this->listener->onConfigUpdate(
            new ConfigUpdateEvent(['oro_locale.timezone' => ['old' => 1, 'new' => 2]], 'global', 0)
        );
    }
}
