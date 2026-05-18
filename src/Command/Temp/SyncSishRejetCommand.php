<?php

namespace App\Command\Temp;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\PartnerType;
use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\Response\DossierStateSISHResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;

#[AsCommand(
    name: 'app:sync-sish-rejet',
    description: '[SISH] Resynchronize SISH dossiers rejected after 06/05/2026',
)]
class SyncSishRejetCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EsaboraManager $esaboraManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Recherche des dossiers SISH rejetés...');
        $io->text('Veuillez patienter...');

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('j')
            ->from(JobEvent::class, 'j')
            ->innerJoin(Signalement::class, 's', 'ON', 'j.signalementId = s.id')
            ->innerJoin(Partner::class, 'p', 'ON', 'j.partnerId = p.id')
            ->innerJoin(Affectation::class, 'a', 'ON', 'a.signalement = s AND a.partner = p')
            ->where('j.createdAt > :date')
            ->andWhere('j.partnerType = :partnerType')
            ->andWhere('j.response LIKE :rejet')
            ->andWhere('a.statut <> :statutRefuse')
            ->setParameter('date', '2026-05-06')
            ->setParameter('partnerType', PartnerType::ARS)
            ->setParameter('rejet', '%Rejet%')
            ->setParameter('statutRefuse', AffectationStatus::REFUSED);

        $jobEvents = $queryBuilder->getQuery()->getResult();

        $countProcessed = 0;
        $countIgnored = 0;
        $countError = 0;

        /** @var JobEvent $jobEvent */
        foreach ($jobEvents as $jobEvent) {
            try {
                $signalement = $this->entityManager->getRepository(Signalement::class)->find($jobEvent->getSignalementId());
                $partner = $this->entityManager->getRepository(Partner::class)->find($jobEvent->getPartnerId());

                if (!$signalement || !$partner) {
                    $io->error(sprintf(
                        'Signalement %s ou Partner %s non trouvé pour JobEvent %s',
                        $jobEvent->getSignalementId(),
                        $jobEvent->getPartnerId(), $jobEvent->getId()
                    ));
                    ++$countError;
                    continue;
                }

                $affectation = $this->entityManager->getRepository(Affectation::class)->findOneBy([
                    'signalement' => $signalement,
                    'partner' => $partner,
                ]);

                if (!$affectation) {
                    $io->warning(sprintf(
                        'Affectation non trouvée pour Signalement %s et Partner %s',
                        $signalement->getUuid(),
                        $partner->getNom()
                    ));
                    ++$countIgnored;
                    continue;
                }

                $responseData = json_decode($jobEvent->getResponse(), true);
                if (null === $responseData) {
                    $io->error(sprintf('Impossible de décoder la réponse pour JobEvent %s', $jobEvent->getId()));
                    ++$countError;
                    continue;
                }

                $dossierStateSISHResponse = new DossierStateSISHResponse($responseData, Response::HTTP_OK);
                if (AbstractEsaboraService::hasSuccess($dossierStateSISHResponse)) {
                    $this->esaboraManager->synchronizeAffectationFrom($dossierStateSISHResponse, $affectation);
                    $io->success(sprintf(
                        'Dossier %s (Affectation %s) traité avec succès.',
                        $signalement->getUuid(),
                        $affectation->getId()
                    ));
                    ++$countProcessed;
                } else {
                    $io->error(sprintf('Erreur lors du traitement du JobEvent %s avec SasEtat = %s',
                        $jobEvent->getId(),
                        $dossierStateSISHResponse->getSasEtat()
                    ));
                    ++$countError;
                }
            } catch (\Throwable $exception) {
                $io->error(sprintf(
                    'Erreur lors du traitement du JobEvent %s : %s',
                    $jobEvent->getId(),
                    $exception->getMessage()
                ));
                ++$countError;
            }
        }

        $io->section('Compte rendu');
        $io->table(
            ['Dossiers traités', 'Dossiers ignorés', 'Erreurs'],
            [[$countProcessed, $countIgnored, $countError]]
        );

        return Command::SUCCESS;
    }
}
