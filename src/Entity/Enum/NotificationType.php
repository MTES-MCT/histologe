<?php

namespace App\Entity\Enum;

use App\Entity\Behaviour\EnumTrait;

enum NotificationType: string
{
    use EnumTrait;
    case NOUVEAU_SIGNALEMENT = 'NOUVEAU_SIGNALEMENT';
    case NOUVEAU_SUIVI = 'NOUVEAU_SUIVI';
    case NOUVELLE_AFFECTATION = 'NOUVELLE_AFFECTATION';
    case CLOTURE_SIGNALEMENT = 'CLOTURE_SIGNALEMENT';
    case CLOTURE_PARTENAIRE = 'CLOTURE_PARTENAIRE';
    case SUIVI_USAGER = 'SUIVI_USAGER';
    case NOUVEL_ABONNEMENT = 'NOUVEL_ABONNEMENT';
    case DEMANDE_ABANDON_PROCEDURE = 'DEMANDE_ABANDON_PROCEDURE';

    /** @return array<string, string> */
    public static function getLabelList(): array
    {
        return [
            self::NOUVEAU_SIGNALEMENT->name => self::NOUVEAU_SIGNALEMENT->value,
            self::NOUVEAU_SUIVI->name => self::NOUVEAU_SUIVI->value,
            self::NOUVELLE_AFFECTATION->name => self::NOUVELLE_AFFECTATION->value,
            self::CLOTURE_SIGNALEMENT->name => self::CLOTURE_SIGNALEMENT->value,
            self::CLOTURE_PARTENAIRE->name => self::CLOTURE_PARTENAIRE->value,
            self::SUIVI_USAGER->name => self::SUIVI_USAGER->value,
            self::NOUVEL_ABONNEMENT->name => self::NOUVEL_ABONNEMENT->value,
            self::DEMANDE_ABANDON_PROCEDURE->name => self::DEMANDE_ABANDON_PROCEDURE->value,
        ];
    }

    /**
     * @return array<NotificationType>
     */
    public static function getForAgent(): array
    {
        return array_filter(NotificationType::cases(), function (NotificationType $notificationType) {
            return self::SUIVI_USAGER !== $notificationType;
        });
    }

    /**
     * @return array<NotificationType>
     */
    public static function getForUsager(): array
    {
        return array_filter(NotificationType::cases(), function (NotificationType $notificationType) {
            return self::SUIVI_USAGER === $notificationType;
        });
    }
}
