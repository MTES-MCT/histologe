<?php

namespace App\Command;

use App\Entity\Suivi;
use App\Repository\NotificationRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:delete-useless-suivis-notifications',
    description: 'Delete useless suivis and notifications'
)]
class DeleteUselessSuivisAndNotifications extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SuiviRepository $suiviRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly ParameterBagInterface $parameterBag,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userCreatedBy = $this->userRepository->findOneBy(['email' => $this->parameterBag->get('user_system_email')]);

        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT s.id
                FROM suivi s
                WHERE s.created_by_id = :created_by
                AND s.context = :context
                AND DATE_FORMAT(s.created_at, \'%Y-%m-%d\') = :created_at';
        $statement = $connection->prepare($sql);
        $parameters = [
            'created_by' => $userCreatedBy->getId(),
            'context' => Suivi::CONTEXT_INTERVENTION,
            'created_at' => '2025-06-03',
        ];

        $suivis = $statement->executeQuery($parameters)->fetchFirstColumn();
        $count = \count($suivis);

        $this->notificationRepository->deleteBySuiviIds($suivis);
        $this->suiviRepository->deleteBySuiviIds($suivis);

        $this->io->success(\sprintf('%s suivis were successfully deleted.', $count));

        return Command::SUCCESS;
    }
}
