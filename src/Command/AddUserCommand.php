<?php

namespace App\Command;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\EventSubscriber\UserCreatedSubscriber;
use App\Factory\UserFactory;
use App\Manager\PartnerManager;
use App\Manager\TerritoryManager;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:add-user',
    description: 'Create a user'
)]
class AddUserCommand extends Command
{
    private SymfonyStyle $io;

    private const FIELDS = [
        'ROLE' => 'role',
        'EMAIL' => 'email',
        'FIRSTNAME' => 'firstname',
        'LASTNAME' => 'lastname',
        'PARTNER' => 'partner',
        'TERRITORY' => 'territory',
    ];

    public const ROLES = [
        'ROLE_USER_PARTNER',
        'ROLE_ADMIN_PARTNER',
        'ROLE_ADMIN_TERRITORY',
        'ROLE_ADMIN',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserRepository $users,
        private UserPasswordHasherInterface $hasher,
        private UserFactory $userFactory,
        private UserManager $userManager,
        private PartnerManager $partnerManager,
        private TerritoryManager $territoryManager,
        private UserCreatedSubscriber $userAddedSubscriber,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(self::FIELDS['ROLE'], InputArgument::REQUIRED, 'The role of the new user')
            ->addArgument(self::FIELDS['EMAIL'], InputArgument::REQUIRED, 'The email of the new user')
            ->addArgument(self::FIELDS['FIRSTNAME'], InputArgument::REQUIRED, 'The firstname of the new user')
            ->addArgument(self::FIELDS['LASTNAME'], InputArgument::REQUIRED, 'The lastname of the new user')
            ->addArgument(self::FIELDS['PARTNER'], InputArgument::OPTIONAL, 'If set, the user will belong partner', Partner::DEFAULT_PARTNER)
            ->addArgument(self::FIELDS['TERRITORY'], InputArgument::OPTIONAL, 'If set, the user will belong territory');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument(self::FIELDS['ROLE']) &&
            null !== $input->getArgument(self::FIELDS['EMAIL']) &&
            null !== $input->getArgument(self::FIELDS['FIRSTNAME']) &&
            null !== $input->getArgument(self::FIELDS['LASTNAME'])
        ) {
            return;
        }

        $this->io->title('Add User Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console app:add-user role email firstname lastname partner territory',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);

        $role = $input->getArgument(self::FIELDS['ROLE']);
        if (null !== $role) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['ROLE']).'</info>: '.$role);
        } else {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Please select a role (default: ROLE_USER_PARTNER)',
                User::ROLES,
                User::ROLES['Utilisateur']
            );

            $role = $helper->ask($input, $output, $question);
            $this->io->text(' > <info> You have just selected: </info>'.$role);
            $input->setArgument(self::FIELDS['ROLE'], $role);
        }

        $email = $input->getArgument(self::FIELDS['EMAIL']);
        if (null !== $email) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['EMAIL']).'</info>: '.$email);
        } else {
            $email = $this->io->ask(ucfirst(self::FIELDS['EMAIL']));
            $input->setArgument(self::FIELDS['EMAIL'], $email);
        }

        $firstname = $input->getArgument(self::FIELDS['FIRSTNAME']);
        if (null !== $firstname) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['FIRSTNAME']).'</info>: '.$firstname);
        } else {
            $firstname = $this->io->ask(ucfirst(self::FIELDS['FIRSTNAME']));
            $input->setArgument(self::FIELDS['FIRSTNAME'], $firstname);
        }

        $lastname = $input->getArgument(self::FIELDS['LASTNAME']);
        if (null !== $lastname) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['LASTNAME']).'</info>: '.$lastname);
        } else {
            $lastname = $this->io->ask(ucfirst(self::FIELDS['LASTNAME']));
            $input->setArgument(self::FIELDS['LASTNAME'], $lastname);
        }

        $partner = $this->io->ask(ucfirst(self::FIELDS['PARTNER']));
        $input->setArgument(self::FIELDS['PARTNER'], $partner);

        $territory = $input->getArgument(self::FIELDS['TERRITORY']);
        if (null !== $territory) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['TERRITORY']).'</info>: '.$territory);
        } else {
            $territory = $this->io->ask(ucfirst(self::FIELDS['TERRITORY']));
            $input->setArgument(self::FIELDS['TERRITORY'], $territory);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->entityManager->getEventManager()->removeEventSubscriber($this->userAddedSubscriber);
        $partner = $input->getArgument('partner');
        $territory = $input->getArgument('territory');

        if (empty($partner) && empty($territory)) {
            $this->io->error('Partner or territory is missing');

            return Command::FAILURE;
        }

        $partner = $this->partnerManager->findOneBy(['nom' => $partner]);

        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => $territory]);

        /** @var User $user */
        $user = $this->userManager->findOneBy(['email' => $input->getArgument('email')]);

        if (null !== $user && \in_array('ROLE_USAGER', $user->getRoles())) {
            $this->io->text(' > <info>'.$input->getArgument('email').' existe déjà avec le rôle </info>: '.implode(',', $user->getRoles()));
            $data['nom'] = $input->getArgument('lastname');
            $data['prenom'] = $input->getArgument('firstname');
            $data['roles'] = \in_array($input->getArgument('role'), User::ROLES) ? $input->getArgument('role') : User::ROLES[$input->getArgument('role')];
            $data['email'] = $input->getArgument('email');
            $data['isMailingActive'] = true;
            $data['territory'] = $territory;
            $data['partner'] = $partner;
            $this->userManager->updateUserFromData($user, $data);
        } else {
            $user = $this->userFactory->createInstanceFrom(
                roleLabel: $input->getArgument('role'),
                partner: $partner,
                territory: $territory,
                firstname: $input->getArgument('firstname'),
                lastname: $input->getArgument('lastname'),
                email: $input->getArgument('email')
            );
        }

        $password = $this->hasher->hashPassword($user, 'histologe');

        $user->setPassword($password)->setStatut(User::STATUS_ACTIVE);

        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($user);

        if (\count($errors) > 0) {
            $this->io->error((string) $errors);

            return Command::FAILURE;
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->io->success(sprintf(
            '%s was successfully created: %s',
            $user->getNomComplet(),
            $user->getEmail()
        ));

        return Command::SUCCESS;
    }
}
