<?php

namespace App\Command;

use App\Entity\Enum\PartnerType;
use App\Entity\Territory;
use App\Factory\PartnerFactory;
use App\Manager\PartnerManager;
use App\Manager\TerritoryManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:add-partner',
    description: 'Create a partner'
)]
class AddPartnerCommand extends Command
{
    private SymfonyStyle $io;

    private const FIELDS = [
        'TERRITORY' => 'territory',
        'NAME' => 'name',
        'EMAIL' => 'email',
        'INSEE' => 'insee',
        'TYPE' => 'type',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private PartnerFactory $partnerFactory,
        private PartnerManager $partnerManager,
        private TerritoryManager $territoryManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(self::FIELDS['TERRITORY'], InputArgument::REQUIRED, 'The territory of the partner')
            ->addArgument(self::FIELDS['NAME'], InputArgument::REQUIRED, 'The name of the partner')
            ->addArgument(self::FIELDS['EMAIL'], InputArgument::REQUIRED)
            ->addArgument(self::FIELDS['INSEE'], InputArgument::OPTIONAL, 'Add insee code separated with comma')
            ->addArgument(self::FIELDS['TYPE'], InputArgument::OPTIONAL, 'Type of partner');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument(self::FIELDS['TERRITORY']) &&
            null !== $input->getArgument(self::FIELDS['NAME']) &&
            null !== $input->getArgument(self::FIELDS['EMAIL'])
        ) {
            return;
        }

        $this->io->title('Add Partner Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console app:add-partner territory nom email type insee',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);

        $territory = $input->getArgument(self::FIELDS['TERRITORY']);
        if (null !== $territory) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['TERRITORY']).'</info>: '.$territory);
        } else {
            $territory = $this->io->ask(ucfirst(self::FIELDS['TERRITORY']));
            $input->setArgument(self::FIELDS['TERRITORY'], $territory);
        }

        $firstname = $input->getArgument(self::FIELDS['NAME']);
        if (null !== $firstname) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['NAME']).'</info>: '.$firstname);
        } else {
            $firstname = $this->io->ask(ucfirst(self::FIELDS['NAME']));
            $input->setArgument(self::FIELDS['NAME'], $firstname);
        }

        $email = $input->getArgument(self::FIELDS['EMAIL']);
        if (null !== $email) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['EMAIL']).'</info>: '.$email);
        } else {
            $email = $this->io->ask(ucfirst(self::FIELDS['EMAIL']));
            $input->setArgument(self::FIELDS['EMAIL'], $email);
        }

        $type = $input->getArgument(self::FIELDS['TYPE']);
        if (null !== $type) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['TYPE']).'</info>: '.$type);
        } else {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Which type is the partner',
                [
                    'ADIL',
                    'ARS',
                    'ASSOCIATION',
                    'BAILLEUR_SOCIAL',
                    'CAF_MSA',
                    'CCAS',
                    'COMMUNE_SCHS',
                    'CONCILIATEURS',
                    'CONSEIL_DEPARTEMENTAL',
                    'DDETS',
                    'DDT_M',
                    'DISPOSITIF_RENOVATION_HABITAT',
                    'EPCI',
                    'OPERATEUR_VISITES_ET_TRAVAUX',
                    'POLICE_GENDARMERIE',
                    'PREFECTURE',
                    'TRIBUNAL',
                    'AUTRE',
                ],
                'ADIL',
            );

            $type = $helper->ask($input, $output, $question);
            $this->io->text(' > <info> You have just selected: </info>'.$type);
            $input->setArgument(self::FIELDS['TYPE'], $type);
        }

        if ($input->getArgument(self::FIELDS['TYPE'])) {
            $insee = $input->getArgument(self::FIELDS['INSEE']);
            if (!empty($insee)) {
                $this->io->table([self::FIELDS['INSEE']], $insee);
            } else {
                /** @var QuestionHelper $helper */
                $helper = $this->getHelper('question');
                $question = new Question('Enter code'.self::FIELDS['INSEE'].' separated by comma ? ');
                $insee = $helper->ask($input, $output, $question);
                $input->setArgument(self::FIELDS['INSEE'], $insee);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $territory = $input->getArgument('territory');
        $name = $input->getArgument('name');
        $email = $input->getArgument('email');
        $type = $input->getArgument('type');
        $insee = $input->getArgument('insee');
        $territory = $this->territoryManager->findOneBy(['zip' => $territory]);

        if (!$territory instanceof Territory) {
            $this->io->error('Territory does not exists');

            return Command::FAILURE;
        }

        $partner = $this->partnerFactory->createInstanceFrom($territory, $name, $email, PartnerType::tryFrom($type), $insee);
        $errors = $this->validator->validate($partner);

        if (\count($errors) > 0) {
            $this->showErrors($errors);

            return Command::FAILURE;
        }

        $this->partnerManager->save($partner);

        $this->io->success(sprintf(
            '%s was successfully created: %s',
            $partner->getNom(),
            $partner->getTerritory()?->getName()
        ));

        return Command::SUCCESS;
    }

    private function showErrors(ConstraintViolationList $errors): void
    {
        $errorMessages = [];
        foreach ($errors as $constraint) {
            $property = $constraint->getPropertyPath();
            $errorMessages[$property][] = $constraint->getMessage();
        }
        foreach ($errorMessages as $key => $errorMessage) {
            $this->io->error(sprintf('%s : %s', $key, implode(',', $errorMessage)));
        }
    }
}
