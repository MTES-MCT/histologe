<?php

namespace App\Command;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:reinit-admin-passwords',
    description: 'Reinitialize admin passwords'
)]
class ReinitAdminPasswordsCommand extends Command
{
    private SymfonyStyle $io;

    public const string ROLE_ADMIN = 'ROLE_ADMIN';

    public function __construct(
        private readonly UserManager $userManager,
        private readonly ValidatorInterface $validator,
        private readonly UserRepository $userRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->userRepository->findActiveAdmins();

        foreach ($users as $user) {
            /* @var User $user */
            $user->setPassword('')->setStatut(UserStatus::INACTIVE);
            $this->userManager->loadUserTokenForUser($user, false);

            /** @var ConstraintViolationList $errors */
            $errors = $this->validator->validate($user, null, ['Default']);

            if (\count($errors) > 0) {
                $this->io->error((string) $errors);

                return Command::FAILURE;
            }

            $this->userManager->save($user);

            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_ACCOUNT_ACTIVATION_FROM_BO,
                    to: $user->getEmail(),
                    user: $user,
                )
            );
        }

        $this->io->success(\sprintf(
            '%s admin users were successfully reinitialized',
            \count($users)
        ));

        return Command::SUCCESS;
    }
}
