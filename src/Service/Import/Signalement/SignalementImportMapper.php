<?php

namespace App\Service\Import\Signalement;

use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;

class SignalementImportMapper
{
    private const SITUATION_SECURITE_OCCUPANT = 'Sécurité des occupants';
    private const SITUATION_ETAT_PROPRETE_LOGEMENT = 'Etat et propreté du logement';
    private const SITUATION_CONFORT_LOGEMENT = 'confort du logement';
    private const SITUATION_ETAT_BATIMENT = 'Etat du bâtiment';
    private const SITUATION_ESPACE_VIE = 'Les espaces de vie';
    private const SITUATION_VIE_COMMUNE_VOISINAGE = 'Vie commune et voisinage';

    private const STATUT_CSV_EN_COURS = 'en cours';
    private const STATUT_CSV_OUVERTURE = 'ouverture';
    private const STATUT_CSV_FERMETURE = 'fermeture';

    /**
     * @return array<string, string|int>
     */
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
            'etage occupant' => 'etageOccupant',
            'escalier occupant' => 'escalierOccupant',
            'numéro appartement  occupant' => 'numAppartOccupant',
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
            'naissance occupant' => 'naissanceOccupants',
            'logement collectif' => 'isLogementCollectif',
            'nom du referent social' => 'nomReferentSocial',
            'structure referent social' => 'StructureReferentSocial',
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
            'suivi' => 'suivi',
        ];
    }

    /**
     * @param array<int, string>   $columns
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function map(array $columns, array $data): array
    {
        $dataMapped = [];
        if (1 === \count($data) || empty($data['Ref signalement'])) {
            return $dataMapped;
        }
        $situations = [];
        $createdAt = $this->transformToDatetime($data['Date de creation signalement']);
        $todayYear = (new \DateTime())->format('Y');
        foreach ($this->getMapping() as $fileColumn => $fieldColumn) {
            if (\in_array($fileColumn, $columns)) {
                $fieldValue = 'NSP' !== $data[$fileColumn] ? $data[$fileColumn] : '';
                $fieldValue = trim($fieldValue, '"');
                switch ($fieldColumn) {
                    case 'reference':
                        // TODO que fait-on si on n'a pas de date de création du signalement ?
                        if (null === $createdAt) {
                            break;
                        }
                        $yearCreatedAt = $createdAt->format('Y');

                        if (preg_match('/^20\d{2}-\d+$/', $fieldValue)) {
                            list($year, $index) = explode('-', $fieldValue);
                            if ($year == $yearCreatedAt) {
                                break;
                            }
                        }

                        $digits = preg_replace('/\D/', '', $fieldValue);
                        $digits = str_replace($yearCreatedAt, '', $digits);
                        if (!empty($year)) {
                            $digits = str_replace($year, '', $digits);
                        }
                        if ('' === $digits) {
                            $digits = $fieldValue;
                        }
                        if ($yearCreatedAt === $todayYear && \strlen($digits) > 4) {
                            throw new \Exception(\sprintf('La référence %s concerne une année en cours et risque de semer la confusion', $fieldValue));
                        }
                        $fieldValue = $yearCreatedAt.'-'.$digits;
                        break;
                    case 'isProprioAverti':
                    case 'isAllocataire':
                    case 'isRisqueSurOccupation':
                    case 'isConstructionAvant1949':
                    case 'isLogementCollectif':
                    case 'isConsentementTiers':
                    case 'isOccupantPresentVisite':
                    case 'isLogementSocial':
                    case 'isPreavisDepart':
                    case 'isRelogement':
                    case 'isFondSolidariteLogement':
                    case 'isBailEnCours':
                    case 'isRefusIntervention':
                    case 'isCguAccepted':
                        $fieldValue = 'NSP' !== $data[$fileColumn] ? 'O' === $fieldValue : null;
                        break;
                    case 'isNotOccupant':
                        if ('O' == $fieldValue) {
                            $fieldValue = false;
                            break;
                        }
                        if ('N' == $fieldValue) {
                            $fieldValue = true;
                            break;
                        }
                        $fieldValue = null;
                        break;
                    case 'createdAt':
                    case 'modifiedAt':
                    case 'closedAt':
                    case 'dateEntree':
                    case 'prorioAvertiAt':
                    case 'dateVisite':
                    case 'naissanceOccupantAt':
                        $fieldValue = $this->transformToDatetime($fieldValue);
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
                        preg_match('!\d+!', $fieldValue, $matches);
                        $fieldValue = array_shift($matches);
                        break;
                    case 'statut':
                        $fieldValue = $this->transformToSignalementStatus($fieldValue);
                        break;
                    case 'motifCloture':
                        if (!$fieldValue) {
                            break;
                        }
                        if ('Abandon de procédure' == $fieldValue) {
                            $fieldValue = 'ABANDON_DE_PROCEDURE_ABSENCE_DE_REPONSE';
                            break;
                        }
                        if ('Responsabilité de l\'occupant' == $fieldValue) {
                            $fieldValue = 'RESPONSABILITE_DE_L_OCCUPANT';
                            break;
                        }
                        if ('Logement décent - Pas d\'infraction' == $fieldValue) {
                            $fieldValue = 'LOGEMENT_DECENT';
                            break;
                        }
                        $listMotifs = MotifCloture::getLabelList();
                        if (\in_array($fieldValue, $listMotifs)) {
                            $fieldValue = array_search($fieldValue, $listMotifs);
                            break;
                        }
                        if (isset($listMotifs[mb_strtoupper($fieldValue)])) {
                            $fieldValue = mb_strtoupper($fieldValue);
                            break;
                        }
                        break;
                    case self::SITUATION_SECURITE_OCCUPANT:
                    case self::SITUATION_ETAT_PROPRETE_LOGEMENT:
                    case self::SITUATION_CONFORT_LOGEMENT:
                    case self::SITUATION_ETAT_BATIMENT:
                    case self::SITUATION_ESPACE_VIE:
                    case self::SITUATION_VIE_COMMUNE_VOISINAGE:
                        $situations[$fieldColumn] = $fieldValue;
                        break;
                    case 'suivi':
                        $fieldValue = array_filter(array_map('trim', explode('-', $fieldValue)));
                        break;
                    case 'telDeclarant':
                        if (9 === \strlen($fieldValue)) {
                            $fieldValue = str_pad($fieldValue, 10, '0', \STR_PAD_LEFT);
                        }
                        break;
                    case 'nomDeclarant':
                    case 'nomOccupant':
                        $fieldValue = mb_strimwidth($fieldValue, 0, 50);
                        break;
                    default:
                }

                if (str_contains($fileColumn, 'Signalement - ')) {
                    if (!empty($situations[$fieldColumn])) {
                        $critere = explode(' - ', $situations[$fieldColumn]);
                        if (\count($critere) > 1) {
                            list($critereLabel, $etat) = $critere;
                            $dataMapped[$fieldColumn][trim($critereLabel)] = isset($etat) ? trim($etat) : 'mauvais état'; // @phpstan-ignore-line
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

        if (!str_contains($dataMapped['reference'], '-')) {
            $createdAt = $dataMapped['createdAt'];
            if ($createdAt instanceof \DateTimeImmutable) {
                $dataMapped['reference'] = $createdAt->format('Y').'-'.$dataMapped['reference'];
            } else {
                $currentDate = new \DateTimeImmutable();
                $dataMapped['reference'] = $currentDate->format('Y').'-'.$dataMapped['reference'];
            }
        }

        return $dataMapped;
    }

    private function transformToDatetime(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        $date = \DateTimeImmutable::createFromFormat('d/m/y', $value);
        if (false === $date) {
            $date = \DateTimeImmutable::createFromFormat('Y/m/d', $value);
        }
        if (false === $date) {
            $date = \DateTimeImmutable::createFromFormat('d/m/Y', $value);
        }

        return false !== $date ? $date : null;
    }

    private function transformToSignalementStatus(?string $value): ?SignalementStatus
    {
        if (self::STATUT_CSV_EN_COURS === $value || self::STATUT_CSV_OUVERTURE === $value) {
            return SignalementStatus::ACTIVE;
        }
        if (self::STATUT_CSV_FERMETURE === $value) {
            return SignalementStatus::CLOSED;
        }

        return null;
    }
}
