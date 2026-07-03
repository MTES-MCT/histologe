<?php

namespace App\EventListener;

use App\Entity\Enum\SuiviDelayedType;
use App\Entity\Signalement;
use App\Entity\User;
use App\Security\User\SignalementUser;
use App\Service\History\EntityComparator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Signalement::class)]
class SignalementUpdatedListener
{
    private const string DATE_FORMAT = 'd/m/Y';

    /**
     * Définition des champs suivis.
     * - Champs simples : "field" => "label"
     * - Champs JSON : "<jsonField>.<jsonProperty>" => "label".
     */
    public const array EDIT_SECTIONS = [
        SuiviDelayedType::FO_EDIT_ADRESSE_LOGEMENT->value => [
            'fields' => [
                'etageOccupant' => 'Étage',
                'escalierOccupant' => 'Escalier',
                'numAppartOccupant' => 'Numéro d\'appartement',
                'adresseAutreOccupant' => 'Autre',
            ],
        ],
        SuiviDelayedType::FO_EDIT_COORDONNEES_OCCUPANT->value => [
            'fields' => [
                'civiliteOccupant' => 'Civilité',
                'nomOccupant' => 'Nom',
                'prenomOccupant' => 'Prénom',
                'telOccupant' => 'Téléphone',
                'telOccupantSecondaire' => 'Téléphone secondaire',
            ],
        ],
        SuiviDelayedType::FO_EDIT_COORDONNEES_BAILLEUR->value => [
            'fields' => [
                'nomProprio' => 'Nom',
                'prenomProprio' => 'Prénom',
                'mailProprio' => 'E-mail',
                'telProprio' => 'Téléphone',
                'telProprioSecondaire' => 'Téléphone secondaire',
                'adresseProprio' => 'Adresse',
                'codePostalProprio' => 'Code postal',
                'villeProprio' => 'Ville',
                'isProprioAverti' => 'Le bailleur a-t-il été averti ?',
                'proprioAvertiAt' => 'Date d\'information du bailleur',
                'informationProcedure.info_procedure_bail_moyen' => 'Moyen d\'information du bailleur',
                'informationProcedure.info_procedure_bail_reponse' => 'Réponse du bailleur',
                'informationProcedure.info_procedure_bail_numero' => 'Numéro de réclamation fourni par le bailleur',
            ],
        ],
        SuiviDelayedType::FO_EDIT_COORDONNEES_AGENCE->value => [
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
        SuiviDelayedType::FO_EDIT_COORDONNEES_SYNDIC->value => [
            'fields' => [
                'denominationSyndic' => 'Dénomination',
                'nomSyndic' => 'Nom',
                'mailSyndic' => 'E-mail',
                'telSyndic' => 'Téléphone',
                'telSyndicSecondaire' => 'Téléphone secondaire',
            ],
        ],
        SuiviDelayedType::FO_EDIT_INFORMATIONS_ASSURANCE->value => [
            'fields' => [
                'informationProcedure.info_procedure_reponse_assurance' => 'Réponse de l\'assurance',
                'informationProcedure.info_procedure_assurance_contactee' => 'Assurance contactée',
            ],
        ],
        SuiviDelayedType::FO_EDIT_SITUATION_FOYER->value => [
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
                'situationFoyer.travailleur_social_accompagnement_nom_referent' => 'Nom du référent de la structure d\'accompagnement',
                'situationFoyer.travailleur_social_accompagnement_prenom_referent' => 'Prénom du référent de la structure d\'accompagnement',
                'informationComplementaire.informations_complementaires_situation_occupants_beneficiaire_rsa' => 'Bénéficiaire du RSA',
                'informationComplementaire.informations_complementaires_situation_occupants_beneficiaire_fsl' => 'Bénéficiaire du FSL',
                'informationComplementaire.informations_complementaires_situation_occupants_revenu_fiscal' => 'Revenu fiscal de référence',
                'informationProcedure.info_procedure_depart_apres_travaux' => 'Rester si travaux faits',
            ],
        ],
        SuiviDelayedType::FO_EDIT_INFORMATIONS_GENERALES->value => [
            'fields' => [
                'dateEntree' => 'Date d\'entrée dans le logement',
                'nbOccupantsLogement' => 'Nombre de personnes occupant le logement',
                'numeroInvariant' => 'Invariant fiscal',
                'loyer' => 'Montant du loyer',
                'autresOccupantsDesordre' => 'Autres occupants de l\'immeuble ayant rencontré des désordres',
                'typeCompositionLogement.composition_logement_nombre_enfants' => 'Nombre d\'enfants occupant le logement',
                'typeCompositionLogement.composition_logement_enfants' => 'Présence d\'enfants de moins de 6 ans',
                'autreSituationVulnerabilite' => 'Autre situation de vulnérabilité',
                'typeCompositionLogement.bail_dpe_bail' => 'Contrat de location (bail)',
                'typeCompositionLogement.bail_dpe_etat_des_lieux' => 'Etat des lieux',
                'typeCompositionLogement.bail_dpe_dpe' => 'Diagnostic performance énergétique (DPE)',
                'typeCompositionLogement.bail_dpe_classe_energetique' => 'Classe énergétique du logement',
                'informationComplementaire.informations_complementaires_situation_bailleur_date_effet_bail' => 'Date d\'effet du bail',
                'informationComplementaire.informations_complementaires_situation_occupants_loyers_payes' => 'Paiement des loyers à jour',
                'informationComplementaire.informations_complementaires_logement_annee_construction' => 'Année de construction du logement',
            ],
        ],
        SuiviDelayedType::FO_EDIT_TYPE_COMPOSITION->value => [
            'fields' => [
                'natureLogement' => 'Nature du logement',
                'superficie' => 'Superficie du logement',
                'typeCompositionLogement.type_logement_nature_autre_precision' => 'Précision sur la nature du logement',
                'typeCompositionLogement.type_logement_appartement_etage' => 'Étage',
                'typeCompositionLogement.type_logement_appartement_avec_fenetres' => 'Avec fenêtres',
                'typeCompositionLogement.composition_logement_piece_unique' => 'Une seule ou plusieurs pièces',
                'typeCompositionLogement.composition_logement_nb_pieces' => 'Nombre de pièces à vivre',
                'typeCompositionLogement.type_logement_commodites_piece_a_vivre_9m' => 'Une pièce à vivre fait 9m² ou plus',
                'typeCompositionLogement.type_logement_commodites_cuisine' => 'Cuisine ou coin cuisine',
                'typeCompositionLogement.type_logement_commodites_cuisine_collective' => 'Accès à une cuisine collective',
                'typeCompositionLogement.type_logement_commodites_salle_de_bain' => 'Salle de bain',
                'typeCompositionLogement.type_logement_commodites_salle_de_bain_collective' => 'Accès à une salle de bain collective',
                'typeCompositionLogement.type_logement_commodites_wc' => 'Toilettes (WC)',
                'typeCompositionLogement.type_logement_commodites_wc_collective' => 'Accès à des toilettes collectives',
                'typeCompositionLogement.type_logement_commodites_wc_cuisine' => 'Toilettes et cuisine dans la même pièce',
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

                    $fieldChanges[$label] = $new;

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

                if (array_key_exists('new', $diffProperty)) {
                    $fieldChanges[$label] = $diffProperty['new'];
                }
            }

            if ([] !== $fieldChanges) {
                $changes = [
                    'suiviDelayedType' => $sectionKey,
                    'fieldChanges' => $fieldChanges,
                ];
                break;
            }
        }

        $signalement->registerChanges($changes);
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

        return $user instanceof SignalementUser;
    }
}
