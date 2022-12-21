<?php

namespace App\Service\Signalement\Import;

use App\Entity\Enum\MotifCloture;

class SignalementImportMapper
{
    public function getMapping(): array
    {
        return [
            'Ref signalement' => 'reference',
            'Date de creation signalement' => 'createdAt',
            'Date cloture' => 'closedAt',
            'motif_cloture' => 'motifCloture',
            'ref des photos' => 'photos',
            'ref des documents' => 'documents',
            'details' => 'details',
            'Propriétaire averti' => 'isProprioAverti',
            'Date proprietaire averti' => 'prorioAvertiAt',
            'nb d\'adultes' => 'nbAdultes',
            'nb d enfants <6ans' => 'nbEnfantsM6',
            'nb d enfants >6ans' => 'nbEnfantsP6',
            'nb occupants logement' => 'nbOccupantsLogement',
            'Allocataire' => 'isAllocataire',
            'numéro Allocataire' => 'numAllocataire',
            'type logement' => 'typeLogement',
            'superficie' => 'superficie',
            'Nom propriétaire' => 'nomProprio',
            'Adresse Propriétaire' => 'adresseProprio',
            'Telephone Proprietaire' => 'telProprio',
            'Mail Propriétaire' => 'mailProprio',
            'Logement social' => 'isLogementSocial',
            'Préavis de départ donné' => 'isPreavisDepart',
            'Demande de Relogement en cours?' => 'isRelogement',
            'Déclarant est l occupant?' => 'isNotOccupant',
            'nom du declarant' => 'nomDeclarant',
            'prenom declarant' => 'prenomDeclarant',
            'telephone declarant' => 'telDeclarant',
            'mail declarant' => 'mailDeclarant',
            'lien entre declarant et occupant' => 'lienDeclarantOccupant',
            'nom structure declarant si tiers professionnel' => 'structureDeclarant',
            'nom occupant' => 'nomOccupant',
            'prenom occupant' => 'prenomOccupant',
            'telephone occupant' => 'telOccupant',
            'mail occupant' => 'mailOccupant',
            'adresse occupant' => 'adresseOccupant',
            'Code postal occupant' => 'cpOccupant',
            'ville occupant' => 'villeOccupant',
            'code insee occupant' => 'inseeOccupant',
            'date visite' => 'dateVisite',
            'Occupant présent lors de la visite ?' => 'isOccupantPresentVisite',
            'Occupant en situation handicap' => 'isSituationHandicap',
            'etage occupant' => 'etageOccupant',
            'escalier occupant' => 'escalierOccupant',
            'numéro appartement  occupant' => 'numAppartOccupant',
            'mode contact propriétaire  ?' => 'modeContactProprio',
            'RSA' => 'isRsa',
            'Logement < 1948' => 'isConstructionAvant1949',
            'Fond solidarite logement' => 'isFondSolidariteLogement',
            'Risque de suroccupation' => 'isRisqueSurOccupation',
            'numero invariant' => 'numeroInvariant',
            'Nature du logement' => 'natureLogement',
            'loyer' => 'loyer',
            'Bail en cours' => 'isBailEnCours',
            'date entree bail' => 'dateEntree',
            'Occupant Accepte visite/travaux ?' => 'isRefusIntervention',
            'Occupant refuse visite/ Motif' => 'raisonRefusIntervention',
            'CGU acceptees' => 'isCguAccepted',
            'Date modification / maj' => 'modifiedAt',
            'statut' => 'statut',
            'geoloc' => 'geoloc',
            'montant allocation' => 'montantAllocation',
            'code procedure en cours' => 'codeProcedure',
            'adresse_autre_occupant' => 'adresseAutreOccupant',
            'Accord occupant declaration par tiers' => 'isConsentementTiers',
            'annee construction immeuble' => 'anneeConstruction',
            'type energie logement' => 'typeEnergieLogement',
            'origine signalement' => 'origineSignalement',
            'situation occupant' => 'situationOccupant',
            'situation pro occupant' => 'situationProOccupant',
            'naissance occupant' => 'naissanceOccupantAt',
            'logement collectif' => 'isLogementCollectif',
            'nom du referent social' => 'nomReferentSocial',
            'structure referent social' => 'StructureReferentSocial',
            'mail syndic' => 'mailSyndic',
            'telelephone syndic' => 'telSyndic',
            'nom syndic' => 'nomSyndic',
            'nom sci' => 'nomSci',
            'nom representant sci' => 'nomRepresentantSci',
            'telephone sci' => 'telSci',
            'mail sci' => 'mailSci',
            'nb de pieces du logement' => 'nbPiecesLogement',
            'nb chambres logement' => 'nbChambresLogement',
            'nb niveaux logement' => 'nbNiveauxLogement',
            'qualification' => 'tags',
            'Partenaires à affecter' => 'partners',
            'Signalement - Securite occupants 1' => 'Sécurité des occupants',
            'Signalement - Securite occupants 2' => 'Sécurité des occupants',
            'Signalement - Securite occupants 3' => 'Sécurité des occupants',
            'Signalement - Etat & Proprete logement 1' => 'Etat et propreté du logement',
            'Signalement - Etat & Proprete logement 2' => 'Etat et propreté du logement',
            'Signalement - Etat & Proprete logement 3' => 'Etat et propreté du logement',
            'Signalement - Confort logement 1' => 'confort du logement',
            'Signalement - Confort logement 2' => 'confort du logement',
            'Signalement - Confort logement 3' => 'confort du logement',
            'Signalement - Etat batiment 1' => 'Etat du bâtiment',
            'Signalement - Etat batiment 2' => 'Etat du bâtiment',
            'Signalement - Etat batiment 3' => 'Etat du bâtiment',
            'Signalement - Espaces de vie 1' => 'Les espaces de vie',
            'Signalement - Espaces de vie 2' => 'Les espaces de vie',
            'Signalement - Espaces de vie 3' => 'Les espaces de vie',
            'Signalement - Vie commune & voisinage 1' => 'Vie commune et voisinage',
            'Signalement - Vie commune & voisinage 2' => 'Vie commune et voisinage',
            'Signalement - Vie commune & voisinage 3' => 'Vie commune et voisinage',
        ];
    }

