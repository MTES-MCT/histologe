<?php

namespace App\Tests\Functional\Service\Import\AutoAffectationRule;

use App\Entity\AutoAffectationRule;
use App\Entity\Territory;
use App\Repository\AutoAffectationRuleRepository;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Service\Import\AutoAffectationRule\AutoAffectationRuleHeader;
use App\Service\Import\AutoAffectationRule\AutoAffectationRuleLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AutoAffectationRuleLoaderTest extends KernelTestCase
{
    private AutoAffectationRuleLoader $loader;
    private EntityManagerInterface $entityManager;
    private Territory $herault;
    private const string HERAULT_ZIP = '34';
    private const string HERAULT_NAME = 'Hérault';

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $em;
        /** @var Territory $herault */
        $herault = static::getContainer()->get(TerritoryRepository::class)
            ->findOneBy(['zip' => self::HERAULT_ZIP, 'name' => self::HERAULT_NAME]);
        $this->herault = $herault;
        $this->loader = new AutoAffectationRuleLoader(
            static::getContainer()->get(AutoAffectationRuleRepository::class),
            static::getContainer()->get(PartnerRepository::class),
            $this->entityManager,
        );
    }

    public function testValidateReturnsNoErrorsForValidData(): void
    {
        $errors = $this->loader->validate($this->provideValidData(), $this->herault);

        $this->assertEmpty($errors);
    }

    public function testValidateReturnsErrorForInvalidStatus(): void
    {
        $errors = $this->loader->validate([$this->buildRow(status: 'INVALIDE')], $this->herault);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Statut "INVALIDE" invalide', $errors[0]);
    }

    public function testValidateReturnsErrorForInvalidPartnerType(): void
    {
        $errors = $this->loader->validate([$this->buildRow(partnerType: 'Type Inconnu')], $this->herault);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Type de partenaire "Type Inconnu" invalide', $errors[0]);
    }

    public function testValidateReturnsErrorForInvalidProfileDeclarant(): void
    {
        $errors = $this->loader->validate([$this->buildRow(profileDeclarant: 'profil_inconnu')], $this->herault);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Profil déclarant "profil_inconnu" invalide', $errors[0]);
    }

    public function testValidateReturnsErrorForInvalidParc(): void
    {
        $errors = $this->loader->validate([$this->buildRow(parc: 'inconnu')], $this->herault);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Parc "inconnu" invalide', $errors[0]);
    }

    public function testValidateReturnsErrorForInvalidAllocataire(): void
    {
        $errors = $this->loader->validate([$this->buildRow(allocataire: 'inconnu')], $this->herault);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Allocataire "inconnu" invalide', $errors[0]);
    }

    public function testValidateAccumulatesMultipleErrorsOnSameLine(): void
    {
        $errors = $this->loader->validate(
            [$this->buildRow(status: 'INVALIDE', parc: 'inconnu', allocataire: 'inconnu')],
            $this->herault,
        );

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Ligne 2', $errors[0]);
        $this->assertStringContainsString('Statut', $errors[0]);
        $this->assertStringContainsString('Parc', $errors[0]);
        $this->assertStringContainsString('Allocataire', $errors[0]);
    }

    public function testValidateDetectsDuplicateWithinBatch(): void
    {
        $row = $this->buildRow(allocataire: 'nsp');
        $errors = $this->loader->validate([$row, $row], $this->herault);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('doublon dans le fichier CSV', $errors[0]);
    }

    public function testValidateErrorLineNumberStartsAtTwo(): void
    {
        $errors = $this->loader->validate(
            [$this->buildRow(status: 'INVALIDE'), $this->buildRow(parc: 'inconnu')],
            $this->herault,
        );

        $this->assertStringContainsString('Ligne 2', $errors[0]);
        $this->assertStringContainsString('Ligne 3', $errors[1]);
    }

    public function testLoadPersistsRulesInDatabase(): void
    {
        $countBefore = $this->entityManager->getRepository(AutoAffectationRule::class)
            ->count(['territory' => $this->herault]);

        $this->loader->load($this->provideValidData(), $this->herault);

        $countAfter = $this->entityManager->getRepository(AutoAffectationRule::class)
            ->count(['territory' => $this->herault]);
        $this->assertSame($countBefore + 2, $countAfter);
    }

    public function testLoadUpdatesNbRulesCreatedMetadata(): void
    {
        $this->loader->load($this->provideValidData(), $this->herault);

        $this->assertSame(2, $this->loader->getMetadata()['nb_rules_created']);
    }

    public function testLoadArchivesExistingActiveRules(): void
    {
        $ruleRepo = $this->entityManager->getRepository(AutoAffectationRule::class);
        $countActiveBefore = $ruleRepo->count(['territory' => $this->herault, 'status' => AutoAffectationRule::STATUS_ACTIVE]);
        $this->assertGreaterThan(0, $countActiveBefore);

        $this->loader->load($this->provideValidData(), $this->herault);

        // All previous active rules are archived; only the 2 newly created ones remain active
        $this->assertSame(2, $ruleRepo->count(['territory' => $this->herault, 'status' => AutoAffectationRule::STATUS_ACTIVE]));
        $this->assertSame($countActiveBefore, $this->loader->getMetadata()['nb_rules_archived']);
    }

    public function testLoadMetadataHasZeroArchivedWhenNoExistingRules(): void
    {
        /** @var Territory $ain */
        $ain = static::getContainer()->get(TerritoryRepository::class)->findOneBy(['zip' => '01', 'name' => 'Ain']);
        $this->assertNotNull($ain);

        $this->loader->load([$this->buildRow()], $ain);

        $this->assertSame(0, $this->loader->getMetadata()['nb_rules_archived']);
    }

    public function testLoadSetsAllRuleFieldsCorrectly(): void
    {
        $row = $this->buildRow(
            partnerType: 'CCAS',
            profileDeclarant: 'LOCATAIRE',
            parc: 'public',
            allocataire: 'oui',
            inseeToInclude: '34172,34173',
            inseeToExclude: '34001',
            partnerToExclude: '999',
        );

        $this->loader->load([$row], $this->herault);

        $rule = $this->entityManager->getRepository(AutoAffectationRule::class)->findOneBy([
            'territory' => $this->herault,
            'parc' => 'public',
            'allocataire' => 'oui',
        ]);

        $this->assertNotNull($rule);
        $this->assertSame(AutoAffectationRule::STATUS_ACTIVE, $rule->getStatus());
        $this->assertSame('LOCATAIRE', $rule->getProfileDeclarant());
        $this->assertSame('34172,34173', $rule->getInseeToInclude());
        $this->assertSame(['34001'], $rule->getInseeToExclude());
        $this->assertSame(['999'], $rule->getPartnerToExclude());
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function provideValidData(): array
    {
        return [
            $this->buildRow(allocataire: 'msa'),
            $this->buildRow(partnerType: 'CCAS', allocataire: 'all'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildRow(
        string $status = 'ACTIVE',
        string $partnerType = 'CAF / MSA',
        string $profileDeclarant = 'all',
        string $parc = 'prive',
        string $allocataire = 'all',
        string $inseeToInclude = '/',
        string $inseeToExclude = '/',
        string $partnerToExclude = '/',
        string $proceduresSuspectees = '/',
    ): array {
        return [
            AutoAffectationRuleHeader::STATUS => $status,
            AutoAffectationRuleHeader::PARTNER_TYPE => $partnerType,
            AutoAffectationRuleHeader::PROFILE_DECLARANT => $profileDeclarant,
            AutoAffectationRuleHeader::PARC => $parc,
            AutoAffectationRuleHeader::ALLOCATAIRE => $allocataire,
            AutoAffectationRuleHeader::INSEE_TO_INCLUDE => $inseeToInclude,
            AutoAffectationRuleHeader::INSEE_TO_EXCLUDE => $inseeToExclude,
            AutoAffectationRuleHeader::PARTNER_TO_EXCLUDE => $partnerToExclude,
            AutoAffectationRuleHeader::PROCEDURES_SUSPECTEES => $proceduresSuspectees,
        ];
    }
}
