<?php

namespace Oro\Bundle\ActivityContactBundle\Tests\Unit\Stub;

use Oro\Bundle\ActivityContactBundle\Direction\DirectionProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Email;

/**
 * Email stub for testing purpose
 */
class EmailStub extends Email
{
    protected $targets = [];

    #[\Override]
    public function getActivityTargets($targetClass = null)
    {
        return $this->targets;
    }

    public function setActivityTargets(array $activityTargets): void
    {
        $this->targets = $activityTargets;
    }

    public function getDirection()
    {
        return DirectionProviderInterface::DIRECTION_INCOMING;
    }
}
