<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/signalements-meme-adresse')]
class SignalementSameAddressController extends AbstractController
{
    #[Route('/', name: 'back_signalement_same_address_index')]
    public function index(SignalementRepository $signalementRepository, TerritoryRepository $territoryRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $territories = $user->getPartnersTerritories();
        if ($this->isGranted('ROLE_ADMIN')) {
            $territories = $territoryRepository->findAllList();
        }

        $signalements = $signalementRepository->findSameAddressFiltered($user);
        $signalementsByAddress = [];
        $addressSuggestions = [];
        $communeSuggestions = [];
        $bailleurSuggestions = [];
        foreach ($signalements as $signalement) {
            $addressKey = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $signalement['adresseOccupant'].' '.$signalement['cpOccupant'].' '.$signalement['villeOccupant']));
            $communeNormalized = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $signalement['villeOccupant'].' '.$signalement['cpOccupant']));
            $bailleurNormalized = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $signalement['nomProprio']));
            if (!isset($signalementsByAddress[$addressKey])) {
                $signalementsByAddress[$addressKey] = [
                    'adresse' => $signalement['adresseOccupant'],
                    'cp' => $signalement['cpOccupant'],
                    'ville' => $signalement['villeOccupant'],
                    'territoryId' => $signalement['territoryId'],
                    'addressNormalised' => $addressKey,
                    'communeNormalised' => $communeNormalized,
                    'bailleurNormalised' => $bailleurNormalized,
                    'lat' => null,
                    'lng' => null,
                    'signalements' => [],
                ];
            }
            $signalementsByAddress[$addressKey]['signalements'][] = $signalement;
            if ($signalement['geoloc']) {
                $signalementsByAddress[$addressKey]['lat'] = $signalement['geoloc']['lat'];
                $signalementsByAddress[$addressKey]['lng'] = $signalement['geoloc']['lng'];
            }
            $addressSuggestions[$addressKey] = $signalement['adresseOccupant'].' '.$signalement['cpOccupant'].' '.$signalement['villeOccupant'];
            $communeSuggestions[$communeNormalized] = $signalement['villeOccupant'].' '.$signalement['cpOccupant'];
            $bailleurSuggestions[$bailleurNormalized] = $signalement['nomProprio'];
        }

        return $this->render('back/signalement-same-address/index.html.twig', [
            'nbSignalements' => count($signalements),
            'signalementsByAddress' => $signalementsByAddress,
            'addressSuggestions' => $addressSuggestions,
            'communeSuggestions' => $communeSuggestions,
            'bailleurSuggestions' => $bailleurSuggestions,
            'territories' => $territories,
        ]);
    }

    #[Route('/export', name: 'back_signalement_same_address_export')]
    public function export(): Response
    {
        // TODO
    }
}
