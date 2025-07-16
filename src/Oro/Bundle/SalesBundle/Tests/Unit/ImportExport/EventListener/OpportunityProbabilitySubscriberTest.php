<?php

namespace Oro\Bundle\SalesBundle\Tests\Unit\ImportExport\EventListener;

use Oro\Bundle\ImportExportBundle\Event\DenormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;
use Oro\Bundle\SalesBundle\Entity\Opportunity;
use Oro\Bundle\SalesBundle\ImportExport\EventListener\OpportunityProbabilitySubscriber;
use PHPUnit\Framework\TestCase;

class OpportunityProbabilitySubscriberTest extends TestCase
{
    private Opportunity $opportunity;
    private OpportunityProbabilitySubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        $this->opportunity = new Opportunity();

        $this->subscriber = new OpportunityProbabilitySubscriber();
    }

    public function testBeforeNormalize(): void
    {
        $this->opportunity->setProbability(0.1);
        $event = new NormalizeEntityEvent($this->opportunity, [], false);
        $this->subscriber->beforeNormalize($event);
        $this->assertEquals('10', $this->opportunity->getProbability());
    }

    public function testAfterDenormalize(): void
    {
        $this->opportunity->setProbability('10');
        $event = new DenormalizeEntityEvent($this->opportunity, []);
        $this->subscriber->afterDenormalize($event);
        $this->assertEquals(0.1, $this->opportunity->getProbability());
    }
}
