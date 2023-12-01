<?php

namespace App\Service\Signalement;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Messenger\EsaboraBus;
use App\Repository\PartnerRepository;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AutoAssigner
{
    public function __construct(
        private SignalementManager $signalementManager,
        private AffectationManager $affectationManager,
        private SuiviManager $suiviManager,
        private PartnerRepository $partnerRepository,
        private UserManager $userManager,
        private ParameterBagInterface $parameterBag,
        private EsaboraBus $esaboraBus,
    ) {
    }

    public function assign(Signalement $signalement): array
    {
        $affectations = [];
        if ($signalement->getTerritory()->isAutoAffectationEnabled()) {
            $inseeOccupant = $signalement->getInseeOccupant();
            if (!empty($inseeOccupant)) {
                $adminEmail = $this->parameterBag->get('user_system_email');
                $adminUser = $this->userManager->findOneBy(['email' => $adminEmail]);

                $partners = $this->partnerRepository->findAutoAssignable($inseeOccupant);
                if (!empty($partners)) {
                    $signalement->setStatut(Signalement::STATUS_ACTIVE);
                    $signalement->setValidatedAt(new DateTimeImmutable());
                    $this->signalementManager->save($signalement);

                    $suivi = new Suivi();
                    $suivi->setSignalement($signalement)
                        ->setDescription('Signalement validé')
                        ->setCreatedBy($adminUser)
                        ->setIsPublic(true)
                        ->setType(SUIVI::TYPE_AUTO);
                    $this->suiviManager->save($suivi);

                    /** @var Partner $partner */
                    foreach ($partners as $partner) {
                        $affectation = $this->affectationManager->createAffectationFrom($signalement, $partner, $adminUser);
                        $affectations[] = $affectation;
                        if ($affectation instanceof Affectation) {
                            $this->affectationManager->persist($affectation);
                            if ($partner->getEsaboraToken() && $partner->getEsaboraUrl() && $partner->isEsaboraActive()) {
                                $affectation->setIsSynchronized(true);
                                $this->affectationManager->save($affectation);
                                $this->esaboraBus->dispatch($affectation);
                            } else {
                                $this->affectationManager->save($affectation);
                            }
                        }
                    }
                }
            }
        }

        return $affectations;
    }
}
