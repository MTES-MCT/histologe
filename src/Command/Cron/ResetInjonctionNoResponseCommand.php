<?php

namespace App\Command\Cron;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Service\InjonctionBailleur\InjonctionBailleurService;
use App\Service\Signalement\AutoAssigner;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;

#[AsCommand(
    name: 'app:reset-injonction-no-response',
    description: 'After weeks without response, the status should automatically change to "NEED_VALIDATION"')]
class ResetInjonctionNoResponseCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly SignalementRepository $signalementRepository,
        private readonly SuiviManager $suiviManager,
        private readonly AutoAssigner $autoAssigner,
        private readonly UserManager $userManager,
        #[Autowire(env: 'INJONCTION_PERIOD_THRESHOLD')]
        private readonly string $periodThreshold,
        private readonly InjonctionBailleurService $injonctionBailleurService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($this->parameterBag);
    }

    /**
     * @throws \Exception
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $signalements = $this->signalementRepository->findInjonctionBeforePeriodWithoutAnswer($this->periodThreshold);
        foreach ($signalements as $signalement) {
            $this->entityManager->beginTransaction();
            try {
                $this->injonctionBailleurService->switchFromInjonctionToProcedure($signalement);
                $this->suiviManager->createSuivi(
                    signalement: $signalement,
                    description: 'La procédure d’injonction a expiré pour le bailleur. Le signalement est désormais en attente de validation.',
                    type: Suivi::TYPE_AUTO,
                    category: SuiviCategory::INJONCTION_BAILLEUR_EXPIREE,
                    user: $this->userManager->getSystemUser(),
                    isPublic: true
                );
                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
                $io->error($e->getMessage());

                return Command::FAILURE;
            }

            $this->autoAssigner->assignOrSendNewSignalementNotification($signalement);

            $output->writeln(sprintf('#%s updated', $signalement->getUuid()));
        }

        $countSignalement = count($signalements);
        if (count($signalements) > 0) {
            $io->success(\sprintf(
                '%s signalements ont basculé de la procédure d\'injonction à la procédure classique.',
                $countSignalement
            ));
        } else {
            $io->warning('Aucun signalement en injonction n’a expiré');
        }

        return Command::SUCCESS;
    }
}
