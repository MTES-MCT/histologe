<?php

namespace App\Command;

use App\Entity\Partner;
use App\Entity\Territory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
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
        'IS_COMMUNE' => 'is_commune',
        'INSEE' => 'insee',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(self::FIELDS['TERRITORY'], InputArgument::REQUIRED, 'The territory of the partner')
            ->addArgument(self::FIELDS['NAME'], InputArgument::REQUIRED, 'The name of the partner')
            ->addArgument(self::FIELDS['EMAIL'], InputArgument::REQUIRED)
            ->addArgument(self::FIELDS['IS_COMMUNE'], InputArgument::OPTIONAL, 'Is the partner a commune ?')
            ->addArgument(self::FIELDS['INSEE'], InputArgument::IS_ARRAY, 'Add insee code');
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
            ' $ php bin/console app:add-partner territory nom email is_commune insee',
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

        $isCommune = $input->getArgument(self::FIELDS['IS_COMMUNE']);
        if (null !== $isCommune) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['IS_COMMUNE']).'</info>: '.$isCommune);
        } else {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Is the partner a commune ?',
                ['yes', 'no'],
                'no',
            );

            $isCommune = $helper->ask($input, $output, $question);
            $this->io->text(' > <info> You have just selected: </info>'.$isCommune);
            $input->setArgument(self::FIELDS['IS_COMMUNE'], 'yes' === $isCommune ? true : false);
        }

        if ($input->getArgument(self::FIELDS['IS_COMMUNE'])) {
            $insee = $input->getArgument(self::FIELDS['INSEE']);
            if (!empty($insee)) {
                $this->io->table([self::FIELDS['INSEE']], $insee);
            } else {
                $helper = $this->getHelper('question');
                $question = new Question('Enter code'.self::FIELDS['INSEE'].' separated by space ? ');
                $insee = $helper->ask($input, $output, $question);

                $inseeList = explode(' ', $insee);
                $input->setArgument(self::FIELDS['INSEE'], $inseeList);
                $this->io->table([strtoupper(self::FIELDS['INSEE'])], [$inseeList]);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $territory = $input->getArgument('territory');
        $name = $input->getArgument('name');
        $email = $input->getArgument('email');
        $isCommune = $input->getArgument('is_commune');
        $insee = (array) $input->getArgument('insee');

        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => $territory]);

        if (!$territory instanceof Territory) {
            $this->io->error('Territory does not exists');

            return Command::FAILURE;
        }

        $partner = (new Partner())
            ->setTerritory($territory)
            ->setNom($name)
            ->setEmail(mb_strtolower($email))
            ->setIsCommune($isCommune)
            ->setInsee($insee);

        $errors = $this->validator->validate($partner);

        if (\count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $constraint) {
                $property = $constraint->getPropertyPath();
                $errorMessages[$property][] = $constraint->getMessage();
            }
            foreach ($errorMessages as $key => $errorMessage) {
                $this->io->error(sprintf('%s : %s', $key, implode(',', $errorMessage)));
            }

            return Command::FAILURE;
        }

        $this->entityManager->persist($partner);
        $this->entityManager->flush();

        $this->io->success(sprintf('%s was successfully created: %s',
            $partner->getNom(),
            $partner->getTerritory()?->getName()
        ));

        $this->entityManager->persist($partner);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
