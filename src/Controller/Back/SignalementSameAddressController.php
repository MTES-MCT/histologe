<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\Query\SignalementList\SameAddressQuery;
use App\Repository\TerritoryRepository;
use App\Utils\ExportFormat;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
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

    #[Route('/export', name: 'back_signalement_same_address_export')]
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
        $filtersText = [];
        $searchTerritoryId = $request->query->get('territoryId');
        if ($searchTerritoryId) {
            $filtersText['Territoire'] = $territories[$searchTerritoryId]->getZipAndName();
        }
        $searchAddress = $this->normalizeStr($request->query->get('address'));
        if ($searchAddress) {
            $filtersText['Adresse'] = $request->query->get('address');
        }
        $searchCommune = $this->normalizeStr($request->query->get('commune'));
        if ($searchCommune) {
            $filtersText['Commune'] = $request->query->get('commune');
        }
        $searchBailleur = $this->normalizeStr($request->query->get('bailleur'));
        if ($searchBailleur) {
            $filtersText['Bailleur'] = $request->query->get('bailleur');
        }
        $format = $request->request->get('file-format');
        foreach ($signalements as $signalement) {
            if ($searchTerritoryId && $signalement['territoryId'] != $searchTerritoryId) {
                continue;
            }
            $addressKey = $this->normalizeStr($signalement['adresseOccupant'].' '.$signalement['cpOccupant'].' '.$signalement['villeOccupant']);
            if ($searchAddress && $addressKey != $searchAddress) {
                continue;
            }
            $communeNormalized = $this->normalizeStr($signalement['villeOccupant'].' '.$signalement['cpOccupant']);
            if ($searchCommune && $communeNormalized != $searchCommune) {
                continue;
            }
            $bailleurNormalized = $this->normalizeStr((string) $signalement['nomProprio']);
            if ($searchBailleur && $bailleurNormalized != $searchBailleur) {
                continue;
            }
            $signalementsFiltered[] = $signalement;
        }
        if (!in_array($format, [ExportFormat::FORMAT_CSV, ExportFormat::FORMAT_XLSX])) {
            if ('POST' === $request->getMethod()) {
                $this->addFlash('error', 'Merci de sélectionner le format de l\'export.');
            }

            return $this->render('back/signalement-same-address/export-same-address.html.twig', [
                'nbResults' => \count($signalementsFiltered),
                'filtersText' => $filtersText,
                'isMultiTerritory' => $isMultiTerritory,
            ]);
        }
        if (ExportFormat::FORMAT_XLSX === $format) {
            $writer = new XlsxWriter();
        } else {
            $writer = new CsvWriter(new CsvOptions(FIELD_DELIMITER: ExportFormat::CSV_SEPARATOR));
        }
        $filename = 'dossier_meme_adresse_'.date('Y-m-d_H-i-s').'.'.$format;
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

    private function normalizeStr(string $str): string
    {
        // Attention toute modification de cette fonction doit être répercutée dans le back_signalement_same_address.js (voir fonction "normalizeStr")
        $normalized = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str));
        $normalized = str_replace('-', ' ', $normalized);

        return $normalized;
    }
}
