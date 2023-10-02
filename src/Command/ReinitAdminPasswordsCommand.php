<?php

namespace App\Command;

use App\Entity\User;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Token\TokenGeneratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:reinit-admin-passwords',
    description: 'Reinitialize admin passwords'
)]
class ReinitAdminPasswordsCommand extends Command
{
    private SymfonyStyle $io;

    public const ROLE_ADMIN = 'ROLE_ADMIN';

    public function __construct(
        private UserManager $userManager,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $hasher,
        private UserRepository $userRepository,
        private NotificationMailerRegistry $notificationMailerRegistry,
        private TokenGeneratorInterface $tokenGenerator
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
            $password = $this->hasher->hashPassword($user, $this->tokenGenerator->generateToken());

            $user->setPassword($password)->setStatut(User::STATUS_INACTIVE);
            $this->userManager->loadUserToken($user->getEmail());

            /** @var ConstraintViolationList $errors */
            $errors = $this->validator->validate($user);

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
                    territory: $user->getTerritory(),
                )
            );
        }

        $this->io->success(sprintf(
            '%s admin users were successfully reinitialized',
            \count($users)
        ));

        return Command::SUCCESS;
    }
}
