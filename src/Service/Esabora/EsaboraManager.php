<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\InterfacageType;
use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Entity\User;
use App\Event\InterventionCreatedEvent;
use App\Factory\InterventionFactory;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Repository\InterventionRepository;
use App\Service\Esabora\Enum\EsaboraStatus;
use App\Service\Esabora\Response\DossierResponseInterface;
use App\Service\Esabora\Response\Model\DossierArreteSISH;
use App\Service\Esabora\Response\Model\DossierVisiteSISH;
use App\Service\Intervention\InterventionDescriptionGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EsaboraManager
{
    public function __construct(
        private readonly AffectationManager $affectationManager,
        private readonly SuiviManager $suiviManager,
        private readonly InterventionRepository $interventionRepository,
        private readonly InterventionFactory $interventionFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserManager $userManager,
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    public function synchronizeAffectationFrom(
        DossierResponseInterface $dossierResponse,
        Affectation $affectation
    ): void {
        $user = $affectation->getPartner()->getUsers()->first();
        $signalement = $affectation->getSignalement();

        $description = $this->updateStatusFor($affectation, $user, $dossierResponse);
        if (!empty($description)) {
            $adminEmail = $this->parameterBag->get('user_system_email');
            $adminUser = $this->userManager->findOneBy(['email' => $adminEmail]);
            $suivi = $this->suiviManager->createSuivi(
                user: $adminUser,
                signalement: $signalement,
                params: [
                    'domain' => 'esabora',
                    'action' => 'synchronize',
                    'description' => $description,
                    'name_partner' => $affectation->getPartner()->getNom(),
                ],
            );
            $this->suiviManager->save($suivi);
        }
    }

    public function updateStatusFor(
        Affectation $affectation,
        User $user,
        DossierResponseInterface $dossierResponse
    ): string {
        $description = '';
        $currentStatus = $affectation->getStatut();

        $esaboraStatus = $dossierResponse->getSasEtat();
        $esaboraDossierStatus = null !== $dossierResponse->getEtat()
        ? strtolower($dossierResponse->getEtat())
        : null;

        switch ($esaboraStatus) {
            case EsaboraStatus::ESABORA_WAIT->value:
                if (Affectation::STATUS_WAIT !== $currentStatus) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_WAIT);
                    $description = 'remis en attente via '.$dossierResponse->getNameSI();
                }
                break;
            case EsaboraStatus::ESABORA_ACCEPTED->value:
                if ($this->shouldBeAcceptedViaEsabora($esaboraDossierStatus, $currentStatus)) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_ACCEPTED);
                    $description = 'accepté via '.$dossierResponse->getNameSI();
                }

                if ($this->shouldBeClosedViaEsabora($esaboraDossierStatus, $currentStatus)) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_CLOSED);
                    $description = 'cloturé via '.$dossierResponse->getNameSI();
                }
                break;
            case EsaboraStatus::ESABORA_REFUSED->value:
                if (Affectation::STATUS_REFUSED !== $currentStatus) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_REFUSED);
                    $description = 'refusé via '.$dossierResponse->getNameSI();
                }
                break;
            case EsaboraStatus::ESABORA_REJECTED->value:
                if (Affectation::STATUS_REFUSED !== $currentStatus) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_REFUSED);
                    $description = sprintf(
                        'refusé via '.$dossierResponse->getNameSI().' pour motif suivant: %s',
                        $dossierResponse->getSasCauseRefus()
                    );
                }
                break;
        }

        return $description;
    }

    public function createOrUpdateVisite(Affectation $affectation, DossierVisiteSISH $dossierVisiteSISH): void
    {
        $intervention = $this->interventionRepository->findOneBy(['providerId' => $dossierVisiteSISH->getVisiteId()]);
        if (null !== $intervention) {
            $this->updateFromDossierVisite($intervention, $dossierVisiteSISH);
        } else {
            if (null === InterventionType::tryFromLabel($dossierVisiteSISH->getVisiteType())) {
                $this->logger->error(
                    sprintf(
                        '#%s - Le dossier SISH %s a un type de visite invalide `%s`. Types valides : (%s)',
                        $dossierVisiteSISH->getReferenceDossier(),
                        $dossierVisiteSISH->getDossNum(),
                        $dossierVisiteSISH->getVisiteType(),
                        implode(',', InterventionType::getLabelList())
                    )
                );
            } else {
                $newIntervention = $this->interventionFactory->createInstanceFrom(
                    affectation: $affectation,
                    type: InterventionType::tryFromLabel($dossierVisiteSISH->getVisiteType()),
                    scheduledAt: DateParser::parse($dossierVisiteSISH->getVisiteDate()),
                    registeredAt: new \DateTimeImmutable(),
                    status: Intervention::STATUS_DONE,
                    providerName: InterfacageType::ESABORA->value,
                    providerId: $dossierVisiteSISH->getVisiteId(),
                    doneBy: $dossierVisiteSISH->getVisitePar(),
                );
                $this->interventionRepository->save($newIntervention, true);
                $this->eventDispatcher->dispatch(
                    new InterventionCreatedEvent($newIntervention, $this->userManager->getSystemUser()),
                    InterventionCreatedEvent::NAME
                );
            }
        }
    }

    public function createOrUpdateArrete(Affectation $affectation, DossierArreteSISH $dossierArreteSISH): void
    {
        $intervention = $this->interventionRepository->findOneBy(['providerId' => $dossierArreteSISH->getArreteId()]);
        $additionalInformation = [
            'arrete_numero' => $dossierArreteSISH->getArreteNumero(),
            'arrete_type' => $dossierArreteSISH->getArreteType(),
            'arrete_mainlevee_date' => $dossierArreteSISH->getArreteMLDate(),
            'arrete_mainlevee_numero' => $dossierArreteSISH->getArreteMLNumero(),
        ];

        if (null !== $intervention) {
            $intervention->setAdditionalInformation($additionalInformation);
            $this->updateFromDossierArrete($intervention, $dossierArreteSISH);
        } else {
            $intervention = $this->interventionFactory->createInstanceFrom(
                affectation: $affectation,
                type: InterventionType::ARRETE_PREFECTORAL,
                scheduledAt: DateParser::parse($dossierArreteSISH->getArreteDate()),
                registeredAt: new \DateTimeImmutable(),
                status: Intervention::STATUS_DONE,
                providerName: InterfacageType::ESABORA->value,
                providerId: $dossierArreteSISH->getArreteId(),
                details: InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH),
                additionalInformation: $additionalInformation
            );

            $this->interventionRepository->save($intervention, true);
            $this->eventDispatcher->dispatch(
                new InterventionCreatedEvent($intervention, $this->userManager->getSystemUser()),
                InterventionCreatedEvent::NAME
            );
        }
    }

    private function updateFromDossierVisite(Intervention $intervention, DossierVisiteSISH $dossierVisiteSISH): void
    {
        $intervention
            ->setScheduledAt(DateParser::parse($dossierVisiteSISH->getVisiteDate()))
            ->setDoneBy($dossierVisiteSISH->getVisitePar());

        $this->interventionRepository->save($intervention, true);
    }

    private function updateFromDossierArrete(Intervention $intervention, DossierArreteSISH $dossierArreteSISH): void
    {
        $intervention
            ->setScheduledAt(DateParser::parse($dossierArreteSISH->getArreteDate()))
            ->setDetails(InterventionDescriptionGenerator::buildDescriptionArreteCreated($dossierArreteSISH))
            ->setStatus(Intervention::STATUS_DONE);

        $this->interventionRepository->save($intervention, true);
    }

    private function shouldBeAcceptedViaEsabora(string $esaboraDossierStatus, int $currentStatus): bool
    {
        return EsaboraStatus::ESABORA_IN_PROGRESS->value === $esaboraDossierStatus
            && Affectation::STATUS_ACCEPTED !== $currentStatus;
    }

    private function shouldBeClosedViaEsabora(string $esaboraDossierStatus, int $currentStatus): bool
    {
        return EsaboraStatus::ESABORA_CLOSED->value === $esaboraDossierStatus
            && Affectation::STATUS_CLOSED !== $currentStatus;
    }
}
