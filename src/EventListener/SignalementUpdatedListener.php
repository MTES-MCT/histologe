<?php

namespace App\EventListener;

use App\Entity\Signalement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Signalement::class)]
class SignalementUpdatedListener
{
    public const string EDIT_COORDONNEES_BAILLEUR = 'coordonnees_bailleur';
    public const string EDIT_COORDONNEES_AGENCE = 'coordonnees_agence';

    public const array EDIT_SECTIONS = [
        self::EDIT_COORDONNEES_BAILLEUR => [
            'label' => 'Les coordonnées du bailleur',
            'fields' => [
                'nomProprio' => 'Nom',
                'prenomProprio' => 'Prénom',
                'mailProprio' => 'E-mail',
                'telProprio' => 'Téléphone',
                'telProprioSecondaire' => 'Téléphone secondaire',
                'adresseProprio' => 'Adresse',
                'codePostalProprio' => 'Code postal',
                'villeProprio' => 'Ville',
            ],
        ],
        self::EDIT_COORDONNEES_AGENCE => [
            'label' => 'Les coordonnées de l\'agence',
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
            ],
        ],
    ];

    public function __construct(private readonly Security $security)
    {
    }

    public function preUpdate(Signalement $signalement, PreUpdateEventArgs $event): void
    {
        // On continue de flagger qu'un changement est détecté.
        // On le fait AVANT le verrou `supports` pour que le BO puisse afficher l'info même si on ne détaille pas les changements.
        $signalement->markUpdateOccurred();

        if (!$this->supports()) { // On ne traite que les modifications de l'usager
            return;
        }

        $changes = [];
        foreach (self::EDIT_SECTIONS as $sectionKey => $sectionDefinition) {
            $fieldChanges = [];

            foreach ($sectionDefinition['fields'] as $fieldName => $label) {
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
                    'new' => $new,
                ];
            }

            if ([] !== $fieldChanges) {
                $changes[$sectionKey] = [
                    'label' => $sectionDefinition['label'],
                    'fieldChanges' => $fieldChanges,
                ];
            }
        }

        $signalement->registerChanges($changes);
    }

    private function supports(): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return false;
        }

        return in_array('ROLE_USAGER', $user->getRoles(), true);
    }
}
