<?php

namespace App\Factory;

use App\Dto\SignalementExport;
use App\Entity\Enum\MotifCloture;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class SignalementExportFactory
{
    public function createInstanceFrom(UserInterface|User $user, array $data): SignalementExport
    {
        $createdAt = $data['createdAt'] instanceof \DateTimeImmutable ? $data['createdAt']->format('d/m/y') : null;
        $modifiedAt = $data['modifiedAt'] instanceof \DateTimeImmutable ? $data['modifiedAt']->format('d/m/y') : null;
        $closedAt = $data['closedAt'] instanceof \DateTimeImmutable ? $data['closedAt']->format('d/m/y') : null;
        $dateVisite = $data['dateVisite'] instanceof \DateTimeImmutable ? $data['dateVisite']->format('d/m/y') : null;
        $motifCloture = $data['motifCloture'] instanceof MotifCloture ? $data['motifCloture']->value : null;

        return new SignalementExport(
            reference: $data['reference'],
            createdAt: $createdAt,
            statut: $data['statut'],
            description: $data['details'],
            nomOccupant: $data['nomOccupant'],
            prenomOccupant: $data['prenomOccupant'],
            telephoneOccupant: $data['telOccupant'],
            telephoneOccupantBis: $data['telOccupantBis'],
            emailOccupant: $data['mailOccupant'],
            adresseOccupant: $data['adresseOccupant'],
            cpOccupant: $data['cpOccupant'],
            villeOccupant: $data['villeOccupant'],
            inseeOccupant: $data['inseeOccupant'],
            etageOccupant: $data['etageOccupant'],
            escalierOccupant: $data['escalierOccupant'],
            numAppartOccupant: $data['numAppartOccupant'],
            adresseAutreOccupant: $data['adresseAutreOccupant'],
            photos: !empty($data['photos']),
            documents: !empty($data['documents']),
            isProprioAverti: $data['isProprioAverti'],
            nbAdultes: $data['nbAdultes'],
            nbEnfantsM6: $data['nbEnfantsM6'],
            nbEnfantsP6: $data['nbEnfantsP6'],
            isAllocataire: $data['isAllocataire'],
            numAllocataire: $data['numAllocataire'],
            superficie: $data['superficie'],
            nomProprio: $data['nomProprio'],
            isLogementSocial: $data['isLogementSocial'],
            isPreavisDepart: $data['isPreavisDepart'],
            isRelogement: $data['isRelogement'],
            isNotOccupant: $data['isNotOccupant'],
            nomDeclarant: $data['nomDeclarant'],
            structureDeclarant: $data['structureDeclarant'],
            lienDeclarantOccupant: $data['lienDeclarantOccupant'],
            scoreCreation: $data['scoreCreation'],
            dateVisite: $dateVisite,
            isOccupantPresentVisite: $data['isOccupantPresentVisite'],
            modifiedAt: $modifiedAt,
            closedAt: $closedAt,
            motifCloture: $motifCloture,
            scoreCloture: $data['scoreCloture']
        );
    }
}
