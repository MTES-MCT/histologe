<?php

namespace App\EventListener;

use App\Entity\Signalement;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Signalement::class)]
class SignalementUpdatedListener
{
    public const string EDIT_COORDONNEES_BAILLEUR = 'coordonnees_bailleur';
    public const string EDIT_COORDONNEES_AGENCE = 'coordonnees_agence';

    public const array EDIT_FIELDS = [
        self::EDIT_COORDONNEES_BAILLEUR => [
            'label' => 'coordonnées du bailleur',
            'fields' => [
                'nomProprio' => 'Nom',
                'prenomProprio' => 'Prénom',
                'mailProprio' => 'E-mail',
                'telProprio' => 'Téléphone',
                'telProprioSecondaire' => 'Téléphone secondaire',
                'adresseProprio' => 'Adresse',
                'codePostalProprio' => 'Code postal',
                'villeProprio' => 'Ville',
            ]
        ],
        self::EDIT_COORDONNEES_AGENCE => [
            'label' => 'coordonnées de l\'agence',
            'fields' => [
                'denominationAgence' => 'Dénomination',
                'nomAgence' => 'Nom',
                'prenomAgence' => 'Prénom',
                'mailAgence' => 'E-mail',
                'telAgence' => 'Téléphone',
                'telAgenceSecondaire' => 'Téléphone secondaire',
                'adresseAgence' => 'Adresse',
                'codePostalAgence' => 'Code postal',
                'villeAgence' => 'Ville',
            ]
        ]
    ];

    public function preUpdate(Signalement $signalement, PreUpdateEventArgs $event): void
    {
        if ([] !== $event->getEntityChangeSet()) {
            $signalement->setUpdateOccurred(true);
        }

        $editedForms = [];

        foreach (self::EDIT_FIELDS as $editFormKey => $editFormMapping) {

            $fieldChanges = [];

            foreach ($editFormMapping['fields'] as $fieldName => $label) {

                if (!$event->hasChangedField($fieldName)) {
                    continue;
                }

                $old = $event->getOldValue($fieldName);
                $new = $event->getNewValue($fieldName);

                if ($old === $new) {
                    continue;
                }

                $fieldChanges[$fieldName] = [
                    'label' => $label,
                    'new'   => $new,
                ];
            }

            if ($fieldChanges !== []) {
                $editedForms[$editFormKey] = [
                    'label' => $editFormMapping['label'],
                    'fieldChanges' => $fieldChanges,
                ];
            }
        }

        $signalement->setChanges($editedForms);
    }
}
