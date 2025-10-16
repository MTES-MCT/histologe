<?php

namespace App\Repository\Query\SignalementList;

use App\Dto\SignalementAffectationListView;
use App\Dto\SignalementExport;
use App\Entity\Commune;
use App\Entity\User;
use App\Entity\View\ViewLatestIntervention;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

class ExportIterableQuery
{
    public function __construct(
        private readonly QueryBuilderFactory $queryBuilderFactory,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws Exception
     */
    public function stream(User $user, array $options = []): \Generator
    {
        // temporary increase the group_concat_max_len to a higher value, for texts in GROUP_CONCAT
        $connection = $this->em->getConnection();
        $sql = 'SET SESSION group_concat_max_len=32505856';
        $connection->prepare($sql)->executeQuery();

        $qb = $this->queryBuilderFactory->create($user, $options);

        $qb->addSelect(
            's.details,
            s.telOccupant,
            s.telOccupantBis,
            s.mailOccupant,
            s.cpOccupant,
            s.inseeOccupant,
            e.nom as epciNom,
            s.etageOccupant,
            s.escalierOccupant,
            s.numAppartOccupant,
            s.adresseAutreOccupant,
            s.isProprioAverti,
            s.nbOccupantsLogement,
            s.nbEnfantsM6,
            s.nbEnfantsP6,
            s.isAllocataire,
            s.numAllocataire,
            s.natureLogement,
            s.superficie,
            s.nomProprio,
            s.isLogementSocial,
            s.isPreavisDepart,
            s.isRelogement,
            s.nomDeclarant,
            s.mailDeclarant,
            s.structureDeclarant,
            s.lienDeclarantOccupant,
            s.modifiedAt,
            s.closedAt,
            s.motifCloture,
            s.comCloture,
            s.geoloc,
            s.typeCompositionLogement,
            s.informationProcedure,
            s.debutDesordres,
            GROUP_CONCAT(DISTINCT situations.label SEPARATOR :group_concat_separator_1) as oldSituations,
            GROUP_CONCAT(DISTINCT criteres.label SEPARATOR :group_concat_separator_1) as oldCriteres,
            GROUP_CONCAT(DISTINCT desordreCategories.label SEPARATOR :group_concat_separator_1) as listDesordreCategories,
            GROUP_CONCAT(DISTINCT desordreCriteres.labelCritere SEPARATOR :group_concat_separator_1) as listDesordreCriteres,
            GROUP_CONCAT(DISTINCT tags.label SEPARATOR :group_concat_separator_1) as etiquettes,
            MAX(vli.occupantPresent) AS interventionOccupantPresent,
            MAX(vli.concludeProcedure) AS interventionConcludeProcedure,
            MAX(vli.details) AS interventionDetails,
            MAX(vli.status) AS interventionStatus,
            MAX(vli.scheduledAt) AS interventionScheduledAt,
            MAX(vli.nbVisites) AS interventionNbVisites
            '
        )->leftJoin('s.situations', 'situations')
            ->leftJoin('s.criteres', 'criteres')
            ->leftJoin('s.desordrePrecisions', 'desordrePrecisions')
            ->leftJoin('desordrePrecisions.desordreCritere', 'desordreCriteres')
            ->leftJoin('desordreCriteres.desordreCategorie', 'desordreCategories')
            ->leftJoin('s.tags', 'tags')
            ->leftJoin(ViewLatestIntervention::class, 'vli', 'WITH', 'vli.signalementId = s.id')
            ->setParameter('concat_separator', SignalementAffectationListView::SEPARATOR_CONCAT)
            ->setParameter('group_concat_separator_1', SignalementExport::SEPARATOR_GROUP_CONCAT)
            ->leftJoin(Commune::class, 'c', Join::WITH, 'c.codePostal = s.cpOccupant AND c.codeInsee = s.inseeOccupant')
            ->leftJoin('c.epci', 'e')
        ;

        return $qb->getQuery()->toIterable();
    }
}
