<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\Query\SignalementList\SameAddressQuery;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/historique-des-adresses')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class HistoAddressController extends AbstractController
{
    #[Route('/', name: 'back_histo_address_index')]
    public function index(SameAddressQuery $sameAddressQuery, TerritoryRepository $territoryRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $territories = $user->getPartnersTerritories();
        if ($this->isGranted('ROLE_ADMIN')) {
            $territories = $territoryRepository->findAllList();
        }

        $signalements = $sameAddressQuery->findSameAddressFiltered($user);
        $signalementsByAddress = [];
        foreach ($signalements as $signalement) {
            $addressKey = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $signalement['adresseOccupant'].' '.$signalement['cpOccupant'].' '.$signalement['villeOccupant']));
            if (!isset($signalementsByAddress[$addressKey])) {
                $signalementsByAddress[$addressKey] = [
                    'adresse' => $signalement['adresseOccupant'],
                    'cp' => $signalement['cpOccupant'],
                    'ville' => $signalement['villeOccupant'],
                    'territoryId' => $signalement['territoryId'],
                    'addressForHuman' => $signalement['adresseOccupant'].' '.$signalement['cpOccupant'].' '.$signalement['villeOccupant'],
                    'communeForHuman' => $signalement['villeOccupant'].' '.$signalement['cpOccupant'],
                    'bailleurForHuman' => $signalement['nomProprio'],
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
        }

        return $this->render('back/histo-address/index.html.twig', [
            'nbSignalements' => count($signalements),
            'signalementsByAddress' => $signalementsByAddress,
            'territories' => $territories,
        ]);
    }

    #[Route('/export', name: 'back_histo_address_export')]
    public function export(): Response
    {
        // TODO: implémenter l'export
        return new Response('Export en cours de développement');
    }
}
