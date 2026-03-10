<?php

namespace App\EventListener;

use App\Entity\Signalement;
use App\Entity\User;
use App\Service\History\EntityComparator;
use App\Utils\DateHelper;
use App\Utils\DictionaryProvider;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Signalement::class)]
class SignalementUpdatedListener
{
    private const string DATE_FORMAT = 'd/m/Y';
    public const string EDIT_COORDONNEES_BAILLEUR = 'coordonnees_bailleur';
    public const string EDIT_COORDONNEES_AGENCE = 'coordonnees_agence';
    public const string EDIT_INFORMATIONS_ASSURANCE = 'informations_assurance';
    public const string EDIT_SITUATION_FOYER = 'situation_foyer';
    public const string EDIT_INFORMATIONS_GENERALES = 'informations_generales';

    /**
     * Définition des champs suivis.
     * - Champs simples : "field" => "label"
     * - Champs JSON : "<jsonField>.<jsonProperty>" => "label".
     */
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
        self::EDIT_INFORMATIONS_ASSURANCE => [
            'label' => 'Les informations d\'assurance',
            'fields' => [
                'informationProcedure.info_procedure_reponse_assurance' => 'Réponse de l\'assurance',
                'informationProcedure.info_procedure_assurance_contactee' => 'Assurance contactée',
            ],
        ],
        self::EDIT_SITUATION_FOYER => [
            'label' => 'La situation du foyer',
            'fields' => [
                'isLogementSocial' => 'Logement social',
                'isRelogement' => 'Relogement',
                'isAllocataire' => 'Allocataire / Caisse d\'allocation',
                'dateNaissanceOccupant' => 'Date de naissance de l\'occupant',
                'numAllocataire' => 'Numéro d\'allocataire / de dossier',
                'informationComplementaire.informations_complementaires_situation_occupants_type_allocation' => 'Type d\'allocation',
                'montantAllocation' => 'Montant de l\'allocation',
                'situationFoyer.travailleur_social_quitte_logement' => 'Souhaite quitter le logement',
                'situationFoyer.travailleur_social_preavis_depart' => 'Préavis de départ déposé',
                'situationFoyer.travailleur_social_accompagnement' => 'Accompagnement travailleur social',
                'situationFoyer.travailleur_social_accompagnement_nom_structure' => 'Nom de la structure d\'accompagnement',
                'informationComplementaire.informations_complementaires_situation_occupants_beneficiaire_rsa' => 'Bénéficiaire du RSA',
                'informationComplementaire.informations_complementaires_situation_occupants_beneficiaire_fsl' => 'Bénéficiaire du FSL',
                'informationComplementaire.informations_complementaires_situation_occupants_revenu_fiscal' => 'Revenu fiscal de référence',
                'informationProcedure.info_procedure_depart_apres_travaux' => 'Rester si travaux faits',
            ],
        ],
        self::EDIT_INFORMATIONS_GENERALES => [
            'label' => 'Les informations générales',
            'fields' => [
                'dateEntree' => 'Date d\'entrée dans le logement',
                'nbOccupantsLogement' => 'Nombre de personnes occupant le logement',
                'numeroInvariant' => 'Invariant fiscal',
                'loyer' => 'Montant du loyer',
                'typeCompositionLogement.composition_logement_nombre_enfants' => 'Nombre d\'enfants occupant le logement',
                'typeCompositionLogement.composition_logement_enfants' => 'Présence d\'enfants de moins de 6 ans',
                'typeCompositionLogement.bail_dpe_bail' => 'Contrat de location (bail)',
                'typeCompositionLogement.bail_dpe_etat_des_lieux' => 'Etat des lieux',
                'typeCompositionLogement.bail_dpe_dpe' => 'Diagnostic performance énergétique (DPE)',
                'typeCompositionLogement.bail_dpe_classe_energetique' => 'Classe énergétique du logement',
                'informationComplementaire.informations_complementaires_situation_bailleur_date_effet_bail' => 'Date d\'effet du bail',
                'informationComplementaire.informations_complementaires_situation_occupants_loyers_payes' => 'Paiement des loyers à jour',
                'informationComplementaire.informations_complementaires_logement_annee_construction' => 'Année de construction du logement',
            ],
        ],
    ];

    private const array JSON_FIELDS = [
        'typeCompositionLogement',
        'situationFoyer',
        'informationProcedure',
        'informationComplementaire',
    ];

    public function __construct(
        private readonly Security $security,
        private readonly EntityComparator $entityComparator,
        private readonly DictionaryProvider $dictionaryProvider,
    ) {
    }

    /**
     * @throws \ReflectionException
     */
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

            foreach ($sectionDefinition['fields'] as $field => $label) {
                if (!str_contains($field, '.')) {
                    if (!$event->hasChangedField($field)) {
                        continue;
                    }

                    $old = $event->getOldValue($field);
                    $new = $event->getNewValue($field);

                    // Si c'est un champ de type DateTimeImmutable, on formate la date pour que ce soit plus lisible dans le suivi
                    if ($new instanceof \DateTimeImmutable) {
                        $new = $new->format(self::DATE_FORMAT);
                    }
                    if ($old instanceof \DateTimeImmutable) {
                        $old = $old->format(self::DATE_FORMAT);
                    }

                    if ($old === $new) {
                        continue;
                    }

                    if ('boolean' === gettype($new)) {
                        $new = $new ? 'Oui' : 'Non';
                    }

                    $fieldChanges[$field] = [
                        'label' => $label,
                        'new' => $new,
                    ];

                    continue;
                }

                $parsed = $this->parseJsonPath($field); // ex: information_procedure.info_procedure_assurance_contactee
                if (null === $parsed) {
                    continue;
                }

                $jsonField = $parsed['jsonField']; // ex: information_procedure
                $jsonProperty = $parsed['jsonProperty'];   // ex: info_procedure_assurance_contactee

                if (!$event->hasChangedField($jsonField)) {
                    continue;
                }

                $oldValue = $this->entityComparator->processValue($event->getOldValue($jsonField) ?? []);
                $newValue = $this->entityComparator->processValue($event->getNewValue($jsonField) ?? []);
                $fieldsChanges = $this->entityComparator->compareValues($oldValue, $newValue, $jsonField);

                // la propriété déclarée n'a pas changé
                if (!isset($fieldsChanges[$jsonProperty])
                    || [] === ($diffProperty = $fieldsChanges[$jsonProperty])
                ) {
                    continue;
                }

                $fieldChanges[$field] = [
                    'label' => $label,
                    'new' => $this->formatValue(
                        $diffProperty['new'],
                        fn (string $v) => $this->dictionaryProvider->translate($v, 'suivi')
                    ),
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

    private function formatValue(?string $value, callable $fallback): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $dateFormatted = DateHelper::formatDateString($value, 'Y-m-d', self::DATE_FORMAT);
        if (false !== $dateFormatted) {
            return $dateFormatted;
        }

        return $fallback($value);
    }

    /**
     * Parse "<jsonField>.<jsonProperty>".
     *
     * @return array{jsonField:string,jsonProperty:string}|null
     */
    private function parseJsonPath(string $path): ?array
    {
        $parts = explode('.', $path, 2);

        if (2 !== count($parts)) {
            return null;
        }

        [$jsonField, $jsonProperty] = $parts;

        if (!in_array($jsonField, self::JSON_FIELDS, true)) {
            return null;
        }

        if ('' === $jsonProperty) {
            return null;
        }

        return [
            'jsonField' => $jsonField,
            'jsonProperty' => $jsonProperty,
        ];
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
