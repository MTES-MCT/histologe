<?php

namespace App\Service;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class HtmlTargetContentsService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SignalementRepository $signalementRepository,
    ) {
    }

    public function getHtmlTargetContentsForSignalementAddress(Signalement $signalement): array
    {
        $signalementsOnSameAddress = $this->signalementRepository->findOnSameAddress(signalement: $signalement, exclusiveStatus: [], excludedStatus: SignalementStatus::excludedStatuses());

        return [
            [
                'target' => '#header-address',
                'content' => $this->twig->render('back/signalement/view/header/_address.html.twig', [
                    'signalement' => $signalement,
                    'signalementsOnSameAddress' => $signalementsOnSameAddress,
                    'routeForListOfSignalementOnAddress' => $this->urlGenerator->generate('back_signalements_index', [
                        'isImported' => 'oui',
                        'searchTerms' => trim($signalement->getAdresseOccupant()),
                        'communes[]' => $signalement->getCpOccupant(),
                    ]),
                ]),
            ],
        ];
    }
}
