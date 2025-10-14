<?php

namespace App\Command\Cron;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Sentry\State\Scope;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface as MessengerSerializerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(name: 'app:monitor-messenger-queues', description: 'Alerte si des messages restent trop longtemps en file d\'attente')]
class MonitorMessengerQueuesCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly MessengerSerializerInterface $messengerSerialize,
        private readonly SerializerInterface $serializer,
        #[Autowire(env: 'MESSENGER_ALERT_THRESHOLD')]
        private readonly string $threshold,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sql = <<<SQL
            SELECT *
            FROM messenger_messages
            WHERE queue_name NOT LIKE 'failed%'
              AND created_at < (NOW() - INTERVAL $this->threshold)
            ORDER BY created_at DESC;
         SQL;

        $rows = $this->connection->fetchAllAssociative($sql);

        if (empty($rows)) {
            $output->writeln('OK, no old messages found.');

            return Command::SUCCESS;
        }

        foreach ($rows as $row) {
            /** @var Envelope $envelope */
            $envelope = $this->messengerSerialize->decode([
                'body' => $row['body'],
            ]);

            $envelopeMessage = $envelope->getMessage();
            $typeMessage = $envelopeMessage::class;
            $jsonMessage = $this->serializer->serialize($envelopeMessage, 'json');

            \Sentry\configureScope(function (Scope $scope) use ($row, $typeMessage, $jsonMessage): void {
                $scope->setTag('type', $typeMessage);
                $scope->setTag('queue_name', $row['queue_name']);
                $scope->setExtra('message', $jsonMessage);
            });

            $message = sprintf(
                'Messenger queue "%s" stalled: %s message older than %s(s) detected.',
                $row['queue_name'],
                $typeMessage,
                strtolower($this->threshold),
            );

            \Sentry\captureMessage($message);
            $output->writeln($message);
        }

        return Command::SUCCESS;
    }
}
