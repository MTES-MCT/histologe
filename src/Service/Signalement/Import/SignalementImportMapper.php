<?php

namespace App\Service\Signalement\Import;

use App\Entity\Enum\MotifCloture;

class SignalementImportMapper
{
    private const SITUATION_SECURITE_OCCUPANT = 'Sécurité des occupants';
    private const SITUATION_ETAT_PROPRETE_LOGEMENT = 'Etat et propreté du logement';
    private const SITUATION_CONFORT_LOGEMENT = 'confort du logement';
    private const SITUATION_ETAT_BATIMENT = 'Etat du bâtiment';
    private const SITUATION_ESPACE_VIE = 'Les espaces de vie';
    private const SITUATION_VIE_COMMUNE_VOISINAGE = 'Vie commune et voisinage';

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
            'Signalement - Securite occupants 1' => self::SITUATION_SECURITE_OCCUPANT,
            'Signalement - Securite occupants 2' => self::SITUATION_SECURITE_OCCUPANT,
            'Signalement - Securite occupants 3' => self::SITUATION_SECURITE_OCCUPANT,
            'Signalement - Etat & Proprete logement 1' => self::SITUATION_ETAT_PROPRETE_LOGEMENT,
            'Signalement - Etat & Proprete logement 2' => self::SITUATION_ETAT_PROPRETE_LOGEMENT,
            'Signalement - Etat & Proprete logement 3' => self::SITUATION_ETAT_PROPRETE_LOGEMENT,
            'Signalement - Confort logement 1' => self::SITUATION_CONFORT_LOGEMENT,
            'Signalement - Confort logement 2' => self::SITUATION_CONFORT_LOGEMENT,
            'Signalement - Confort logement 3' => self::SITUATION_CONFORT_LOGEMENT,
            'Signalement - Etat batiment 1' => self::SITUATION_ETAT_BATIMENT,
            'Signalement - Etat batiment 2' => self::SITUATION_ETAT_BATIMENT,
            'Signalement - Etat batiment 3' => self::SITUATION_ETAT_BATIMENT,
            'Signalement - Espaces de vie 1' => self::SITUATION_ESPACE_VIE,
            'Signalement - Espaces de vie 2' => self::SITUATION_ESPACE_VIE,
            'Signalement - Espaces de vie 3' => self::SITUATION_ESPACE_VIE,
            'Signalement - Vie commune & voisinage 1' => self::SITUATION_VIE_COMMUNE_VOISINAGE,
            'Signalement - Vie commune & voisinage 2' => self::SITUATION_VIE_COMMUNE_VOISINAGE,
            'Signalement - Vie commune & voisinage 3' => self::SITUATION_VIE_COMMUNE_VOISINAGE,
        ];
    }

    public function map(array $columns, array $data): array
    {
        $dataMapped = [];
        if (1 === \count($data) || empty($data['Ref signalement'])) {
            return $dataMapped;
        }
        $situations = [];
        foreach ($this->getMapping() as $fileColumn => $fieldColumn) {
            if (\in_array($fileColumn, $columns)) {
                $fieldValue = 'NSP' !== $data[$fileColumn] ? $data[$fileColumn] : null;
                $fieldValue = trim($fieldValue, '"');
                switch ($fieldColumn) {
                    case 'reference':
                        list($index, $year) = preg_split('(-|/)', $fieldValue);
                        $fieldValue = '20'.$year.'-'.$index;
                        break;
                    case 'modeContactProprio':
                        $modes = array_filter(preg_split('(-|/)', $fieldValue));
                        $fieldValue = empty($modes) ? null : $modes;
                        break;
                    case 'isProprioAverti':
                    case 'isAllocataire':
                    case 'isRisqueSurOccupation':
                    case 'isConstructionAvant1949':
                    case 'isLogementCollectif':
                    case 'isConsentementTiers':
                    case 'isOccupantPresentVisite':
                    case 'isSituationHandicap':
                    case 'isLogementSocial':
                    case 'isPreavisDepart':
                    case 'isRelogement':
                    case 'isNotOccupant':
                    case 'isFondSolidariteLogement':
                    case 'isBailEnCours':
                    case 'isRefusIntervention':
                    case 'isCguAccepted':
                        $fieldValue = 'NSP' !== $data[$fileColumn] ? 'O' === $fieldValue : null;
                        break;
                    case 'createdAt':
                    case 'modifiedAt':
                    case 'closedAt':
                    case 'dateEntree':
                    case 'prorioAvertiAt':
                    case 'dateVisite':
                    case 'naissanceOccupantAt':
                        $date = \DateTimeImmutable::createFromFormat('d/m/y', $fieldValue);
                        if (false === $date) {
                            $date = \DateTimeImmutable::createFromFormat('Y/m/d', $fieldValue);
                        }
                        if (false === $date) {
                            $date = \DateTimeImmutable::createFromFormat('d/m/Y', $fieldValue);
                        }
                        $fieldValue = false !== $date ? $date : null;
                        break;
                    case 'superficie':
                    case 'loyer':
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
                    case self::SITUATION_SECURITE_OCCUPANT:
                    case self::SITUATION_ETAT_PROPRETE_LOGEMENT:
                    case self::SITUATION_CONFORT_LOGEMENT:
                    case self::SITUATION_ETAT_BATIMENT:
                    case self::SITUATION_ESPACE_VIE:
                    case self::SITUATION_VIE_COMMUNE_VOISINAGE:
                        $situations[$fieldColumn] = $fieldValue;
                        break;
                    default:
                }

                if (str_contains($fileColumn, 'Signalement - ')) {
                    if (!empty($situations[$fieldColumn])) {
                        $critere = explode(' - ', $situations[$fieldColumn]);
                        if (\count($critere) > 1) {
                            list($critereLabel, $etat) = $critere;
                            $dataMapped[$fieldColumn][trim($critereLabel)] = trim($etat) ?? 'mauvais état';
                        } else {
                            $dataMapped[$fieldColumn][trim($critere[0])] = 'mauvais état';
                        }
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
