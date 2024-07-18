<?php

namespace App\Command;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Territory;
use App\Factory\AutoAffectationRuleFactory;
use App\Manager\AutoAffectationRuleManager;
use App\Manager\TerritoryManager;
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
    name: 'app:add-auto-affectation-rule',
    description: 'Create an AutoAffectationRule'
)]
class AddAutoAffectationRuleCommand extends Command
{
    private SymfonyStyle $io;

    private const FIELDS = [
        'TERRITORY' => 'territory',
        'PARTNER_TYPE' => 'partnerType',
        'STATUS' => 'status',
        'PROFILE_DECLARANT' => 'profileDeclarant',
        'INSEE_TO_INCLUDE' => 'inseeToInclude',
        'INSEE_TO_EXCLUDE' => 'inseeToExclude',
        'PARC' => 'parc',
        'ALLOCATAIRE' => 'allocataire',
    ];

    public function __construct(
        private ValidatorInterface $validator,
        private AutoAffectationRuleFactory $autoAffectationRulerFactory,
        private AutoAffectationRuleManager $autoAffectationRuleManager,
        private TerritoryManager $territoryManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(self::FIELDS['TERRITORY'], InputArgument::REQUIRED, 'The territory of the rule')
            ->addArgument(self::FIELDS['PARTNER_TYPE'], InputArgument::REQUIRED, 'The parner_type concerned')
            ->addArgument(self::FIELDS['STATUS'], InputArgument::OPTIONAL, 'The status of the rule, "ACTIVE" if not specified')
            ->addArgument(self::FIELDS['PROFILE_DECLARANT'], InputArgument::OPTIONAL, 'The profile_declarant concerned, "all" if not specified')
            ->addArgument(self::FIELDS['INSEE_TO_INCLUDE'], InputArgument::OPTIONAL, '"partner_list" if not specified')
            ->addArgument(self::FIELDS['INSEE_TO_EXCLUDE'], InputArgument::OPTIONAL, 'null if not specified')
            ->addArgument(self::FIELDS['PARC'], InputArgument::OPTIONAL, 'Parc concerned, "all" if not specified')
            ->addArgument(self::FIELDS['ALLOCATAIRE'], InputArgument::OPTIONAL, 'allocataire concerned, "all" if not specified');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument(self::FIELDS['TERRITORY']) &&
            null !== $input->getArgument(self::FIELDS['PARTNER_TYPE'])
        ) {
            return;
        }

