<?php

namespace App\Scheduler;

use App\Scheduler\Message\SyncEsaboraSCHSMessage;
use App\Scheduler\Message\SyncEsaboraSISHInterventionMessage;
use App\Scheduler\Message\SyncEsaboraSISHMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;

#[AsSchedule('esabora')]
final class EsaboraScheduleProvider implements ScheduleProviderInterface
{
    public function __construct(
        #[Autowire('%env(ESABORA_CRON_SCHEDULE_SYNC_SISH)%')]
        private readonly string $syncSishSchedule,
        #[Autowire('%env(ESABORA_CRON_SCHEDULE_SYNC_SISH_INTERVENTION)%')]
        private readonly string $syncSishInterventionSchedule,
        #[Autowire('%env(ESABORA_CRON_SCHEDULE_SYNC_SCHS)%')]
        private readonly string $syncSchsSchedule,
        private readonly LockFactory $lockFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getSchedule(): Schedule
    {
        $messages = [...$this->getMessages()];

        $schedule = new Schedule()
            ->lock($this->lockFactory->createLock('scheduler-esabora'));

        if ([] === $messages) {
            $this->logger->warning('[scheduler] No Esabora messages scheduled');

            return $schedule;
        }

        return $schedule->add(...$messages);
    }

    /**
     * @return iterable<RecurringMessage>
     */
    private function getMessages(): iterable
    {
        yield from $this->addCronMessage($this->syncSishSchedule, new SyncEsaboraSISHMessage());
        yield from $this->addCronMessage($this->syncSishInterventionSchedule, new SyncEsaboraSISHInterventionMessage());
        yield from $this->addCronMessage($this->syncSchsSchedule, new SyncEsaboraSCHSMessage());
    }

    /**
     * @return iterable<RecurringMessage>
     */
    private function addCronMessage(string $cron, object $message): iterable
    {
        if ('' === trim($cron) || 'false' === strtolower(trim($cron))) {
            $this->logger->warning(sprintf(
                '[scheduler] Message "%s" disabled',
                $message::class,
            ));

            return;
        }

        yield RecurringMessage::trigger(
            CronExpressionTrigger::fromSpec($cron),
            new RedispatchMessage(
                $message,
                'async'
            ),
        );
    }
}
