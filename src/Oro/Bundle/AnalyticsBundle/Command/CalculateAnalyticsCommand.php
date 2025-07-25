<?php

declare(strict_types=1);

namespace Oro\Bundle\AnalyticsBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AnalyticsBundle\Model\AnalyticsAwareInterface;
use Oro\Bundle\AnalyticsBundle\Service\CalculateAnalyticsScheduler;
use Oro\Bundle\ChannelBundle\Entity\Channel;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Calculates all registered analytic metrics.
 */
#[AsCommand(
    name: 'oro:cron:analytic:calculate',
    description: 'Calculates all registered analytic metrics.'
)]
class CalculateAnalyticsCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    private ManagerRegistry $doctrine;
    private CalculateAnalyticsScheduler $calculateAnalyticsScheduler;

    public function __construct(ManagerRegistry $doctrine, CalculateAnalyticsScheduler $calculateAnalyticsScheduler)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->calculateAnalyticsScheduler = $calculateAnalyticsScheduler;
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '0 0 * * *';
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->addOption('channel', null, InputOption::VALUE_OPTIONAL, 'Channel ID')
            ->addOption('ids', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Customer IDs');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelId = $input->getOption('channel');
        $customerIds = $input->getOption('ids');

        if (!$channelId && $customerIds) {
            throw new \InvalidArgumentException('Option "ids" does not work without "channel"');
        }

        if ($channelId) {
            $output->writeln(sprintf('Schedule analytics calculation for "%s" channel.', $channelId));

            $channel = $this->doctrine->getRepository(Channel::class)->find($channelId);

            // check if given channel is active.
            if (Channel::STATUS_ACTIVE != $channel->getStatus()) {
                $output->writeln(sprintf('Channel not active: %s', $channelId));

                return Command::FAILURE;
            }

            // check if the channel's customer supports analytics.
            if (false === is_a($channel->getCustomerIdentity(), AnalyticsAwareInterface::class, true)) {
                $output->writeln(
                    sprintf('Channel is not supposed to calculate analytics: %s', $channelId)
                );

                return Command::FAILURE;
            }

            $this->calculateAnalyticsScheduler->scheduleForChannel($channelId, $customerIds);
        } else {
            $output->writeln('Schedule analytics calculation for all channels.');

            $this->calculateAnalyticsScheduler->scheduleForAllChannels();
        }

        $output->writeln('Completed');

        return Command::SUCCESS;
    }
}
