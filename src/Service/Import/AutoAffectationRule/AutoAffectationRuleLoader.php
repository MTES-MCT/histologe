<?php

namespace App\Service\Import\AutoAffectationRule;

use App\Entity\AutoAffectationRule;
use App\Entity\Commune;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\Qualification;
use App\Entity\Territory;
use App\Repository\AutoAffectationRuleRepository;
use App\Repository\CommuneRepository;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;

class AutoAffectationRuleLoader
{
    private const string EMPTY_FIELD_MARKER = '/';

    private const array VALID_PARC = ['all', 'prive', 'public', 'non_renseigne'];
    private const array VALID_ALLOCATAIRE = ['all', 'oui', 'non', 'caf', 'msa', 'nsp'];

    /**
     * @var array{nb_rules_created: int, nb_rules_archived: int}
     */
    private array $metadata = [
        'nb_rules_created' => 0,
        'nb_rules_archived' => 0,
    ];

    public function __construct(
        private readonly AutoAffectationRuleRepository $autoAffectationRuleRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly CommuneRepository $communeRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<int, array<string, string>> $data
     *
     * @return string[]
     */
    public function validate(array $data, Territory $territory): array
    {
        $errors = [];
        $seenRuleKeys = [];

        foreach ($data as $index => $row) {
            $lineNumber = $index + 2;
            $parsed = $this->parseRow($row);
            $rowErrors = $this->buildRowErrors($parsed, $territory);

            if (!empty($rowErrors)) {
                $errors[] = sprintf('Ligne %d : <ul>%s</ul>', $lineNumber, $rowErrors);
                continue;
            }

            /** @var PartnerType $partnerType */
            $partnerType = $parsed['partnerType'];

            $ruleKey = $this->buildRuleKey(
                $territory,
                $partnerType,
                $parsed['profileDeclarant'],
                $parsed['parc'],
                $parsed['allocataire'],
                $parsed['inseeToInclude'],
                $parsed['inseeToExclude'],
                $parsed['partnerToExclude'],
                $parsed['proceduresSuspectees'],
            );

            if (\in_array($ruleKey, $seenRuleKeys)) {
                $errors[] = sprintf('Ligne %d : Cette règle est en doublon dans le fichier CSV.', $lineNumber);
                continue;
            }
            $seenRuleKeys[] = $ruleKey;
        }

        return $errors;
    }

    /**
     * @param array<int, array<string, string>> $data
     */
    public function load(array $data, Territory $territory): void
    {
        $this->archiveExistingRules($territory);

        foreach ($data as $row) {
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
            $rule->setInseeToInclude(implode(',', $this->parseArrayField($row[AutoAffectationRuleHeader::INSEE_TO_INCLUDE]) ?? []));
            $rule->setInseeToExclude($this->parseArrayField($row[AutoAffectationRuleHeader::INSEE_TO_EXCLUDE]));
            $rule->setPartnerToExclude($this->parseArrayField($row[AutoAffectationRuleHeader::PARTNER_TO_EXCLUDE]));
            $rule->setProceduresSuspectees($this->parseProceduresSuspectees($row[AutoAffectationRuleHeader::PROCEDURES_SUSPECTEES]));

            $this->entityManager->persist($rule);
            ++$this->metadata['nb_rules_created'];
        }

        $this->entityManager->flush();
    }

    /**
     * @return array{nb_rules_created: int, nb_rules_archived: int}
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    private function archiveExistingRules(Territory $territory): void
    {
        $existingRules = $this->autoAffectationRuleRepository->findBy([
            'territory' => $territory,
            'status' => AutoAffectationRule::STATUS_ACTIVE,
        ]);

        foreach ($existingRules as $rule) {
            $rule->setStatus(AutoAffectationRule::STATUS_ARCHIVED);
            ++$this->metadata['nb_rules_archived'];
        }
    }

    /**
     * @param array<string, string> $row
     *
     * @return array{status: string, partnerTypeLabel: string, partnerType: ?PartnerType, profileDeclarant: string, parc: string, allocataire: string, inseeToInclude: ?array<string>, inseeToExclude: ?array<string>, partnerToExclude: ?array<string>, rawProcedures: string, proceduresSuspectees: ?list<Qualification>}
     */
    private function parseRow(array $row): array
    {
        $partnerTypeLabel = trim($row[AutoAffectationRuleHeader::PARTNER_TYPE] ?? '');
        $rawProcedures = trim($row[AutoAffectationRuleHeader::PROCEDURES_SUSPECTEES] ?? '');

        return [
            'status' => trim($row[AutoAffectationRuleHeader::STATUS] ?? ''),
            'partnerTypeLabel' => $partnerTypeLabel,
            'partnerType' => PartnerType::tryFromLabel($partnerTypeLabel),
            'profileDeclarant' => trim($row[AutoAffectationRuleHeader::PROFILE_DECLARANT] ?? ''),
            'parc' => trim($row[AutoAffectationRuleHeader::PARC] ?? ''),
            'allocataire' => trim($row[AutoAffectationRuleHeader::ALLOCATAIRE] ?? ''),
            'inseeToInclude' => $this->parseArrayField($row[AutoAffectationRuleHeader::INSEE_TO_INCLUDE] ?? ''),
            'inseeToExclude' => $this->parseArrayField($row[AutoAffectationRuleHeader::INSEE_TO_EXCLUDE] ?? ''),
            'partnerToExclude' => $this->parseArrayField($row[AutoAffectationRuleHeader::PARTNER_TO_EXCLUDE] ?? ''),
            'rawProcedures' => $rawProcedures,
            'proceduresSuspectees' => $this->parseProceduresSuspectees($rawProcedures),
        ];
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private function buildRowErrors(array $parsed, Territory $territory): string
    {
        $territoryInseeCodes = $this->getTerritoryInseeCodes($territory);

        return $this->validateCoreFields($parsed)
            .$this->validateInseeCodes($parsed['inseeToInclude'] ?? [], 'inclure', $territory, $territoryInseeCodes)
            .$this->validateInseeCodes($parsed['inseeToExclude'] ?? [], 'exclure', $territory, $territoryInseeCodes)
            .$this->validatePartnersToExclude($parsed['partnerToExclude'], $territory, $parsed['partnerType'])
            .$this->validateProceduresSuspectees($parsed['rawProcedures']);
    }

    /**
     * @return string[]
     */
    private function getTerritoryInseeCodes(Territory $territory): array
    {
        return array_values(array_unique(array_map(
            static fn (Commune $commune): string => $commune->getCodeInsee(),
            $this->communeRepository->findBy(['territory' => $territory]),
        )));
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private function validateCoreFields(array $parsed): string
    {
        $errors = '';

        if (!\in_array($parsed['status'], [AutoAffectationRule::STATUS_ACTIVE, AutoAffectationRule::STATUS_ARCHIVED])) {
            $errors .= sprintf('<li>Statut "%s" invalide (valeurs acceptées : ACTIVE, ARCHIVED)</li>', $parsed['status']);
        }
        if (null === $parsed['partnerType']) {
            $errors .= sprintf('<li>Type de partenaire "%s" invalide (valeurs acceptées : %s)</li>', $parsed['partnerTypeLabel'], implode(', ', PartnerType::getLabelList()));
        }
        if (!$this->isValidProfileDeclarant($parsed['profileDeclarant'])) {
            $errors .= sprintf('<li>Profil déclarant "%s" invalide (valeurs acceptées : all, tiers, occupant, %s)</li>', $parsed['profileDeclarant'], implode(', ', ProfileDeclarant::names()));
        }
        if (!\in_array($parsed['parc'], self::VALID_PARC)) {
            $errors .= sprintf('<li>Parc "%s" invalide (valeurs acceptées : %s)</li>', $parsed['parc'], implode(', ', self::VALID_PARC));
        }
        if (!\in_array($parsed['allocataire'], self::VALID_ALLOCATAIRE)) {
            $errors .= sprintf('<li>Allocataire "%s" invalide (valeurs acceptées : %s)</li>', $parsed['allocataire'], implode(', ', self::VALID_ALLOCATAIRE));
        }

        return $errors;
    }

    /**
     * @param array<string> $codes
     * @param string[]      $territoryInseeCodes
     */
    private function validateInseeCodes(array $codes, string $direction, Territory $territory, array $territoryInseeCodes): string
    {
        $invalidCodes = array_filter($codes, static fn (string $code) => !(bool) preg_match('/^\d{5}$/', $code));
        $errors = '';

        if (!empty($invalidCodes)) {
            $errors .= sprintf(
                '<li>Codes INSEE %s invalides : "%s" (format attendu : liste de codes à 5 chiffres séparés par des virgules)</li>',
                $direction,
                implode('", "', $invalidCodes),
            );
        }

        $validCodes = array_filter($codes, static fn (string $code) => (bool) preg_match('/^\d{5}$/', $code));
        $unknownCodes = array_filter($validCodes, static fn (string $code) => !\in_array($code, $territoryInseeCodes, true));

        if (!empty($unknownCodes)) {
            $errors .= sprintf(
                '<li>Codes INSEE à %s hors du territoire %s : "%s"</li>',
                $direction,
                $territory->getZipAndName(),
                implode('", "', $unknownCodes),
            );
        }

        return $errors;
    }

    /**
     * @param array<string>|null $partnerToExclude
     */
    private function validatePartnersToExclude(?array $partnerToExclude, Territory $territory, ?PartnerType $partnerType): string
    {
        if (null === $partnerToExclude) {
            return '';
        }

        $invalidIds = array_filter($partnerToExclude, static fn (string $id) => !preg_match('/^\d+$/', $id));
        if (!empty($invalidIds)) {
            return sprintf('<li>IDs partenaires à exclure invalides : "%s" (entiers séparés par des virgules attendus)</li>', implode('", "', $invalidIds));
        }

        if (null === $partnerType) {
            return '';
        }

        $errors = '';
        foreach ($partnerToExclude as $partnerId) {
            $errors .= $this->validatePartnerToExclude($partnerId, $territory, $partnerType);
        }

        return $errors;
    }

    private function validatePartnerToExclude(string $partnerId, Territory $territory, PartnerType $partnerType): string
    {
        $partner = $this->partnerRepository->findOneBy(['id' => (int) $partnerId]);

        if (null === $partner) {
            return sprintf('<li>Partenaire à exclure ID %s introuvable</li>', $partnerId);
        }
        if ($partner->getIsArchive()) {
            return sprintf('<li>Partenaire à exclure ID %s est archivé</li>', $partnerId);
        }
        if ($partner->getTerritory()->getId() !== $territory->getId()) {
            return sprintf('<li>Partenaire à exclure ID %s n\'appartient pas au territoire "%s"</li>', $partnerId, $territory->getZipAndName());
        }
        if ($partner->getType() !== $partnerType) {
            return sprintf('<li>Partenaire à exclure ID %s n\'a pas le type "%s"</li>', $partnerId, $partnerType->label());
        }

        return '';
    }

    private function validateProceduresSuspectees(string $rawProcedures): string
    {
        if ('' === $rawProcedures || self::EMPTY_FIELD_MARKER === $rawProcedures) {
            return '';
        }

        $validProcedures = Qualification::getProcedureSuspecteeList();
        $validLabels = implode(', ', array_map(static fn (Qualification $q) => $q->label(), $validProcedures));
        $errors = '';

        foreach (explode(',', $rawProcedures) as $label) {
            $label = trim($label);
            $qualification = Qualification::tryFromLabel($label);
            if (null === $qualification || !\in_array($qualification, $validProcedures)) {
                $errors .= sprintf('<li>Procédure suspectée "%s" invalide (valeurs acceptées : %s)</li>', $label, $validLabels);
            }
        }

        return $errors;
    }

    private function isValidProfileDeclarant(string $value): bool
    {
        return \in_array($value, ['all', 'tiers', 'occupant'])
            || null !== ProfileDeclarant::tryFrom($value)
            || null !== ProfileDeclarant::tryFromLabel($value);
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

        return $result ?: null;
    }

    /**
     * @param array<string>|null       $inseeToInclude
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
        ?array $inseeToInclude,
        ?array $inseeToExclude,
        ?array $partnerToExclude,
        ?array $proceduresSuspectees,
    ): string {
        $sortedInseeInclude = $inseeToInclude ?? [];
        sort($sortedInseeInclude);

        $sortedInseeExclude = $inseeToExclude ?? [];
        sort($sortedInseeExclude);

        $sortedPartnerExclude = $partnerToExclude ?? [];
        sort($sortedPartnerExclude);

        $sortedProcedures = array_map(static fn (Qualification $q) => $q->value, $proceduresSuspectees ?? []);
        sort($sortedProcedures);

        return implode('|', [
            $territory->getId(),
            $partnerType->value,
            $profileDeclarant,
            $parc,
            $allocataire,
            implode(',', $sortedInseeInclude),
            implode(',', $sortedInseeExclude),
            implode(',', $sortedPartnerExclude),
            implode(',', $sortedProcedures),
        ]);
    }
}
