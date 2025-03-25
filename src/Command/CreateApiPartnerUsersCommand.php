<?php

namespace App\Command;

use App\Entity\Enum\PartnerType;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Factory\PartnerFactory;
use App\Factory\UserFactory;
use App\Manager\PartnerManager;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-api-partner-users',
    description: 'Create new partner and users with API connection',
)]
class CreateApiPartnerUsersCommand extends Command
{
    public function __construct(
        private readonly TerritoryRepository $territoryRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly PartnerFactory $partnerFactory,
        private readonly PartnerManager $partnerManager,
        private readonly UserFactory $userFactory,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserManager $userManager,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('api_email', null, InputOption::VALUE_REQUIRED, 'E-mail of the user with API connection')
            ->addOption('partner_id', null, InputOption::VALUE_OPTIONAL, 'ID of an existing partner (for production purpose)')
            ->addOption('zip', null, InputOption::VALUE_OPTIONAL, 'Territory zip to target (for testing purpose)')
            ->addOption('partner_name', null, InputOption::VALUE_OPTIONAL, 'Name of a new partner (for testing purpose)')
            ->addOption('bo_email', null, InputOption::VALUE_OPTIONAL, 'E-mail of the user with BO connection (for testing purpose)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $apiEmail = $input->getOption('api_email');

        if (empty($apiEmail)) {
            $io->warning('Missing required arguments');

            return Command::INVALID;
        }

        $foundUserApi = $this->userRepository->findOneBy([
            'email' => $apiEmail,
            'statut' => User::STATUS_ACTIVE,
        ]);
        if (!empty($foundUserApi)) {
            $io->warning('User already exists with API e-mail');

            return Command::INVALID;
        }

        $partnerId = $input->getOption('partner_id');
        $zip = $input->getOption('zip');
        $partnerName = $input->getOption('partner_name');
        $boEmail = $input->getOption('bo_email');

        // Testing purpose: create partner and BO user, and get partner ID
        if (!empty($zip) && !empty($partnerName) && !empty($boEmail)) {
            $foundUserBo = $this->userRepository->findOneBy([
                'email' => $boEmail,
                'statut' => User::STATUS_ACTIVE,
            ]);
            if (!empty($foundUserBo)) {
                $io->warning('User already exists with BO e-mail');

                return Command::INVALID;
            }

            $territory = $this->territoryRepository->findOneBy(['zip' => $zip]);
            $partner = $this->partnerFactory->createInstanceFrom(
                territory: $territory,
                name: $partnerName,
                email: null,
                type: PartnerType::ARS
            );
            $this->partnerManager->save($partner);
            $partnerId = $partner->getId();

            $boUser = $this->userFactory->createInstanceFrom(
                roleLabel: 'Agent',
                firstname: 'Agent',
                lastname: 'Test',
                email: $boEmail,
                isMailActive: false,
                isActivateAccountNotificationEnabled: false,
            );
            $password = $this->userManager->getComplexRandomPassword();
            $passwordHashed = $this->hasher->hashPassword($boUser, $password);
            $boUser->setStatut(User::STATUS_ACTIVE)->setPassword($passwordHashed);

            $boUserPartner = (new UserPartner())->setPartner($partner)->setUser($boUser);
            $boUser->addUserPartner($boUserPartner);

            $this->userManager->persist($boUserPartner);
            $this->userManager->save($boUser);

            $io->success('BO account was created for '.$boEmail.' with password '.$password);
        } elseif (empty($partnerId)) {
            $io->warning('Missing optional argument: we need either a partner ID or a set of zip, partner name, bo e-mail');

            return Command::INVALID;
        }

        if (empty($partner)) {
            $partner = $this->partnerRepository->findOneBy(['id' => $partnerId]);
        }

        if (empty($partner)) {
            $io->error('No partner found');

            return Command::FAILURE;
        }

        $user = $this->userFactory->createInstanceFrom(
            roleLabel: 'API',
            firstname: 'API',
            lastname: 'Test',
            email: $apiEmail,
            isMailActive: false,
            isActivateAccountNotificationEnabled: false,
        );
        $password = $this->userManager->getComplexRandomPassword();
        $passwordHashed = $this->hasher->hashPassword($user, $password);
        $user->setStatut(User::STATUS_ACTIVE)->setPassword($passwordHashed);

        $userPartner = (new UserPartner())->setPartner($partner)->setUser($user);
        $user->addUserPartner($userPartner);

        $this->userManager->persist($userPartner);
        $this->userManager->save($user);

        $io->success('API account was created for '.$apiEmail.' with password '.$password);

        return Command::SUCCESS;
    }
}
