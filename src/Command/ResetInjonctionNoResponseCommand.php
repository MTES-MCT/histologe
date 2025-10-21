<?php

namespace App\Command;

use App\Command\Cron\AbstractCronCommand;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:reset-injonction-no-response',
    description: 'After weeks without response, the status should automatically change to "NEED_VALIDATION"')]
class ResetInjonctionNoResponseCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly SignalementRepository $signalementRepository,
        private readonly SuiviManager $suiviManager,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(env: 'INJONCTION_PERIOD_THRESHOLD')]
        private readonly string $periodThreshold,
    ) {
        parent::__construct($this->parameterBag);
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $signalements = $this->signalementRepository->findInjonctionBeforePeriod($this->periodThreshold);

        foreach ($signalements as $signalement) {
            $signalement->setStatut(SignalementStatus::NEED_VALIDATION);
            $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: 'La procédure d’injonction a expiré pour le bailleur. Le signalement est désormais en attente de validation.',
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::INJONCTION_BAILLEUR_EXPIREE,
                isPublic: true
            );
            $this->entityManager->persist($signalement);
            $output->writeln(sprintf('#%s updated', $signalement->getUuid()));
        }

        $this->entityManager->flush();
        $countSignalement = count($signalements);
        if (count($signalements) > 0) {
            $io->success(\sprintf('%s signalements sont désormais en attente de validation', $countSignalement));
        } else {
            $io->warning('Aucun signalement en injonction n’a expiré');
        }

        return Command::SUCCESS;
    }
}
