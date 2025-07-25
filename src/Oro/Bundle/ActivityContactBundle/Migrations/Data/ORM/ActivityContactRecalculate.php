<?php

namespace Oro\Bundle\ActivityContactBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CronBundle\Async\Topic\RunCommandTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Produces message to recalculate contacting activities.
 */
class ActivityContactRecalculate extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var MessageProducerInterface $producer */
        $producer = $this->container->get('oro_message_queue.client.message_producer');
        $producer->send(RunCommandTopic::getName(), [
            'command' => 'oro:activity-contact:recalculate',
            'arguments' => ['-v' => true, '--disabled-listeners' => ['all']]
        ]);
    }
}
