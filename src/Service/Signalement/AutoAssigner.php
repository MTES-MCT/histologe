<?php

namespace App\Service\Signalement;

use App\Entity\Affectation;
use App\Entity\AutoAffectationRule;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Factory\SuiviFactory;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Messenger\InterconnectionBus;
use App\Repository\PartnerRepository;
use App\Specification\Affectation\AllocataireSpecification;
use App\Specification\Affectation\CodeInseeSpecification;
use App\Specification\Affectation\ParcSpecification;
use App\Specification\Affectation\PartnerTypeSpecification;
use App\Specification\Affectation\ProfilDeclarantSpecification;
use App\Specification\AndSpecification;
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
        private InterconnectionBus $interconnectionBus,
    ) {
    }

    public function assign(Signalement $signalement): void
    {
        $this->countAffectations = 0;
        $autoAffectationRules = $signalement->getTerritory()->getAutoAffectationRules()->filter(function (AutoAffectationRule $autoAffectationRule) {
            return AutoAffectationRule::STATUS_ACTIVE === $autoAffectationRule->getStatus();
        });
        if (!$autoAffectationRules->isEmpty()) {
            $inseeOccupant = $signalement->getInseeOccupant();
            $adminEmail = $this->parameterBag->get('user_system_email');
            $adminUser = $this->userManager->findOneBy(['email' => $adminEmail]);
            $partners = $signalement->getTerritory()->getPartners();
            $assignablePartners = [];

            /** @var AutoAffectationRule $rule */
            foreach ($autoAffectationRules as $rule) {
                $specification = new AndSpecification(
                    new ProfilDeclarantSpecification($rule->getProfileDeclarant()),
                    new PartnerTypeSpecification($rule->getPartnerType()),
                    new CodeInseeSpecification($rule->getInseeToInclude(), $rule->getInseeToExclude()),
                    new ParcSpecification($rule->getParc()),
                    new AllocataireSpecification($rule->getAllocataire()),
                );

                foreach ($partners as $partner) {
                    if ($specification->isSatisfiedBy(['partner' => $partner, 'signalement' => $signalement])) {
                        $assignablePartners[] = $partner;
                    }
                }
            }

            if (!empty($assignablePartners)) {
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
                foreach ($assignablePartners as $partner) {
                    $affectation = $this->affectationManager->createAffectationFrom(
                        $signalement,
                        $partner,
                        $adminUser
                    );
                    ++$this->countAffectations;
                    if ($affectation instanceof Affectation) {
                        $this->affectationManager->persist($affectation);
                        if ($partner->canSyncWithEsabora()) {
                            $this->interconnectionBus->dispatch($affectation);
                        }
                        $this->affectationManager->save($affectation, false);
                    }
                }
                $this->affectationManager->flush();
            }
        }
    }

    public function getCountAffectations(): int
    {
        return $this->countAffectations;
    }
}
