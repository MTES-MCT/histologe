<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-signalement-usager',
    description: 'Create declarant and occupant usager for a signalement, affect unaffected suivis if needed'
)]
class CreateSignalementUsagerCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserManager $userManager,
        private SuiviManager $suiviManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('signalement', InputArgument::REQUIRED, 'Signalement uuid for which we want to create usager (declarant/occupant)');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $signalementUuid = $input->getArgument('signalement');
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => $signalementUuid]);
        if (null === $signalement) {
            $this->io->error('Signalement does not exists');

            return Command::FAILURE;
        }

        $userOccupant = $this->userManager->createUsagerFromSignalement($signalement, UserManager::OCCUPANT);
        $userDeclarant = $this->userManager->createUsagerFromSignalement($signalement, UserManager::DECLARANT);

        if ($userOccupant) {
            $this->io->success($userOccupant->getEmail().' occupant created or already existing');
        }
        if ($userDeclarant) {
            $this->io->success($userDeclarant->getEmail().' declarant created or already existing');
        }

        $unaffectedSuivis = $this->suiviManager->getRepository()->findBy([
            'signalement' => $signalement,
            'createdBy' => null,
        ]);

        if (\count($unaffectedSuivis) > 0) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Do you want to affect unaffected suivis ?',
                ['no', 'yes'],
                'yes',
            );

            $affectSuivis = $helper->ask($input, $output, $question);
            $this->io->text(' > <info> You have just selected: </info>'.$affectSuivis);

            if ('yes' == $affectSuivis) {
                $question = 'Do you want to affect this suivi';
                $answers = [];

                if ($userOccupant) {
                    $question .= ' to occupant';
                    $answers[] = UserManager::OCCUPANT;
                }
                if ($userDeclarant) {
                    $question .= ' or to declarant';
                    $answers[] = UserManager::DECLARANT;
                }

                $question .= ' ?';
                $answers[] = 'no';

                foreach ($unaffectedSuivis  as $unaffectedSuivi) {
                    $this->io->text('Unaffected suivi created at '.$unaffectedSuivi->getCreatedAt()->format('d/m/Y H:m'));
                    $this->io->text('"'.$unaffectedSuivi->getDescription().'"');

                    $questionSuivi = new ChoiceQuestion(
                        $question,
                        $answers,
                        'no',
                    );
                    $affectSuivi = $helper->ask($input, $output, $questionSuivi);

                    if ('occupant' == $affectSuivi) {
                        $this->suiviManager->updateSuiviCreatedByUser($unaffectedSuivi, $userOccupant);
                        $this->io->text(' > <info> You have just affected to OCCUPANT this suivi</info>');
                    } elseif ('declarant' == $affectSuivi) {
                        $this->suiviManager->updateSuiviCreatedByUser($unaffectedSuivi, $userDeclarant);
                        $this->io->text(' > <info> You have just affected to DECLARANT this suivi</info>');
                    }
                }
                $this->io->text('FIN ');
            }
        }

        return Command::SUCCESS;
    }
}
