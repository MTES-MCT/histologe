<?php

namespace App\Service\Signalement;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Factory\SuiviFactory;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Messenger\EsaboraBus;
use App\Repository\PartnerRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AutoAssigner
{
    private int $countAffectations;

    public function __construct(
        private SignalementManager $signalementManager,
        private AffectationManager $affectationManager,
        private SuiviManager $suiviManager,
        private SuiviFactory $suiviFactory,
        private PartnerRepository $partnerRepository,
        private UserManager $userManager,
        private ParameterBagInterface $parameterBag,
        private EsaboraBus $esaboraBus,
    ) {
    }

    public function assign(Signalement $signalement): void
    {
        $this->countAffectations = 0;
        if ($signalement->getTerritory()->isAutoAffectationEnabled()) {
            $inseeOccupant = $signalement->getInseeOccupant();
            if (!empty($inseeOccupant)) {
                $adminEmail = $this->parameterBag->get('user_system_email');
                $adminUser = $this->userManager->findOneBy(['email' => $adminEmail]);

                $partners = $this->partnerRepository->findAutoAssignable($inseeOccupant, PartnerType::COMMUNE_SCHS);
                if (!empty($partners)) {
                    $signalement->setStatut(Signalement::STATUS_ACTIVE);
                    $signalement->setValidatedAt(new \DateTimeImmutable());
                    $this->signalementManager->save($signalement);

                    $params = [
                        'type' => SUIVI::TYPE_AUTO,
                        'description' => 'Signalement validé',
                    ];
                    $suivi = $this->suiviFactory->createInstanceFrom(
                        user: $adminUser,
                        signalement: $signalement,
                        params: $params,
                        isPublic: true,
                    );
                    $this->suiviManager->save($suivi);

                    /** @var Partner $partner */
                    foreach ($partners as $partner) {
                        $affectation = $this->affectationManager->createAffectationFrom($signalement, $partner, $adminUser);
                        ++$this->countAffectations;
                        if ($affectation instanceof Affectation) {
                            $this->affectationManager->persist($affectation);
                            if ($partner->getEsaboraToken() && $partner->getEsaboraUrl() && $partner->isEsaboraActive()) {
                                $affectation->setIsSynchronized(true);
                                $this->affectationManager->save($affectation, false);
                                $this->esaboraBus->dispatch($affectation);
                            } else {
                                $this->affectationManager->save($affectation, false);
                            }
                        }
                    }
                    $this->affectationManager->flush();
                }
            }
        }
    }

    public function getCountAffectations(): int
    {
        return $this->countAffectations;
    }
}
