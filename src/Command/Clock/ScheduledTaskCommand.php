<?php

namespace App\Command\Clock;

use Cron\Cron;
use Cron\Executor\Executor;
use Cron\Job\ShellJob;
use Cron\Report\CronReport;
use Cron\Resolver\ArrayResolver;
use Cron\Schedule\CrontabSchedule;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:scheduled-task',
    description: 'SISH Task executed in a scalingo clock process in prod cause duration execution is > 15 minutes',
)]
class ScheduledTaskCommand extends Command
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sleepInterval = $this->parameterBag->get('clock_process')['sleep_interval'];
        $tasks = $this->parameterBag->get('clock_process')['tasks'];

        $io = new SymfonyStyle($input, $output);
        $table = (new Table($output))->setHeaders(['Command', 'Schedule']);

        $resolver = new ArrayResolver();
        foreach ($tasks as $task) {
            $job = new ShellJob();
            $job->setCommand($task['command']);
            $job->setSchedule(new CrontabSchedule($task['schedule']));
            $resolver->addJob($job);
            $table->addRow([$task['command'], $task['schedule']]);
        }

        $cron = new Cron();
        $cron->setExecutor(new Executor());
        $cron->setResolver($resolver);

        $table->render();
        // Based on sleepInveral, run the scheduler which will execute the tasks
        // which have to be started at the given minute.
        while (true) {
            $this->logger->info('[CRON] Running tasks');
            /** @var CronReport $report */
            $report = $cron->run();

            while ($cron->isRunning()) {
                $io->success(sprintf('[CRON] %d tasks has been executed', \count($report->getReports())));

                foreach ($report->getReports() as $jobReport) {
                    $output = $jobReport->getOutput();
                    foreach ($output as $line) {
                        $io->success(sprintf('[CRON] %s', $line));
                    }
                }
            }
            sleep($sleepInterval);
        }

        return Command::SUCCESS;
    }
}
