<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Address;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\SignalementUsager;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Manager\UserManager;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationList;

class SuiviTest extends KernelTestCase
{
    private Signalement $signalement;
    private User $occupant;
    private User $declarant;

    protected function setUp(): void
    {
        $this->signalement = (new Signalement())
            ->setAddress((new Address())->setTerritory(new Territory()))
            ->setNomOccupant('Martin')
            ->setPrenomOccupant('Marie')
            ->setNomDeclarant('Durand')
            ->setPrenomDeclarant('Paul')
            ->setIsNotOccupant(true);

        $this->occupant = $this->createUser([User::ROLE_USAGER], 'Marie', 'Martin');
        $this->declarant = $this->createUser([User::ROLE_USAGER], 'Paul', 'Durand');

        $signalementUsager = (new SignalementUsager())
            ->setSignalement($this->signalement)
            ->setOccupant($this->occupant)
            ->setDeclarant($this->declarant);
        (new \ReflectionProperty(Signalement::class, 'signalementUsager'))->setValue($this->signalement, $signalementUsager);
    }

    public function testCreateSuiviUsager(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $validator = static::getContainer()->get('validator');

        $faker = Factory::create();

        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->find(1);
        $userManager = static::getContainer()->get(UserManager::class);

        /** @var User $userOccupant */
        $userOccupant = $userManager->createUsagerFromSignalement($signalement, UserManager::OCCUPANT);

        $suivi = (new Suivi())
        ->setCreatedBy($userOccupant)
        ->setSignalement($signalement)
        ->setDescription($faker->text())
        ->setType(Suivi::TYPE_USAGER)
        ->setIsVisibleForUsager(true);

        /** @var ConstraintViolationList $errors */
        $errors = $validator->validate($suivi);
        $this->assertEquals(0, $errors->count());
    }

    // getCreatedByLabel : suivis automatiques

    public function testCreatedByLabelAutomaticSuiviFromCron(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::ASK_FEEDBACK_SENT);

