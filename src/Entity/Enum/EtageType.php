<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;
use App\Entity\Model\TypeCompositionLogement;

enum EtageType: string
{
    use EnumTrait;

    case RDC = 'RDC';
    case DERNIER_ETAGE = 'DERNIER_ETAGE';
    case SOUSSOL = 'SOUSSOL';
    case AUTRE = 'AUTRE';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            'RDC' => 'Rez-de-chaussée',
            'DERNIER_ETAGE' => 'Dernier étage',
            'SOUSSOL' => 'Sous-sol',
            'AUTRE' => 'Autre étage',
        ];
    }

    /**
     * Valeur à stocker dans Signalement::etageOccupant : le libellé de l'étage, ou la
     * précision libre saisie par l'usager/l'agent quand l'étage est "Autre". 
     */
    public static function resolveOccupantLabel(?self $etage, ?string $precision): ?string
    {
        if (null === $etage) {
            return null;
        }

        return self::AUTRE === $etage ? $precision : $etage->label();
    }

    /**
     * Répercute un changement d'étage (et de la réponse "avec fenêtres") sur TypeCompositionLogement 
     */
    public static function applyToTypeCompositionLogement(
        TypeCompositionLogement $typeCompositionLogement,
        ?self $etage,
        ?string $avecFenetres,
    ): void {
        $typeCompositionLogement
            ->setTypeLogementAppartementEtage($etage?->value)
            ->setTypeLogementRdc(self::RDC === $etage ? 'oui' : 'non')
            ->setTypeLogementDernierEtage(self::DERNIER_ETAGE === $etage ? 'oui' : 'non')
            ->setTypeLogementSousCombleSansFenetre(self::DERNIER_ETAGE === $etage && 'non' === $avecFenetres ? 'oui' : 'non')
            ->setTypeLogementSousSolSansFenetre(self::SOUSSOL === $etage && 'non' === $avecFenetres ? 'oui' : 'non');
    }
}