    public function map(array $columns, array $data): array
    {
        $dataMapped = [];
        if (1 === \count($data)) {
            return $dataMapped;
        }
        $situations = [];
        foreach ($this->getMapping() as $fileColumn => $fieldColumn) {
            $foundIndex = array_search($fileColumn, $columns);
            if (false !== $foundIndex) {
                $fieldValue = 'NSP' !== $data[$fileColumn] ? $data[$fileColumn] : null;
                switch ($fieldColumn) {
                    case 'reference':
                        list($index, $year) = explode('-', $fieldValue);
                        $fieldValue = '20'.$year.'-'.$index;
                        break;
                    case 'isProprioAverti':
                    case 'isAllocataire':
                        $fieldValue = 'O' === $fieldValue;
                        break;
                    case 'createdAt':
                    case 'modifiedAt':
                    case 'closedAt':
                    case 'dateEntree':
                    case 'prorioAvertiAt':
                    case 'dateVisite':
                        $fieldValue = \DateTimeImmutable::createFromFormat('d/m/y', $fieldValue);
                        $fieldValue = false !== $fieldValue ? $fieldValue : null;
                        break;
                    case 'superficie':
                        $fieldValue = (float) $fieldValue;
                        break;
                    case 'nbAdultes':
                    case 'nbEnfantsM6':
                    case 'nbEnfantsP6':
                    case 'nbOccupantsLogement':
                    case 'nbPiecesLogement':
                    case 'nbChambresLogement':
                    case 'nbNiveauxLogement':
                        $fieldValue = (int) $fieldValue;
                        break;
                    case 'motifCloture':
                        $fieldValue = \array_key_exists($fieldValue, MotifCloture::LABEL) ? $fieldValue : 'AUTRE';
                        break;
                    case 'Sécurité des occupants':
                    case 'Etat et propreté du logement':
                    case 'confort du logement':
                    case 'Etat du bâtiment':
                    case 'Les espaces de vie':
                    case 'Vie commune et voisinage':
                        $situations[$fieldColumn] = $fieldValue;
                        break;
                    default:
                }

                if (str_contains($fileColumn, 'Signalement - ')) {
                    if (!empty($situations[$fieldColumn])) {
                        list($critereLabel, $etat) = explode('-', $situations[$fieldColumn]);
                        $dataMapped[$fieldColumn][trim($critereLabel)] = trim($etat);
                    }
                } else {
                    $dataMapped[$fieldColumn] = $fieldValue;
                }
            } else {
                $dataMapped[$fieldColumn] = null;
            }
        }

        return $dataMapped;
    }
}
