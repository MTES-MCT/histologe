<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\Query\SignalementList\SameAddressQuery;
use App\Repository\TerritoryRepository;
use App\Utils\ExportFormat;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Options;
use OpenSpout\Writer\CSV\Writer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/signalements-meme-adresse')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class SignalementSameAddressController extends AbstractController
{
    #[Route('/', name: 'back_signalement_same_address_index')]
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

        return $this->render('back/signalement-same-address/index.html.twig', [
            'nbSignalements' => count($signalements),
            'signalementsByAddress' => $signalementsByAddress,
            'territories' => $territories,
        ]);
    }

    #[Route('/export', name: 'back_signalement_same_address_export', methods: ['POST'])]
    public function export(Request $request, SameAddressQuery $sameAddressQuery, TerritoryRepository $territoryRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $isMultiTerritory = false;
        $territories = [];
        if ($user->isSuperAdmin() || count($user->getPartnersTerritories()) > 1) {
            $isMultiTerritory = true;
            $territories = $territoryRepository->findAllList();
        }
        $signalements = $sameAddressQuery->findSameAddressFiltered($user);
        $signalementsFiltered = [];
        $searchTerritoryId = $request->request->get('territoryId');
        $searchAddress = $request->request->get('address');
        $searchCommune = $request->request->get('commune');
        $searchBailleur = $request->request->get('bailleur');
        foreach ($signalements as $signalement) {
            if ($searchTerritoryId && $signalement['territoryId'] != $searchTerritoryId) {
                continue;
            }
            $addressKey = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $signalement['adresseOccupant'].' '.$signalement['cpOccupant'].' '.$signalement['villeOccupant']));
            if ($searchAddress && $addressKey != $searchAddress) {
                continue;
            }
            $communeNormalized = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $signalement['villeOccupant'].' '.$signalement['cpOccupant']));
            if ($searchCommune && $communeNormalized != $searchCommune) {
                continue;
            }
            $bailleurNormalized = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $signalement['nomProprio']));
            if ($searchBailleur && $bailleurNormalized != $searchBailleur) {
                continue;
            }
            $signalementsFiltered[] = $signalement;
        }
        $writer = new Writer(new Options(FIELD_DELIMITER: ExportFormat::CSV_SEPARATOR));
        $filename = 'dossier_meme_adresse_'.date('Y-m-d_H-i-s').'.'.ExportFormat::FORMAT_CSV;
        $contentType = 'text/csv';
        $response = new StreamedResponse(static function () use ($signalementsFiltered, $territories, $writer, $isMultiTerritory) {
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues([
                $isMultiTerritory ? 'Territoire' : null,
                'Référence du dossier',
                'Nom / prénom de l\'occupant',
                'Adresse postale',
                'Code postal',
                'Commune',
                'Statut du dossier',
                'Date de création du dossier',
                'Date de fermeture du dossier',
            ]));
            foreach ($signalementsFiltered as $signalement) {
                $writer->addRow(Row::fromValues([
                    $isMultiTerritory ? $territories[$signalement['territoryId']]->getZipAndName() : null,
                    $signalement['reference'],
                    $signalement['nomOccupant'].' '.$signalement['prenomOccupant'],
                    $signalement['adresseOccupant'],
                    $signalement['cpOccupant'],
                    $signalement['villeOccupant'],
                    $signalement['statut']->name,
                    $signalement['createdAt']->format('d/m/Y'),
                    $signalement['closedAt'] ? $signalement['closedAt']->format('d/m/Y') : null,
                ]));
            }
            $writer->close();
        });
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
