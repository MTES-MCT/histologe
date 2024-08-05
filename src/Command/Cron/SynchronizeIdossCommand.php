<?php

namespace App\Command\Cron;

use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Entity\Suivi;
use App\Entity\User;
use App\Manager\SuiviManager;
use App\Repository\AffectationRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\Idoss\IdossService;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:synchronize-idoss',
    description: 'Update idoss status and upload files'
)]
class SynchronizeIdossCommand extends AbstractCronCommand
{
    private SymfonyStyle $io;
    private array $errors = [];
    private array $partners;
    private User $adminUser;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SignalementRepository $signalementRepository,
        private readonly AffectationRepository $affectationRepository,
        private readonly UserRepository $userRepository,
        private readonly SuiviManager $suiviManager,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        private readonly IdossService $idossService,
        #[Autowire(env: 'FEATURE_IDOSS_ENABLE')]
        private bool $featureIdossEnable,
    ) {
        parent::__construct($this->parameterBag);
        $this->partners = $this->entityManager->getRepository(Partner::class)->findBy(['isIdossActive' => true]);
        $this->adminUser = $this->userRepository->findOneBy(['email' => $this->parameterBag->get('user_system_email')]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        if (!$this->featureIdossEnable) {
            $this->io->warning('Feature "FEATURE_IDOSS_ENABLE" is disabled.');

            return Command::SUCCESS;
        }

        $nbStatusUpdated = 0;
        foreach ($this->partners as $partner) {
            $jobEvent = $this->idossService->listStatuts($partner);
            $nbStatusUpdated += $this->updateStatusFromJobEvent($jobEvent, $partner);
        }
        $this->entityManager->flush();

        $nbFilesUploaded = $this->uploadFilesOnIdoss();
        $this->entityManager->flush();

        $message = '';
        foreach ($this->errors as $error) {
            $this->io->error($error);
            $message .= $error.' - ';
        }
        if ($nbStatusUpdated) {
            $message .= 'Statut mis à jour: '.$nbStatusUpdated.' - ';
        }
        if ($nbFilesUploaded) {
            $message .= 'Fichiers uploadés: '.$nbFilesUploaded.' - ';
        }

        if ('' !== $message) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CRON,
                    to: $this->parameterBag->get('admin_email'),
                    message: $message,
                    cronLabel: 'Synchronisation IDOSS',
                )
            );
        }

        $this->io->success(sprintf('Status updated: %d, Files uploaded: %d', $nbStatusUpdated, $nbFilesUploaded));

        return Command::SUCCESS;
    }

    private function updateStatusFromJobEvent(JobEvent $jobEvent, Partner $partner): int
    {
        $nbStatusUpdated = 0;
        if (JobEvent::STATUS_SUCCESS === $jobEvent->getStatus()) {
            $items = json_decode($jobEvent->getResponse(), true);
            foreach ($items['statuts'] as $item) {
                $signalement = $this->signalementRepository->findOneBy(['reference' => $item['uuid'], 'territory' => $partner->getTerritory()]);
                if (!$signalement) {
                    $this->errors[] = sprintf('Signalement "%s" not found on territory "%s"', $item['uuid'], $partner->getTerritory()->getZip());
                    continue;
                }
                $idossData = $signalement->getSynchroData(IdossService::TYPE_SERVICE);
                if (!$idossData) {
                    $this->errors[] = sprintf('Signalement "%s" has no synchro idoss data on territory "%s"', $item['uuid'], $partner->getTerritory()->getZip());
                    continue;
                }
                if ($idossData['id'] != $item['id']) {
                    $this->errors[] = sprintf('Signalement "%s" has not the expected idoss id "%s" on territory "%s"', $item['uuid'], $item['id'], $partner->getTerritory()->getZip());
                    continue;
                }
                $idossData['updated_at'] = $jobEvent->getCreatedAt()->format('Y-m-d H:i:s');
                $idossData['updated_job_event_id'] = $jobEvent->getId();
                $idossData['updated_status'] = $item['statut'];
                if ($item['motif']) {
                    $idossData['motif'] = $item['motif'];
                }
                $signalement->setSynchroData($idossData, IdossService::TYPE_SERVICE);
                $affectation = $this->affectationRepository->findOneBy(['signalement' => $signalement, 'partner' => $jobEvent->getPartnerId()]);
                if ($affectation && $affectation->getStatut() != IdossService::MAPPING_STATUS[$item['statut']]) {
                    $affectation->setStatut(IdossService::MAPPING_STATUS[$item['statut']]);
                    switch ($item['statut']) {
                        case IdossService::STATUS_ACCEPTED:
                        case IdossService::STATUS_IN_PROGRESS:
                            $description = 'Le signalement a été accepté par IDOSS';
                            break;
                        case IdossService::STATUS_CLOSED:
                            $description = 'Le signalement a été clôturé par IDOSS avec le motif suivant : "'.$item['motif'].'"';
                            break;
                        default:
                            $description = 'Le signalement a été mis à jour ("'.$item['statut'].'") par IDOSS';
                    }
                    $params = [
                        'domain' => IdossService::TYPE_SERVICE,
                        'action' => 'synchronize',
                        'description' => $description,
                        'name_partner' => $affectation->getPartner()->getNom(),
                        'type' => Suivi::TYPE_TECHNICAL,
                    ];
                    $suivi = $this->suiviManager->createSuivi(
                        user: $this->adminUser,
                        signalement: $signalement,
                        params: $params,
                    );
                    $this->entityManager->persist($suivi);
                }
                ++$nbStatusUpdated;
            }
        }

        return $nbStatusUpdated;
    }

    private function uploadFilesOnIdoss(): int
    {
        $nbFilesUploaded = 0;
        foreach ($this->partners as $partner) {
            $signalements = $this->signalementRepository->findSignalementsWithFilesToUploadOnIdoss($partner);
            foreach ($signalements as $signalement) {
                $jobEvent = $this->idossService->uploadFiles($partner, $signalement);
                if ($jobEvent && JobEvent::STATUS_FAILED === $jobEvent->getStatus()) {
                    $this->errors[] = sprintf('Error while uploading files for signalement "%s"', $signalement->getUuid());
                    continue;
                }
                $nbFilesUploaded += \count($signalement->getFiles());
            }
        }

        return $nbFilesUploaded;
    }
}
