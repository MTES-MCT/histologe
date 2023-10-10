<?php

namespace App\Command\Clock;

use Cron\Cron;
use Cron\Executor\Executor;
use Cron\Job\ShellJob;
use Cron\Report\CronReport;
use Cron\Resolver\ArrayResolver;
use Cron\Schedule\CrontabSchedule;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:scheduled-task',
    description: 'SISH Task executed in a scalingo clock process in prod cause duration execution is > 15 minutes',
)]
class ScheduledTaskCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tasks = [
            ['command' => 'php bin/console app:sync-esabora-sish', 'schedule' => '*/1 * * * *'],
            ['command' => 'php bin/console app:sync-esabora-sish-intervention', 'schedule' => '*/2 * * * *'],
        ];

        $io = new SymfonyStyle($input, $output);
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        $resolver = new ArrayResolver();
        foreach ($tasks as $task) {
            $job = new ShellJob();
            $job->setCommand($task['command']);
            $job->setSchedule(new CrontabSchedule($task['schedule']));
            $resolver->addJob($job);
        }

        $cron = new Cron();
        $cron->setExecutor(new Executor());
        $cron->setResolver($resolver);

        // Every hour, run the scheduler which will execute the tasks
        // which have to be started at the given minute.
        while (true) {
            $io->info('[CRON] Running tasks');
            /** @var CronReport $report */
            $report = $cron->run();

            dump($cron->isRunning());
            while ($cron->isRunning()) {
                $io->success(sprintf('[CRON] %d tasks has been executed', \count($report->getReports())));

                foreach ($report->getReports() as $jobReport) {
                    $output = $jobReport->getOutput();
                    foreach ($output as $line) {
                        $io->success(sprintf('[CRON] %s', $line));
                    }
                }
            }
            sleep(3600);
        }

        return Command::SUCCESS;
    }
}
