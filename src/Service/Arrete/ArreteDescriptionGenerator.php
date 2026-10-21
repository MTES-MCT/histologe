<?php

namespace App\Service\Arrete;

use App\Entity\Arrete;
use App\Service\Interconnection\Esabora\EsaboraSISHService;

class ArreteDescriptionGenerator
{
    public static function buildDescriptionArreteCreated(Arrete $arrete): string
    {
        if ($arrete->getDateMainLevee()) {
            if ($arrete->getAdditionalInformation()['arrete_modificatif_date']) {
                return \sprintf(
                    'Un arrêté de mainlevée %s du %s a été pris pour l\'arrêté modificatif %s du %s dans le dossier de n°%s.',
                    $arrete->getAdditionalInformation()['arrete_mainlevee_numero'],
                    $arrete->getDateMainLevee()->format('d/m/Y'),
                    $arrete->getAdditionalInformation()['arrete_modificatif_numero'],
                    $arrete->getAdditionalInformation()['arrete_modificatif_date'],
                    $arrete->getAdditionalInformation()['numero_dossier']
                );
            }

            return \sprintf(
                'Un arrêté de mainlevée %s du %s a été pris pour l\'arrêté %s du %s dans le dossier de n°%s.',
                $arrete->getAdditionalInformation()['arrete_mainlevee_numero'],
                $arrete->getDateMainLevee()->format('d/m/Y'),
                $arrete->getAdditionalInformation()['arrete_numero'],
                $arrete->getDateArrete()->format('d/m/Y'),
                $arrete->getAdditionalInformation()['numero_dossier']
            );
        }

        if ($arrete->getAdditionalInformation()['arrete_modificatif_date']) {
            return \sprintf(
                'Un arrêté modificatif %s en date du %s a été pris concernant l\'arrêté %s du %s.',
                $arrete->getAdditionalInformation()['arrete_modificatif_numero'],
                $arrete->getAdditionalInformation()['arrete_modificatif_date'],
                $arrete->getAdditionalInformation()['arrete_numero'],
                $arrete->getDateArrete()->format('d/m/Y')
            );
        }

        $description = \sprintf(
            'L\'arrêté %s du %s a été pris dans le dossier de n°%s.<br>',
            $arrete->getAdditionalInformation()['arrete_numero'],
            $arrete->getDateArrete()->format('d/m/Y'),
            $arrete->getAdditionalInformation()['numero_dossier']
        );

        $description .= \sprintf('Type arrêté : %s<br>', $arrete->getArreteType()->label());

        return $description;
    }

    /**
     * @param array<string, string|null> $changedInformations
     */
    public static function buildDescriptionArreteUpdated(Arrete $arrete, array $changedInformations): ?string
    {
        if (empty($changedInformations)) {
            return null;
        }
        if (isset($changedInformations['arrete_date'])) {
            $messages[] = \sprintf(
                'La date de l\'arrêté dans %s a été modifiée ; La nouvelle date est %s.',
                EsaboraSISHService::NAME_SI,
                $changedInformations['arrete_date']
            );
        }
        if (array_key_exists('arrete_numero', $changedInformations)) {
            $messages[] = \sprintf(
                'Le numéro de l\'arrêté dans %s a été modifié ; Le nouveau numéro est %s.',
                EsaboraSISHService::NAME_SI,
                $changedInformations['arrete_numero'] ?? 'non renseigné'
            );
        }

        if (isset($changedInformations['arrete_modificatif_date'])) {
            $messages[] = \sprintf(
                'La date de l\'arrêté modificatif dans %s a été modifiée ; La nouvelle date est %s.',
                EsaboraSISHService::NAME_SI,
                $changedInformations['arrete_modificatif_date']
            );
        }
        if (array_key_exists('arrete_modificatif_numero', $changedInformations)) {
            $messages[] = \sprintf(
                'Le numéro de l\'arrêté modificatif dans %s a été modifié ; Le nouveau numéro est %s.',
                EsaboraSISHService::NAME_SI,
                $changedInformations['arrete_modificatif_numero'] ?? 'non renseigné'
            );
        }

        if (isset($changedInformations['arrete_mainlevee_date'])) {
            $messages[] = \sprintf(
                'La date de la mainlevée dans %s a été modifiée ; La nouvelle date est %s.',
                EsaboraSISHService::NAME_SI,
                $changedInformations['arrete_mainlevee_date']
            );
        }
        if (array_key_exists('arrete_mainlevee_numero', $changedInformations)) {
            $messages[] = \sprintf(
                'Le numéro de la mainlevée dans %s a été modifié ; Le nouveau numéro est %s.',
                EsaboraSISHService::NAME_SI,
                $changedInformations['arrete_mainlevee_numero'] ?? 'non renseigné'
            );
        }

        if (isset($changedInformations['numero_dossier'])) {
            $messages[] = \sprintf(
                'Le numéro de dossier de l\'arrêté dans %s a été modifié ; Le nouveau numéro est %s.',
                EsaboraSISHService::NAME_SI,
                $changedInformations['numero_dossier']
            );
        }

        if (isset($changedInformations['arrete_type'])) {
            $messages[] = \sprintf(
                'Le type de l\'arrêté dans %s a été modifié ; Le nouveau type est %s.',
                EsaboraSISHService::NAME_SI,
                $changedInformations['arrete_type']
            );
        } else {
            $messages[] = \sprintf('Type arrêté : %s', $arrete->getArreteType()->label());
        }

        return empty($messages) ? null : implode('<br>', $messages);
    }
}
