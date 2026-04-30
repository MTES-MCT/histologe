<?php

namespace App\Repository\Query\SignalementList;

use App\Dto\SignalementAffectationListView;
use App\Dto\SignalementExport;
use App\Entity\Commune;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\View\ViewLatestIntervention;
use App\Service\Signalement\Export\SignalementExportSelectableColumns;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

class ExportIterableQuery
{
    public const int BATCH_SIZE = 500;

    private const array VISITE_COLUMNS = ['NB_VISITES', 'DATE_VISITE', 'OCCUPANT_PRESENT_VISITE', 'STATUT_VISITE', 'CONCLUSION_VISITE', 'COMMENTAIRE_VISITE'];

    public function __construct(
        private readonly QueryBuilderFactory $queryBuilderFactory,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function stream(User $user, array $options = [], array $selectedColumns = []): \Generator
    {
        $columns = SignalementExportSelectableColumns::getColumns();
        foreach ($selectedColumns as $column) {
            if (!isset($columns[$column])) {
                throw new \InvalidArgumentException('La colonne "'.$column.'" sélectionnée n\'est pas exportable.');
            }
        }

        // Augmentation temporaire de la limite GROUP_CONCAT
        $this->em->getConnection()->prepare('SET SESSION group_concat_max_len=32505856')->executeQuery();

        // PASSE 1 : récupération des IDs filtrés (requête légère)
        $qbIds = $this->queryBuilderFactory->create($user, $options);
        $signalementIds = array_column($qbIds->getQuery()->getArrayResult(), 'id');

        if (empty($signalementIds)) {
            return;
        }

        // PASSE 2 : traitement par batch
        foreach (array_chunk($signalementIds, self::BATCH_SIZE) as $batchIds) {
            yield from $this->streamBatch($batchIds, $selectedColumns);
        }
    }

    private function streamBatch(array $signalementIds, array $selectedColumns): \Generator
    {
        // Requête principale : champs scalaires + STATUT + EPCI (pas de produit cartésien)
        $mainRows = $this->fetchMainData($signalementIds, $selectedColumns);

        // Requêtes secondaires isolées (chacune évite le produit cartésien inter-tables)
        $desordresData = [];
        if (\in_array('SITUATIONS', $selectedColumns, true) || \in_array('DESORDRES', $selectedColumns, true)) {
            $desordresData = $this->fetchDesordresData($signalementIds, $selectedColumns);
        }

        $etiquettesData = [];
        if (\in_array('ETIQUETTES', $selectedColumns, true)) {
            $etiquettesData = $this->fetchEtiquettesData($signalementIds);
        }

        $photosData = [];
        if (\in_array('PHOTOS', $selectedColumns, true)) {
            $photosData = $this->fetchPhotosData($signalementIds);
        }

        $documentsData = [];
        if (\in_array('DOCUMENTS', $selectedColumns, true)) {
            $documentsData = $this->fetchDocumentsData($signalementIds);
        }

        $visiteData = [];
        if (!empty(array_intersect(self::VISITE_COLUMNS, $selectedColumns))) {
            $visiteData = $this->fetchVisiteData($signalementIds, $selectedColumns);
        }

        foreach ($mainRows as $row) {
            $id = $row['id'];
            yield array_merge(
                $row,
                $desordresData[$id] ?? [],
                $etiquettesData[$id] ?? [],
                $photosData[$id] ?? [],
                $documentsData[$id] ?? [],
                $visiteData[$id] ?? [],
            );
        }
    }

    private function fetchMainData(array $signalementIds, array $selectedColumns): array
    {
        $columns = SignalementExportSelectableColumns::getColumns();
        $specificSelectKeys = array_keys(array_filter($columns, static fn ($col) => isset($col['specificSelect'])));

        $qb = $this->em->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('s.id')
            ->where('s.id IN (:signalementIds)')
            ->setParameter('signalementIds', $signalementIds)
            ->groupBy('s.id');

        foreach ($selectedColumns as $column) {
            if (\in_array($column, $specificSelectKeys, true)) {
                continue;
            }
            $export = $columns[$column]['export'];
            if (str_starts_with($export, 's.')) {
                $qb->addSelect($export);
            }
        }

        if (\in_array('STATUT', $selectedColumns, true)) {
            $qb->addSelect('s.statut');
            $qb->leftJoin('s.affectations', 'a');
            $qb->leftJoin('a.partner', 'p');
            $qb->addSelect(
                'GROUP_CONCAT(DISTINCT CONCAT(p.nom, :concat_separator, a.statut) SEPARATOR :group_concat_separator) as rawAffectations'
            );
            $qb->setParameter('concat_separator', SignalementAffectationListView::SEPARATOR_CONCAT);
            $qb->setParameter('group_concat_separator', SignalementAffectationListView::SEPARATOR_GROUP_CONCAT);
        }

        if (\in_array('EPCI_NOM', $selectedColumns, true)) {
            $qb->leftJoin(Commune::class, 'c', Join::WITH, 'c.codePostal = s.cpOccupant AND c.codeInsee = s.inseeOccupant');
            $qb->leftJoin('c.epci', 'e');
            $qb->addSelect('e.nom as epciNom');
        }

        if (\in_array('NB_ENFANTS', $selectedColumns, true) || \in_array('MOINS_6_ANS', $selectedColumns, true)) {
            $qb->addSelect('s.typeCompositionLogement');
            $qb->addSelect('s.nbEnfantsM6');
            $qb->addSelect('s.nbEnfantsP6');
        }

        $result = $qb->getQuery()->getArrayResult();

        $indexed = [];
        foreach ($result as $row) {
            $indexed[$row['id']] = $row;
        }

        return array_values(array_filter(array_map(static fn (int $id) => $indexed[$id] ?? null, $signalementIds)));
    }

    private function fetchDesordresData(array $signalementIds, array $selectedColumns): array
    {
        $qb = $this->em->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('s.id')
            ->leftJoin('s.criticites', 'criticites')
            ->leftJoin('criticites.critere', 'criteres')
            ->leftJoin('criteres.situation', 'situations')
            ->leftJoin('s.desordrePrecisions', 'desordrePrecisions')
            ->leftJoin('desordrePrecisions.desordreCritere', 'desordreCriteres')
            ->leftJoin('desordreCriteres.desordreCategorie', 'desordreCategories')
            ->where('s.id IN (:signalementIds)')
            ->setParameter('signalementIds', $signalementIds)
            ->setParameter('sep', SignalementExport::SEPARATOR_GROUP_CONCAT)
            ->groupBy('s.id');

        if (\in_array('SITUATIONS', $selectedColumns, true)) {
            $qb->addSelect('GROUP_CONCAT(DISTINCT situations.label SEPARATOR :sep) as oldSituations');
            $qb->addSelect('GROUP_CONCAT(DISTINCT desordreCategories.label SEPARATOR :sep) as listDesordreCategories');
        }

        if (\in_array('DESORDRES', $selectedColumns, true)) {
            $qb->addSelect('GROUP_CONCAT(DISTINCT criteres.label SEPARATOR :sep) as oldCriteres');
            $qb->addSelect('GROUP_CONCAT(DISTINCT desordreCriteres.labelCritere SEPARATOR :sep) as listDesordreCriteres');
        }

        $result = $qb->getQuery()->getArrayResult();

        $indexed = [];
        foreach ($result as $row) {
            $id = $row['id'];
            unset($row['id']);
            $indexed[$id] = $row;
        }

        return $indexed;
    }

    private function fetchEtiquettesData(array $signalementIds): array
    {
        $result = $this->em->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('s.id')
            ->leftJoin('s.tags', 'tags')
            ->where('s.id IN (:signalementIds)')
            ->setParameter('signalementIds', $signalementIds)
            ->setParameter('sep', SignalementExport::SEPARATOR_GROUP_CONCAT)
            ->groupBy('s.id')
            ->addSelect('GROUP_CONCAT(DISTINCT tags.label SEPARATOR :sep) as etiquettes')
            ->getQuery()
            ->getArrayResult();

        $indexed = [];
        foreach ($result as $row) {
            $indexed[$row['id']] = ['etiquettes' => $row['etiquettes']];
        }

        return $indexed;
    }

    private function fetchPhotosData(array $signalementIds): array
    {
        $result = $this->em->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('s.id')
            ->leftJoin('s.files', 'photos', Join::WITH, 'photos.extension IN (:photo_extensions)')
            ->where('s.id IN (:signalementIds)')
            ->setParameter('signalementIds', $signalementIds)
            ->setParameter('photo_extensions', File::RESIZABLE_EXTENSION)
            ->setParameter('sep', SignalementExport::SEPARATOR_GROUP_CONCAT)
            ->groupBy('s.id')
            ->addSelect('GROUP_CONCAT(DISTINCT photos.filename SEPARATOR :sep) as photosName')
            ->getQuery()
            ->getArrayResult();

        $indexed = [];
        foreach ($result as $row) {
            $indexed[$row['id']] = ['photosName' => $row['photosName']];
        }

        return $indexed;
    }

    private function fetchDocumentsData(array $signalementIds): array
    {
        $result = $this->em->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('s.id')
            ->leftJoin('s.files', 'documents', Join::WITH, 'documents.extension NOT IN (:photo_extensions)')
            ->where('s.id IN (:signalementIds)')
            ->setParameter('signalementIds', $signalementIds)
            ->setParameter('photo_extensions', File::RESIZABLE_EXTENSION)
            ->setParameter('sep', SignalementExport::SEPARATOR_GROUP_CONCAT)
            ->groupBy('s.id')
            ->addSelect('GROUP_CONCAT(DISTINCT documents.filename SEPARATOR :sep) as documentsName')
            ->getQuery()
            ->getArrayResult();

        $indexed = [];
        foreach ($result as $row) {
            $indexed[$row['id']] = ['documentsName' => $row['documentsName']];
        }

        return $indexed;
    }

    private function fetchVisiteData(array $signalementIds, array $selectedColumns): array
    {
        $qb = $this->em->createQueryBuilder()
            ->from(ViewLatestIntervention::class, 'vli')
            ->select('vli.signalementId')
            ->where('vli.signalementId IN (:signalementIds)')
            ->setParameter('signalementIds', $signalementIds)
            ->groupBy('vli.signalementId');

        if (\in_array('NB_VISITES', $selectedColumns, true)) {
            $qb->addSelect('MAX(vli.nbVisites) AS interventionNbVisites');
        }
        if (\in_array('DATE_VISITE', $selectedColumns, true)) {
            $qb->addSelect('MAX(vli.scheduledAt) AS interventionScheduledAt');
        }
        if (\in_array('OCCUPANT_PRESENT_VISITE', $selectedColumns, true)) {
            $qb->addSelect('MAX(vli.occupantPresent) AS interventionOccupantPresent');
        }
        if (\in_array('STATUT_VISITE', $selectedColumns, true)) {
            $qb->addSelect('MAX(vli.status) AS interventionStatus');
        }
        if (\in_array('CONCLUSION_VISITE', $selectedColumns, true)) {
            $qb->addSelect('MAX(vli.concludeProcedure) AS interventionConcludeProcedure');
        }
        if (\in_array('COMMENTAIRE_VISITE', $selectedColumns, true)) {
            $qb->addSelect('MAX(vli.details) AS interventionDetails');
        }

        $result = $qb->getQuery()->getArrayResult();

        $indexed = [];
        foreach ($result as $row) {
            $id = $row['signalementId'];
            unset($row['signalementId']);
            $indexed[$id] = $row;
        }

        return $indexed;
    }
}
