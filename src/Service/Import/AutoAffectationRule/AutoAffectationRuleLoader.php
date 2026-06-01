<?php

namespace App\Service\Import\AutoAffectationRule;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\Qualification;
use App\Entity\Territory;
use App\Repository\AutoAffectationRuleRepository;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class AutoAffectationRuleLoader
{
    private const string EMPTY_FIELD_MARKER = '/';

    private const array VALID_PARC = ['all', 'prive', 'public', 'non_renseigne'];
    private const array VALID_ALLOCATAIRE = ['all', 'oui', 'non', 'caf', 'msa', 'nsp'];

    /**
     * @var array{nb_rules_created: int, imported_territory_ids: int[], errors: string[]}
     */
    private array $metadata = [
        'nb_rules_created' => 0,
        'imported_territory_ids' => [],
        'errors' => [],
    ];

    public function __construct(
        private readonly TerritoryRepository $territoryRepository,
        private readonly AutoAffectationRuleRepository $autoAffectationRuleRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<int, array<string, string>> $data
     *
     * @return string[]
     */
    public function validate(array $data): array
    {
        $errors = [];
        $seenRuleKeys = [];

        foreach ($data as $index => $row) {
            $lineNumber = $index + 2;
            $rowErrors = '';

            $territoryValue = trim($row[AutoAffectationRuleHeader::TERRITORY] ?? '');
            $territory = $this->findTerritory($territoryValue);
            if (null === $territory) {
                $rowErrors .= sprintf('<li>Territoire "%s" introuvable </li>', $territoryValue);
            }

            $status = trim($row[AutoAffectationRuleHeader::STATUS] ?? '');
            if (!\in_array($status, [AutoAffectationRule::STATUS_ACTIVE, AutoAffectationRule::STATUS_ARCHIVED])) {
                $rowErrors .= sprintf(
                    '<li>Statut "%s" invalide (valeurs acceptées : ACTIVE, ARCHIVED) </li>',
                    $status,
                );
            }

            $partnerTypeLabel = trim($row[AutoAffectationRuleHeader::PARTNER_TYPE] ?? '');
            $partnerType = PartnerType::tryFromLabel($partnerTypeLabel);
            if (null === $partnerType) {
                // $rowErrors[] = sprintf(
                $rowErrors .= sprintf(
                    '<li>Type de partenaire "%s" invalide (valeurs acceptées : %s) </li>',
                    $partnerTypeLabel,
                    implode(', ', PartnerType::names()),
                );
            }

            $profileDeclarant = trim($row[AutoAffectationRuleHeader::PROFILE_DECLARANT] ?? '');
            if (!$this->isValidProfileDeclarant($profileDeclarant)) {
                $rowErrors .= sprintf(
                    '<li>Profil déclarant "%s" invalide (valeurs acceptées : all, tiers, occupant, %s) </li>',
                    $profileDeclarant,
                    implode(', ', ProfileDeclarant::names()),
                );
            }

            $parc = trim($row[AutoAffectationRuleHeader::PARC] ?? '');
            if (!\in_array($parc, self::VALID_PARC)) {
                $rowErrors .= sprintf(
                    '<li>Parc "%s" invalide (valeurs acceptées : %s) </li>',
                    $parc,
                    implode(', ', self::VALID_PARC),
                );
            }

            $allocataire = trim($row[AutoAffectationRuleHeader::ALLOCATAIRE] ?? '');
            if (!\in_array($allocataire, self::VALID_ALLOCATAIRE)) {
                $rowErrors .= sprintf(
                    '<li>Allocataire "%s" invalide (valeurs acceptées : %s) </li>',
                    $allocataire,
                    implode(', ', self::VALID_ALLOCATAIRE),
                );
            }

            $inseeToInclude = $this->parseInseeToInclude($row[AutoAffectationRuleHeader::INSEE_TO_INCLUDE] ?? '');
            $inseeToExclude = $this->parseArrayField($row[AutoAffectationRuleHeader::INSEE_TO_EXCLUDE] ?? '');
            $partnerToExclude = $this->parseArrayField($row[AutoAffectationRuleHeader::PARTNER_TO_EXCLUDE] ?? '');
            $rawProcedures = $row[AutoAffectationRuleHeader::PROCEDURES_SUSPECTEES] ?? '';
            $proceduresSuspectees = $this->parseProceduresSuspectees($rawProcedures);

            if ('' !== $inseeToInclude) {
                $invalidCodes = array_filter(
                    array_map('trim', explode(',', $inseeToInclude)),
                    static fn (string $code) => !preg_match('/^\d{5}$/', $code),
                );
                if (!empty($invalidCodes)) {
                    $rowErrors .= sprintf(
                        '<li>Codes INSEE à inclure invalides : "%s" (format attendu :  liste de codes insee à 5 chiffres séparés par des virgules)</li>',
                        implode('", "', $invalidCodes),
                    );
                }
            }

            if (null !== $inseeToExclude) {
                $invalidCodes = array_filter(
                    $inseeToExclude,
                    static fn (string $code) => !preg_match('/^\d{5}$/', $code),
                );
                if (!empty($invalidCodes)) {
                    $rowErrors .= sprintf(
                        '<li>Codes INSEE à exclure invalides : "%s" (format attendu :  liste de codes insee à 5 chiffres séparés par des virgules)</li>',
                        implode('", "', $invalidCodes),
                    );
                }
            }

            if (null !== $partnerToExclude) {
                $invalidIds = array_filter(
                    $partnerToExclude,
                    static fn (string $id) => !preg_match('/^\d+$/', $id),
                );
                if (!empty($invalidIds)) {
                    $rowErrors .= sprintf(
                        '<li>IDs partenaires à exclure invalides : "%s" (entiers séparés par des virgules attendus)</li>',
                        implode('", "', $invalidIds),
                    );
                } elseif (null !== $territory && null !== $partnerType) {
                    foreach ($partnerToExclude as $partnerId) {
                        // $partner = $this->partnerRepository->findOneBy([
                        //     'id' => (int) $partnerId,
                        //     'territory' => $territory,
                        //     'type' => $partnerType,
                        //     'isArchive' => false,
                        // ]);
                        // if (null === $partner) {
                        //     $rowErrors .= sprintf(
                        //         '<li>Partenaire à exclure ID %s introuvable, archivé, ou n\'appartenant pas au territoire "%s" avec le type "%s"</li>',
                        //         $partnerId,
                        //         $territory->getZipAndName(),
                        //         $partnerType->label(),
                        //     );
                        // }
                        $partner = $this->partnerRepository->findOneBy([
                            'id' => (int) $partnerId,
                        ]);
                        if (null === $partner) {
                            $rowErrors .= sprintf(
                                '<li>Partenaire à exclure ID %s introuvable</li>',
                                $partnerId,
                            );
                        } elseif ($partner->getIsArchive()) {
                            $rowErrors .= sprintf(
                                '<li>Partenaire à exclure ID %s est archivé</li>',
                                $partnerId,
                            );
                        } elseif ($partner->getTerritory()->getId() !== $territory->getId()) {
                            $rowErrors .= sprintf(
                                '<li>Partenaire à exclure ID %s n\'appartient pas au territoire "%s" </li>',
                                $partnerId,
                                $territory->getZipAndName(),
                            );
                        } elseif ($partner->getType() !== $partnerType) {
                            $rowErrors .= sprintf(
                                '<li>Partenaire à exclure ID %s n\'a pas le type "%s"</li>',
                                $partnerId,
                                $partnerType->label(),
                            );
                        }
                    }
                }
            }

            $rawProcedures = trim($rawProcedures);
            if ('' !== $rawProcedures && self::EMPTY_FIELD_MARKER !== $rawProcedures) {
                $validProcedures = Qualification::getProcedureSuspecteeList();
                foreach (explode(',', $rawProcedures) as $label) {
                    $label = trim($label);
                    $qualification = Qualification::tryFromLabel($label);
                    if (null === $qualification || !\in_array($qualification, $validProcedures)) {
                        $rowErrors .= sprintf(
                            '<li>Procédure suspectée "%s" invalide (valeurs acceptées : %s séparées par des virgules)</li>',
                            $label,
                            implode(', ', array_map(static fn (Qualification $q) => $q->label(), $validProcedures)),
                        );
                    }
                }
            }

            if (!empty($rowErrors)) {
                $errors[] = sprintf('Ligne %d : <ul>%s</ul>', $lineNumber, rtrim($rowErrors, ' / '));
                continue;
            }

            /** @var Territory $territory */
            $ruleKey = $this->buildRuleKey(
                $territory,
                $partnerType,
                $profileDeclarant,
                $parc,
                $allocataire,
                $inseeToInclude,
                $inseeToExclude,
                $partnerToExclude,
                $proceduresSuspectees,
            );

            if (\in_array($ruleKey, $seenRuleKeys)) {
                $errors[] = sprintf('Ligne %d : Cette règle est en doublon dans le fichier CSV.', $lineNumber);
                continue;
            }
            $seenRuleKeys[] = $ruleKey;

            if ($this->ruleExistsInDatabase($territory, $partnerType, $profileDeclarant, $parc, $allocataire, $inseeToInclude, $inseeToExclude, $partnerToExclude, $proceduresSuspectees)) {
                $errors[] = sprintf(
                    'Ligne %d : Une règle identique existe déjà pour le territoire "%s" (type : %s, profil : %s, parc : %s, allocataire : %s).',
                    $lineNumber,
                    $territory->getZipAndName(),
                    $partnerType->label(),
                    $profileDeclarant,
                    $parc,
                    $allocataire,
                );
            }
        }

        return $errors;
    }

    /**
     * @param array<int, array<string, string>> $data
     */
    public function load(array $data): void
    {
        foreach ($data as $row) {
            $territory = $this->findTerritory(trim($row[AutoAffectationRuleHeader::TERRITORY]));
            if (null === $territory) {
                continue;
            }

            $partnerType = PartnerType::tryFromLabel(trim($row[AutoAffectationRuleHeader::PARTNER_TYPE]));
            if (null === $partnerType) {
                continue;
            }

            $rule = new AutoAffectationRule();
            $rule->setTerritory($territory);
            $rule->setStatus(trim($row[AutoAffectationRuleHeader::STATUS]));
            $rule->setPartnerType($partnerType);
            $rule->setProfileDeclarant(trim($row[AutoAffectationRuleHeader::PROFILE_DECLARANT]));
            $rule->setParc(trim($row[AutoAffectationRuleHeader::PARC]));
            $rule->setAllocataire(trim($row[AutoAffectationRuleHeader::ALLOCATAIRE]));
            $rule->setInseeToInclude($this->parseInseeToInclude($row[AutoAffectationRuleHeader::INSEE_TO_INCLUDE]));
            $rule->setInseeToExclude($this->parseArrayField($row[AutoAffectationRuleHeader::INSEE_TO_EXCLUDE]));
            $rule->setPartnerToExclude($this->parseArrayField($row[AutoAffectationRuleHeader::PARTNER_TO_EXCLUDE]));
            $rule->setProceduresSuspectees($this->parseProceduresSuspectees($row[AutoAffectationRuleHeader::PROCEDURES_SUSPECTEES]));

            $this->entityManager->persist($rule);
            ++$this->metadata['nb_rules_created'];
            $territoryId = $territory->getId();
            if (!\in_array($territoryId, $this->metadata['imported_territory_ids'])) {
                $this->metadata['imported_territory_ids'][] = $territoryId;
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @return array{nb_rules_created: int, imported_territory_ids: int[], errors: string[]}
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    private function findTerritory(string $value): ?Territory
    {
        $parts = explode(' - ', $value, 2);
        if (2 !== \count($parts)) {
            return null;
        }

        return $this->territoryRepository->findOneBy([
            'zip' => trim($parts[0]),
            'name' => trim($parts[1]),
        ]);
    }

    private function isValidProfileDeclarant(string $value): bool
    {
        if (\in_array($value, ['all', 'tiers', 'occupant'])) {
            return true;
        }

        if (null !== ProfileDeclarant::tryFrom($value)) {
            return true;
        }

        return null !== ProfileDeclarant::tryFromLabel($value);
    }

    private function parseInseeToInclude(string $value): string
    {
        $value = trim($value);
        if ('' === $value || self::EMPTY_FIELD_MARKER === $value) {
            return '';
        }

        return $value;
    }

    /**
     * @return array<string>|null
     */
    private function parseArrayField(string $value): ?array
    {
        $value = trim($value);
        if ('' === $value || self::EMPTY_FIELD_MARKER === $value) {
            return null;
        }

        return array_filter(
            array_map('trim', explode(',', $value)),
            static fn (string $v) => '' !== $v,
        );
    }

    /**
     * @return list<Qualification>|null
     */
    private function parseProceduresSuspectees(string $value): ?array
    {
        $value = trim($value);
        if ('' === $value || self::EMPTY_FIELD_MARKER === $value) {
            return null;
        }

        $result = [];
        foreach (explode(',', $value) as $label) {
            $qualification = Qualification::tryFromLabel(trim($label));
            if (null !== $qualification) {
                $result[] = $qualification;
            }
        }

        return empty($result) ? null : $result;
    }

    /**
     * @param array<string>|null       $inseeToExclude
     * @param array<string>|null       $partnerToExclude
     * @param list<Qualification>|null $proceduresSuspectees
     */
    private function buildRuleKey(
        Territory $territory,
        PartnerType $partnerType,
        string $profileDeclarant,
        string $parc,
        string $allocataire,
        string $inseeToInclude,
        ?array $inseeToExclude,
        ?array $partnerToExclude,
        ?array $proceduresSuspectees,
    ): string {
        $sortedInseeExclude = $inseeToExclude ?? [];
        sort($sortedInseeExclude);

        $sortedPartnerExclude = $partnerToExclude ?? [];
        sort($sortedPartnerExclude);

        $sortedProcedures = array_map(
            static fn (Qualification $q) => $q->value,
            $proceduresSuspectees ?? [],
        );
        sort($sortedProcedures);

        return implode('|', [
            $territory->getId(),
            $partnerType->value,
            $profileDeclarant,
            $parc,
            $allocataire,
            $inseeToInclude,
            implode(',', $sortedInseeExclude),
            implode(',', $sortedPartnerExclude),
            implode(',', $sortedProcedures),
        ]);
    }

    /**
     * @param array<string>|null       $inseeToExclude
     * @param array<string>|null       $partnerToExclude
     * @param list<Qualification>|null $proceduresSuspectees
     */
    private function ruleExistsInDatabase(
        Territory $territory,
        PartnerType $partnerType,
        string $profileDeclarant,
        string $parc,
        string $allocataire,
        string $inseeToInclude,
        ?array $inseeToExclude,
        ?array $partnerToExclude,
        ?array $proceduresSuspectees,
    ): bool {
        $existingRules = $this->autoAffectationRuleRepository->findBy([
            'territory' => $territory,
            'partnerType' => $partnerType,
            'profileDeclarant' => $profileDeclarant,
            'parc' => $parc,
            'allocataire' => $allocataire,
            'inseeToInclude' => $inseeToInclude,
        ]);

        $newKey = $this->buildRuleKey(
            $territory,
            $partnerType,
            $profileDeclarant,
            $parc,
            $allocataire,
            $inseeToInclude,
            $inseeToExclude,
            $partnerToExclude,
            $proceduresSuspectees,
        );

        foreach ($existingRules as $existingRule) {
            $existingKey = $this->buildRuleKey(
                $existingRule->getTerritory(),
                $existingRule->getPartnerType(),
                $existingRule->getProfileDeclarant(),
                $existingRule->getParc(),
                $existingRule->getAllocataire(),
                $existingRule->getInseeToInclude(),
                $existingRule->getInseeToExclude(),
                $existingRule->getPartnerToExclude(),
                $existingRule->getProceduresSuspectees(),
            );

            if ($existingKey === $newKey) {
                return true;
            }
        }

        return false;
    }
}
