<?php

namespace App\Command\Cron;

use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:sync-object-storage',
    description: 'Synchronize object storage from ovh to scaleway',
)]
class SynchronizeObjectStorageCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $parameterBag,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        #[Autowire(env: 'RCLONE_CONFIG_OVH_S3_BUCKET_NAME')]
        private readonly string $sourceBucketName,
        #[Autowire(env: 'RCLONE_CONFIG_SCALEWAY_S3_BUCKET_NAME')]
        private readonly string $destinationBucketName,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = 'ovh_s3:'.$this->sourceBucketName.'/';
        $destination = 'scaleway_s3:'.$this->destinationBucketName.'/';
        $command = ['rclone', 'sync', $source, $destination, '--progress'];

        $process = new Process($command);
        $process->setTimeout(null);

        $io->note('🚀 Synchronisation en cours...');

        $process->run(function ($type, $buffer) use ($io) {
            if (Process::ERR === $type) {
                $io->error($buffer);
            } else {
                $io->write($buffer);
            }
        });

        if ($process->isSuccessful()) {
            $message = '✅ Synchronisation terminée avec succès.';
            $this->logger->info($message);
            $io->success($message);

            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CRON,
                    to: $this->parameterBag->get('admin_email'),
                    message: $message,
                    cronLabel: 'Synchronisation des buckets',
                )
            );

            return Command::SUCCESS;
        }

        $errorMessage = "❌ Échec de la synchronisation.\n\n".$process->getErrorOutput();
        $this->logger->error($errorMessage);
        $io->error($errorMessage);

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: $errorMessage,
                cronLabel: 'Synchronisation des buckets',
            )
        );

        return Command::FAILURE;
    }
}
