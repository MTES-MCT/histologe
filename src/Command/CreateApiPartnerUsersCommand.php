<?php

namespace App\Command;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Factory\PartnerFactory;
use App\Factory\UserFactory;
use App\Manager\PartnerManager;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Validator\EmailFormatValidator;
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

        if ($input->getOption('partner_id')
            && (
                $input->getOption('zip')
                || $input->getOption('partner_name')
                || $input->getOption('bo_email')
            )
        ) {
            $io->error('Too many options, you cannot use the --partner_id option with the --zip, --partner_name or --bo_email options.');

            return Command::FAILURE;
        }

        $apiEmail = $input->getOption('api_email');

        if (empty($apiEmail) || !EmailFormatValidator::validate($apiEmail)) {
            $io->error('API e-mail is empty or not valid.');

            return Command::INVALID;
        }

        $foundUserApi = $this->userRepository->findOneBy([
            'email' => $apiEmail,
        ]);
        if (!empty($foundUserApi)) {
            $io->error('User already exists with API e-mail');

            return Command::INVALID;
        }

        $partnerId = $input->getOption('partner_id');
        if (!empty($partnerId)) {
            $partner = $this->partnerRepository->findOneBy(['id' => $partnerId]);
        }

        // Testing purpose: possibility to create partner and BO user, and get partner ID
        if ('histologe' !== getenv('APP')) {
            $zip = $input->getOption('zip');
            $partnerName = $input->getOption('partner_name');
            $boEmail = $input->getOption('bo_email');

            if (!empty($zip) && !empty($partnerName) && !empty($boEmail)) {
                if (!EmailFormatValidator::validate($boEmail)) {
                    $io->error('BO e-mail is not valid.');

                    return Command::INVALID;
                }

                $foundUserBo = $this->userRepository->findOneBy([
                    'email' => $boEmail,
                ]);
                if (!empty($foundUserBo)) {
                    $io->error('User already exists with BO e-mail');

                    return Command::INVALID;
                }

                $territory = $this->territoryRepository->findOneBy(['zip' => $zip]);
                if (empty($territory)) {
                    $io->error('No territory was found with zip '.$zip);

                    return Command::INVALID;
                }

                $existingPartner = $this->partnerRepository->findOneBy([
                    'territory' => $territory,
                    'nom' => $partnerName,
                ]);
                if (!empty($existingPartner)) {
                    $io->error('Another partner exists with this name on this territory.');

                    return Command::INVALID;
                }

                $partner = $this->partnerFactory->createInstanceFrom(
                    territory: $territory,
                    name: $partnerName,
                    email: null,
                    type: PartnerType::ARS
                );
                $this->partnerManager->persist($partner);
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
                $boUser->setStatut(UserStatus::ACTIVE)->setPassword($passwordHashed);

                $boUserPartner = (new UserPartner())->setPartner($partner)->setUser($boUser);
                $boUser->addUserPartner($boUserPartner);

                $this->userManager->persist($boUserPartner);
                $this->userManager->persist($boUser);

                $io->success('BO account was created for '.$boEmail.' with password '.$password);
            }
        }

        if (empty($partner)) {
            if (empty($partnerId)) {
                $io->error('Missing optional argument: we need either a partner ID or a set of zip, partner name, bo e-mail');

                return Command::INVALID;
            }
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
        $user->setStatut(UserStatus::ACTIVE)->setPassword($passwordHashed);

        $userPartner = (new UserPartner())->setPartner($partner)->setUser($user);
        $user->addUserPartner($userPartner);

        $this->userManager->persist($userPartner);
        $this->userManager->save($user);

        $io->success(sprintf(
            'API account was created for %s with password %s.'.\PHP_EOL.
            'Please send the password securely via https://vaultwarden.incubateur.net/#/login'.\PHP_EOL.
            'Documentation: https://github.com/MTES-MCT/histologe/wiki/API-Signal-Logement',
            $apiEmail,
            $password,
        ));

        return Command::SUCCESS;
    }
}