        $this->assertSame('(Suivi automatique)', $suivi->getCreatedByLabel());
    }

    public function testCreatedByLabelAutomaticSuiviFromAgentWithPartnerOnSuivi(): void
    {
        $partner = $this->createPartner('ADIL 13');
        $suivi = $this->createSuivi(SuiviCategory::AFFECTATION_IS_ACCEPTED, $this->createAgent($partner), $partner);

        $this->assertSame('ADIL 13 : Jean DUPONT (Suivi automatique)', $suivi->getCreatedByLabel());
    }

    public function testCreatedByLabelAutomaticSuiviFromAgentWithPartnerFromUser(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::SIGNALEMENT_IS_ACTIVE, $this->createAgent($this->createPartner('ADIL 13')));

        $this->assertSame('ADIL 13 : Jean DUPONT (Suivi automatique)', $suivi->getCreatedByLabel());
    }

    public function testCreatedByLabelAutomaticSuiviFromBailleur(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI);

        $this->assertSame('BAILLEUR (Suivi automatique)', $suivi->getCreatedByLabel());
        $this->assertSame('Vous (bailleur) (Suivi automatique)', $suivi->getCreatedByLabel(forBailleur: true));
    }

    public function testCreatedByLabelAutomaticSuiviLoginBailleur(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::INJONCTION_BAILLEUR_LOGIN_BAILLEUR);

        $this->assertSame('BAILLEUR (Suivi automatique)', $suivi->getCreatedByLabel());
    }

    public function testCreatedByLabelAutomaticSuiviFromOccupant(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::NEW_DOCUMENT, $this->occupant);

        $this->assertSame('OCCUPANT : Marie MARTIN (Suivi automatique)', $suivi->getCreatedByLabel());
    }

    public function testCreatedByLabelAutomaticSuiviFromDeclarant(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::NEW_DOCUMENT, $this->declarant);

        $this->assertSame('DECLARANT : Paul DURAND (Suivi automatique)', $suivi->getCreatedByLabel());
    }

    // getCreatedByLabel : suivis agent

    public function testCreatedByLabelAgentSuivi(): void
    {
        $partner = $this->createPartner('ADIL 13');
        $suivi = $this->createSuivi(SuiviCategory::MESSAGE_PARTNER, $this->createAgent($partner), $partner);

        $this->assertSame('ADIL 13 : Jean DUPONT', $suivi->getCreatedByLabel());
    }

    public function testCreatedByLabelAgentSuiviWithArchivedPartner(): void
    {
        $partner = $this->createPartner('ADIL 13')->setIsArchive(true);
        $suivi = $this->createSuivi(SuiviCategory::MESSAGE_PARTNER, $this->createAgent($partner), $partner);

        $this->assertSame('Partenaire supprimé : Jean DUPONT', $suivi->getCreatedByLabel());
    }

    public function testCreatedByLabelAgentSuiviWithoutAgentName(): void
    {
        $partner = $this->createPartner('ADIL 13');
        $suivi = $this->createSuivi(SuiviCategory::MESSAGE_PARTNER, $this->createAgent($partner), $partner);

        $this->assertSame('ADIL 13', $suivi->getCreatedByLabel(withAgentName: false));
    }

    public function testCreatedByLabelAgentSuiviWithAgentFonction(): void
    {
        $partner = $this->createPartner('ADIL 13');
        $suivi = $this->createSuivi(SuiviCategory::MESSAGE_PARTNER, $this->createAgent($partner, 'Inspecteur'), $partner);

        $this->assertSame('ADIL 13 : Jean DUPONT (Inspecteur)', $suivi->getCreatedByLabel(withAgentFonction: true));
        $this->assertSame('ADIL 13', $suivi->getCreatedByLabel(withAgentName: false, withAgentFonction: true));
    }

    public function testCreatedByLabelAgentSuiviWithCustomSeparator(): void
    {
        $partner = $this->createPartner('ADIL 13');
        $suivi = $this->createSuivi(SuiviCategory::AFFECTATION_IS_ACCEPTED, $this->createAgent($partner), $partner);

        $this->assertSame("ADIL 13\nJean DUPONT\n(Suivi automatique)", $suivi->getCreatedByLabel("\n"));
    }

    public function testCreatedByLabelApiUserSuivi(): void
    {
        $partner = $this->createPartner('ADIL 13');
        $apiUser = $this->createUser([User::ROLE_API_USER], null, null);
        $suivi = $this->createSuivi(SuiviCategory::MESSAGE_PARTNER, $apiUser, $partner);

        $this->assertSame('ADIL 13 (Suivi créé par API)', $suivi->getCreatedByLabel());
    }

    // getCreatedByLabel : suivis bailleur

    public function testCreatedByLabelBailleurSuivi(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::MESSAGE_BAILLEUR);

        $this->assertSame('BAILLEUR', $suivi->getCreatedByLabel());
        $this->assertSame('Vous (bailleur)', $suivi->getCreatedByLabel(forBailleur: true));
    }

    public function testCreatedByLabelBailleurCommentaireSuivi(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::INJONCTION_BAILLEUR_REPONSE_COMMENTAIRE);

        $this->assertSame('BAILLEUR', $suivi->getCreatedByLabel());
    }

    // getCreatedByLabel : suivis usager

    public function testCreatedByLabelOccupantSuivi(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::MESSAGE_USAGER, $this->occupant);

        $this->assertSame('OCCUPANT : Marie MARTIN', $suivi->getCreatedByLabel());
    }

    public function testCreatedByLabelDeclarantSuivi(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::MESSAGE_USAGER, $this->declarant);

        $this->assertSame('DECLARANT : Paul DURAND', $suivi->getCreatedByLabel());
    }

    public function testCreatedByLabelUsagerSuiviNotAffectedByAgentOptions(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::MESSAGE_USAGER, $this->occupant);

        $this->assertSame('OCCUPANT : Marie MARTIN', $suivi->getCreatedByLabel(withAgentName: false));
    }

    // getCreatedByLabel : suivis usager historiques sans createdBy

    public function testCreatedByLabelLegacyUsagerSuiviSince2024(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::MESSAGE_USAGER)
            ->setCreatedAt(new \DateTimeImmutable('2024-06-01'));

        $this->assertSame('OCCUPANT/DECLARANT', $suivi->getCreatedByLabel());
    }

    public function testCreatedByLabelLegacyUsagerSuiviBefore2024(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::MESSAGE_USAGER)
            ->setCreatedAt(new \DateTimeImmutable('2023-06-01'));

        $this->assertSame('OCCUPANT/DECLARANT', $suivi->getCreatedByLabel());

        $this->signalement->setIsNotOccupant(false);
        $this->assertSame('OCCUPANT/DECLARANT', $suivi->getCreatedByLabel());
    }

    // getCreatedByLabel : cas sans auteur identifiable

    public function testCreatedByLabelNonAutomaticSuiviWithoutAuthor(): void
    {
        $suivi = $this->createSuivi(SuiviCategory::SIGNALEMENT_IS_CLOSED);

        $this->assertSame('', $suivi->getCreatedByLabel());
    }

    private function createSuivi(SuiviCategory $category, ?User $createdBy = null, ?Partner $partner = null): Suivi
    {
        return (new Suivi())
            ->setSignalement($this->signalement)
            ->setCategory($category)
            ->setCreatedBy($createdBy)
            ->setPartner($partner);
    }

    /**
     * @param array<string> $roles
     */
    private function createUser(array $roles, ?string $prenom, ?string $nom): User
    {
        return (new User())
            ->setRoles($roles)
            ->setPrenom($prenom)
            ->setNom($nom);
    }

    private function createAgent(Partner $partner, ?string $fonction = null): User
    {
        $agent = $this->createUser([User::ROLE_USER_PARTNER], 'Jean', 'Dupont')->setFonction($fonction);
        $agent->addUserPartner((new UserPartner())->setPartner($partner));

        return $agent;
    }

    private function createPartner(string $nom): Partner
    {
        return (new Partner())->setNom($nom);
    }
}