        $this->io->title('Add AutoAffectationRule Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console app:add-auto-affectation-rule territory partnerType status profileDeclarant inseeToInclude inseeToExclude parc allocataire',
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

        $partnerType = $input->getArgument(self::FIELDS['PARTNER_TYPE']);
        if (null !== $partnerType) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['PARTNER_TYPE']).'</info>: '.$partnerType);
        } else {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'This rule concerns what type of partner ?',
                array_column(PartnerType::cases(), 'name'),
                PartnerType::COMMUNE_SCHS->value,
            );

            $partnerType = $helper->ask($input, $output, $question);
            $this->io->text(' > <info> You have just selected: </info>'.$partnerType);
            $input->setArgument(self::FIELDS['PARTNER_TYPE'], $partnerType);
        }

        $status = $input->getArgument(self::FIELDS['STATUS']);
        if (null !== $status) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['STATUS']).'</info>: '.$status);
        } else {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'What status will this rule have?',
                [
                    AutoAffectationRule::STATUS_ACTIVE,
                    AutoAffectationRule::STATUS_ARCHIVED,
                ],
                AutoAffectationRule::STATUS_ACTIVE,
            );

            $status = $helper->ask($input, $output, $question);
            $this->io->text(' > <info> You have just selected: </info>'.$status);
            $input->setArgument(self::FIELDS['STATUS'], $status);
        }

        $profilDeclarant = $input->getArgument(self::FIELDS['PROFILE_DECLARANT']);
        $profilDeclarantValues = array_column(ProfileDeclarant::cases(), 'name');
        array_unshift($profilDeclarantValues, 'all', 'tiers', 'occupant');
        if (null !== $profilDeclarant) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['PROFILE_DECLARANT']).'</info>: '.$profilDeclarant);
        } else {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'This rule concerns reports created by which declarant profile?',
                $profilDeclarantValues,
                'all',
            );

            $profilDeclarant = $helper->ask($input, $output, $question);
            $this->io->text(' > <info> You have just selected: </info>'.$profilDeclarant);
            $input->setArgument(self::FIELDS['PROFILE_DECLARANT'], $profilDeclarant);
        }

        $inseeToInclude = $input->getArgument(self::FIELDS['INSEE_TO_INCLUDE']);
        if (!empty($inseeToInclude)) {
            $this->io->table([self::FIELDS['INSEE_TO_INCLUDE']], $inseeToInclude);
        } else {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new Question('Enter code'.self::FIELDS['INSEE_TO_INCLUDE'].' separated by comma, or all or partner_list ');
            $inseeToInclude = $helper->ask($input, $output, $question);
            $input->setArgument(self::FIELDS['INSEE_TO_INCLUDE'], $inseeToInclude);
        }

        $inseeToExclude = $input->getArgument(self::FIELDS['INSEE_TO_EXCLUDE']);
        if (!empty($inseeToExclude)) {
            $this->io->table([self::FIELDS['INSEE_TO_EXCLUDE']], $inseeToExclude);
        } else {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new Question('Enter code'.self::FIELDS['INSEE_TO_EXCLUDE'].' separated by comma ');
            $inseeToExclude = $helper->ask($input, $output, $question);
            $input->setArgument(self::FIELDS['INSEE_TO_EXCLUDE'], $inseeToExclude);
        }

        $parc = $input->getArgument(self::FIELDS['PARC']);
        if (null !== $parc) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['PARC']).'</info>: '.$parc);
        } else {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'This rule concerns which housing stock, private, public or all?',
                [
                    'all',
                    'prive',
                    'public',
                    'non_renseigne',
                ],
                'all',
            );

            $parc = $helper->ask($input, $output, $question);
            $this->io->text(' > <info> You have just selected: </info>'.$parc);
            $input->setArgument(self::FIELDS['PARC'], $parc);
        }

        $allocataire = $input->getArgument(self::FIELDS['ALLOCATAIRE']);
        if (null !== $allocataire) {
            $this->io->text(' > <info>'.ucfirst(self::FIELDS['ALLOCATAIRE']).'</info>: '.$allocataire);
        } else {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'This rule concerns which household situation (allocataire)?',
                [
                    'all',
                    'non',
                    'oui',
                    'caf',
                    'msa',
                ],
                'all',
            );

            $allocataire = $helper->ask($input, $output, $question);
            $this->io->text(' > <info> You have just selected: </info>'.$allocataire);
            $input->setArgument(self::FIELDS['ALLOCATAIRE'], $allocataire);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $territory = $input->getArgument('territory');
        $partnerType = $input->getArgument('partnerType');
        $status = $input->getArgument('status');
        $profileDeclarant = $input->getArgument('profileDeclarant');
        $inseeToInclude = $input->getArgument('inseeToInclude');
        $inseeToExclude = $input->getArgument('inseeToExclude');
        $parc = $input->getArgument('parc');
        $allocataire = $input->getArgument('allocataire');

        $territory = $this->territoryManager->findOneBy(['zip' => $territory]);

        if (!$territory instanceof Territory) {
            $this->io->error('Territory does not exists');

            return Command::FAILURE;
        }

        $autoAffectationRule = $this->autoAffectationRuleManager->findOneBy(['territory' => $territory, 'partnerType' => $partnerType]);
        if ($autoAffectationRule) {
            $this->io->error('There is already a rule for this territory and this type of partner');

            return Command::FAILURE;
        }

        $autoAffectationRule = $this->autoAffectationRulerFactory->createInstanceFrom(
            territory : $territory,
            status : $status,
            partnerType : PartnerType::tryFrom($partnerType),
            profileDeclarant : $profileDeclarant,
            inseeToInclude : $inseeToInclude,
            inseeToExclude : $inseeToExclude,
            parc : $parc,
            allocataire : $allocataire
        );

        $errors = $this->validator->validate($autoAffectationRule);

        if (\count($errors) > 0) {
            $this->showErrors($errors);

            return Command::FAILURE;
        }

        $this->autoAffectationRuleManager->save($autoAffectationRule);

        $this->io->success(sprintf(
            'New rule was successfully created for territory %s and partner %s',
            $autoAffectationRule->getTerritory()?->getName(),
            $autoAffectationRule->getPartnerType()->value
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
