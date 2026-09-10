<?php

namespace App\Service;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Repository\EpciRepository;
use App\Repository\SignalementRepository;
use App\Service\Signalement\SignalementSameAddressArreteFinder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class SignalementAddressContentService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SignalementRepository $signalementRepository,
        private readonly EpciRepository $epciRepository,
        private readonly SignalementSameAddressArreteFinder $arreteFinder,
        #[Autowire(env: 'FEATURE_HISTO_ADDRESS')]
        private readonly bool $featureHistoAddress,
        private readonly Security $security,
    ) {
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getHtmlTargetContentsForSignalementAddress(Signalement $signalement): array
    {
        $signalementsOnSameAddress = $this->signalementRepository->findOnSameAddress(signalement: $signalement, exclusiveStatus: [], excludedStatus: SignalementStatus::excludedStatuses());

        $epciOccupant = $this->epciRepository->findOneByCommuneInseeAndPostalCode($signalement->getAddress()->getCityCode(), $signalement->getAddress()->getPostCode());

        return [
            [
                'target' => '#header-address',
                'content' => $this->twig->render('back/signalement/view/header/_address.html.twig', [
                    'signalement' => $signalement,
                    'epciOccupant' => $epciOccupant,
                    'signalementsOnSameAddress' => $signalementsOnSameAddress,
                    'arretesOnSameAddress' => $this->arreteFinder->find($signalement),
                    'routeForListOfSignalementOnAddress' => $this->getRouteForListOfSignalementOnAddress($signalement),
                ]),
            ],
        ];
    }

    public function getRouteForListOfSignalementOnAddress(Signalement $signalement): string
    {
        if ($this->featureHistoAddress && $this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $urlParams = [
                'view' => 'list',
                'adresse' => trim($signalement->getAddress()->getHousenumberAndStreet()),
                'communes[]' => $signalement->getAddress()->getCity(),
            ];
            if ($this->security->isGranted('ROLE_ADMIN')) {
                $urlParams['territoire'] = $signalement->getAddress()->getTerritory()->getId();
            }

            return $this->urlGenerator->generate('back_addresses_history_index', $urlParams);
        }

        return $this->urlGenerator->generate('back_signalements_index', [
            'isImported' => 'oui',
            'searchTerms' => trim($signalement->getAddress()->getHousenumberAndStreet()),
            'communes[]' => $signalement->getAddress()->getPostCode(),
        ]);
    }
}
