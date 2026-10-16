<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Enum\DocumentType;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Enum\SuiviDelayedType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\SuiviDelayed;
use App\Entity\User;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Utils\DictionaryProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SuiviManagerTest extends KernelTestCase
{
    private const string REF_SIGNALEMENT = '2022-8';
    private EventDispatcherInterface $eventDispatcherInterface;
    private Security $security;
    private HtmlSanitizerInterface $htmlSanitizerInterface;
    private UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository;
    private UserSignalementSubscriptionManager $userSignalementSubscriptionManager;
    private UserRepository $userRepository;
    private SuiviManager $suiviManager;
    private UserManager $userManager;
    private DictionaryProvider $dictionaryProvider;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->eventDispatcherInterface = static::getContainer()->get(EventDispatcherInterface::class);
        $this->security = static::getContainer()->get(Security::class);
        $this->htmlSanitizerInterface = static::getContainer()->get('html_sanitizer.sanitizer.app.message_sanitizer');
        $this->userSignalementSubscriptionManager = static::getContainer()->get(UserSignalementSubscriptionManager::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->userSignalementSubscriptionRepository = static::getContainer()->get(UserSignalementSubscriptionRepository::class);
        $this->userManager = static::getContainer()->get(UserManager::class);
        $this->dictionaryProvider = static::getContainer()->get(DictionaryProvider::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->suiviManager = new SuiviManager(
            $this->eventDispatcherInterface,
            $this->entityManager,
            $this->security,
            $this->htmlSanitizerInterface,
            $this->userSignalementSubscriptionManager,
            $this->userManager,
            true,
            static::getContainer()->get(ClockInterface::class),
            $this->dictionaryProvider,
            static::getContainer()->get(SuiviDescriptionHelper::class),
        );
    }

    public function testCreateSuivi(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(
            ['reference' => self::REF_SIGNALEMENT]
        );

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-13-03@signal-logement.fr']);

        $countSuivisBeforeCreate = $signalement->getSuivis()->count();
        $params = [
            'motif_suivi' => 'Lorem ipsum suivi sit amet, consectetur adipiscing elit.',
            'motif_cloture' => MotifCloture::tryFrom('NON_DECENCE'),
            'subject' => 'test',
        ];
        $signalement->setMotifCloture($params['motif_cloture']);
        $signalement->setClosedBy($user);
        $suivi = $this->suiviManager->createSuivi(
            signalement : $signalement,
            description : SuiviManager::buildDescriptionClotureSignalement($params),
            category: SuiviCategory::SIGNALEMENT_IS_CLOSED,
            partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory()),
            user: $user,
            isVisibleForUsager: true
        );
        $signalement->addSuivi($suivi);
        $countSuivisAfterCreate = $signalement->getSuivis()->count();

        $this->assertEquals(Suivi::TYPE_PARTNER, $suivi->getType());
        $this->assertNotEquals($countSuivisBeforeCreate, $countSuivisAfterCreate);
        $this->assertInstanceOf(Suivi::class, $suivi);
        $desc = \sprintf(SuiviDescriptionHelper::DESCRIPTION_MOTIF_CLOTURE_PARTNER, 'test').'<br /><strong>Non décence</strong><br /><strong>Desc. : </strong>Lorem ipsum suivi sit amet, consectetur adipiscing elit.';
        $this->assertEquals($desc, $suivi->getDescription());
        $this->assertTrue($suivi->getIsVisibleForUsager());
        $this->assertTrue($suivi->getIsSanitized());
        $this->assertInstanceOf(UserInterface::class, $suivi->getCreatedBy());
    }

    public function testCreateSuiviWithImageBase64(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(
            ['reference' => self::REF_SIGNALEMENT]
        );

        $desc = 'Salut ma poule <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAApgAAAKYB3X3/OAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAANCSURBVEiJtZZPbBtFFMZ/M7ubXdtdb1xSFyeilBapySVU8h8OoFaooFSqiihIVIpQBKci6KEg9Q6H9kovIHoCIVQJJCKE1ENFjnAgcaSGC6rEnxBwA04Tx43t2FnvDAfjkNibxgHxnWb2e/u992bee7tCa00YFsffekFY+nUzFtjW0LrvjRXrCDIAaPLlW0nHL0SsZtVoaF98mLrx3pdhOqLtYPHChahZcYYO7KvPFxvRl5XPp1sN3adWiD1ZAqD6XYK1b/dvE5IWryTt2udLFedwc1+9kLp+vbbpoDh+6TklxBeAi9TL0taeWpdmZzQDry0AcO+jQ12RyohqqoYoo8RDwJrU+qXkjWtfi8Xxt58BdQuwQs9qC/afLwCw8tnQbqYAPsgxE1S6F3EAIXux2oQFKm0ihMsOF71dHYx+f3NND68ghCu1YIoePPQN1pGRABkJ6Bus96CutRZMydTl+TvuiRW1m3n0eDl0vRPcEysqdXn+jsQPsrHMquGeXEaY4Yk4wxWcY5V/9scqOMOVUFthatyTy8QyqwZ+kDURKoMWxNKr2EeqVKcTNOajqKoBgOE28U4tdQl5p5bwCw7BWquaZSzAPlwjlithJtp3pTImSqQRrb2Z8PHGigD4RZuNX6JYj6wj7O4TFLbCO/Mn/m8R+h6rYSUb3ekokRY6f/YukArN979jcW+V/S8g0eT/N3VN3kTqWbQ428m9/8k0P/1aIhF36PccEl6EhOcAUCrXKZXXWS3XKd2vc/TRBG9O5ELC17MmWubD2nKhUKZa26Ba2+D3P+4/MNCFwg59oWVeYhkzgN/JDR8deKBoD7Y+ljEjGZ0sosXVTvbc6RHirr2reNy1OXd6pJsQ+gqjk8VWFYmHrwBzW/n+uMPFiRwHB2I7ih8ciHFxIkd/3Omk5tCDV1t+2nNu5sxxpDFNx+huNhVT3/zMDz8usXC3ddaHBj1GHj/As08fwTS7Kt1HBTmyN29vdwAw+/wbwLVOJ3uAD1wi/dUH7Qei66PfyuRj4Ik9is+hglfbkbfR3cnZm7chlUWLdwmprtCohX4HUtlOcQjLYCu+fzGJH2QRKvP3UNz8bWk1qMxjGTOMThZ3kvgLI5AzFfo379UAAAAASUVORK5CYII=">';
        $descSanitized = 'Salut ma poule ';

        $suivi = $this->suiviManager->createSuivi(
            signalement : $signalement,
            description : $desc,
            category: SuiviCategory::MESSAGE_USAGER,
        );
        $this->assertEquals($descSanitized, $suivi->getDescription());
        $this->assertFalse($suivi->getIsVisibleForUsager());
    }

    public function testCreateSuiviWithAutoSubscription(): void
    {
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000008']);
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-34-02@signal-logement.fr']);

        $suivi = $this->suiviManager->createSuivi(
            signalement : $signalement,
            description : 'prise en charge du signalement',
            category: SuiviCategory::MESSAGE_PARTNER,
            partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory()),
            user : $user,
        );
        $this->entityManager->flush();

        $sub = $this->userSignalementSubscriptionRepository->findBy([
            'signalement' => $signalement,
            'user' => $user,
        ]);
        $this->assertCount(1, $sub);
        $this->assertFalse($suivi->getIsVisibleForUsager());
    }

    public function testCreateSuiviLogementVacant(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(
            ['reference' => self::REF_SIGNALEMENT]
        );
        $signalement->setIsLogementVacant(true);

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'user-13-03@signal-logement.fr']);

        $countSuivisBeforeCreate = $signalement->getSuivis()->count();
        $params = [
            'motif_suivi' => 'Lorem ipsum suivi sit amet, consectetur adipiscing elit.',
            'motif_cloture' => MotifCloture::tryFrom('NON_DECENCE'),
            'subject' => 'test',
        ];
        $signalement->setMotifCloture($params['motif_cloture']);
        $signalement->setClosedBy($user);
        $suivi = $this->suiviManager->createSuivi(
            signalement : $signalement,
            description : SuiviManager::buildDescriptionClotureSignalement($params),
            category: SuiviCategory::SIGNALEMENT_IS_CLOSED,
            partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory()),
            user: $user,
            isVisibleForUsager: true
        );
        $signalement->addSuivi($suivi);
        $this->entityManager->flush();
        $countSuivisAfterCreate = $signalement->getSuivis()->count();

        $this->assertEquals(Suivi::TYPE_PARTNER, $suivi->getType());
        $this->assertNotEquals($countSuivisBeforeCreate, $countSuivisAfterCreate);
        $this->assertInstanceOf(Suivi::class, $suivi);
        $desc = \sprintf(SuiviDescriptionHelper::DESCRIPTION_MOTIF_CLOTURE_PARTNER, 'test').'<br /><strong>Non décence</strong><br /><strong>Desc. : </strong>Lorem ipsum suivi sit amet, consectetur adipiscing elit.';
        $this->assertEquals($desc, $suivi->getDescription());
        $this->assertFalse($suivi->getIsVisibleForUsager());
        $this->assertTrue($suivi->getIsSanitized());
        $this->assertInstanceOf(UserInterface::class, $suivi->getCreatedBy());
    }

    public function testCreateInstanceForFilesSignalementProcedureDocuments(): void
    {
        [$signalement, $user] = $this->getSignalementAndUserForFiles();
        $files = [
            $this->createFile(DocumentType::PROCEDURE_MISE_EN_DEMEURE),
            $this->createFile(DocumentType::PROCEDURE_MISE_EN_DEMEURE),
        ];

        $suivi = $this->suiviManager->createInstanceForFilesSignalement($user, $signalement, $files);

        $this->assertSame(SuiviCategory::NEW_DOCUMENT, $suivi->getCategory());
        $this->assertSame('2 documents liés à la procédure ont été ajoutés au signalement.', $suivi->getDescription(raw: true));
        $this->assertFalse($suivi->getIsVisibleForUsager());
        $this->assertFalse($suivi->getIsVisibleForBailleur());
        $this->assertCount(2, $suivi->getSuiviFiles());
    }

    public function testCreateInstanceForFilesSignalementSituationDocument(): void
    {
        [$signalement, $user] = $this->getSignalementAndUserForFiles();

        $suivi = $this->suiviManager->createInstanceForFilesSignalement($user, $signalement, [
            $this->createFile(DocumentType::SITUATION_FOYER_BAIL),
        ]);

        $this->assertSame('1 document sur la situation usager a été ajouté au signalement :', $suivi->getDescription(raw: true));
        $this->assertTrue($suivi->getIsVisibleForUsager());
    }

    public function testCreateInstanceForFilesSignalementRapportDeVisite(): void
    {
        [$signalement, $user] = $this->getSignalementAndUserForFiles();
        $intervention = (new Intervention())->setScheduledAt(new \DateTimeImmutable('2026-03-15 10:00:00'));

        $suivi = $this->suiviManager->createInstanceForFilesSignalement($user, $signalement, [
            $this->createFile(DocumentType::PROCEDURE_RAPPORT_DE_VISITE, $intervention),
        ]);

        $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory());
        $this->assertSame($partner, $suivi->getPartner());
        $this->assertTrue($suivi->getIsVisibleForUsager());
        $this->assertStringEndsWith('a ajouté 1 rapport de visite de la visite du 15/03/2026 :', $suivi->getDescription(raw: true));
    }

    public function testCreateInstanceForFilesSignalementPhotosDeVisite(): void
    {
        [$signalement, $user] = $this->getSignalementAndUserForFiles();
        $intervention = (new Intervention())->setScheduledAt(new \DateTimeImmutable('2026-03-15 10:00:00'));

        $suivi = $this->suiviManager->createInstanceForFilesSignalement($user, $signalement, [
            $this->createFile(DocumentType::PHOTO_VISITE, $intervention),
            $this->createFile(DocumentType::PHOTO_VISITE, $intervention),
            $this->createFile(DocumentType::PHOTO_VISITE, $intervention),
        ]);

        $this->assertTrue($suivi->getIsVisibleForUsager());
        $this->assertStringEndsWith('a ajouté 3 photos de la visite du 15/03/2026 :', $suivi->getDescription(raw: true));
    }

    public function testCreateInstanceForFilesSignalementPhotoVisiteWithoutIntervention(): void
    {
        // Sans intervention, une photo de visite est traitée comme un document de procédure
        [$signalement, $user] = $this->getSignalementAndUserForFiles();

        $suivi = $this->suiviManager->createInstanceForFilesSignalement($user, $signalement, [
            $this->createFile(DocumentType::PHOTO_VISITE),
        ]);

        $this->assertSame('1 document lié à la procédure a été ajouté au signalement.', $suivi->getDescription(raw: true));
        $this->assertFalse($suivi->getIsVisibleForUsager());
    }

    /**
     * @return array{Signalement, User}
     */
    private function getSignalementAndUserForFiles(): array
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => self::REF_SIGNALEMENT]);
        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'user-13-03@signal-logement.fr']);

        return [$signalement, $user];
    }

    private function createFile(DocumentType $documentType, ?Intervention $intervention = null): File
    {
        return (new File())
            ->setFilename('document.pdf')
            ->setTitle('Document')
            ->setDocumentType($documentType)
            ->setIntervention($intervention);
    }

    public function testCreateSuiviFromSuiviDelayedListFrontOffice(): void
    {
        [$signalement, $user] = $this->getSignalementAndUserForFiles();

        $this->suiviManager->createSuiviFromSuiviDelayedList([
            $this->createSuiviDelayed($signalement, $user, SuiviCategory::SIGNALEMENT_EDITED_FO, SuiviDelayedType::FO_EDIT_COORDONNEES_OCCUPANT, [
                'Nom' => 'Dupont <b>',
                'Téléphone' => null,
                'Commentaire' => "ligne 1\nligne 2",
            ]),
            $this->createSuiviDelayed($signalement, $user, SuiviCategory::SIGNALEMENT_EDITED_FO, SuiviDelayedType::FO_FILE_DELETED),
            $this->createSuiviDelayed($signalement, $user, SuiviCategory::SIGNALEMENT_EDITED_FO, SuiviDelayedType::FO_FILE_DELETED),
        ]);
        $this->entityManager->flush();

        $suivi = $this->findLastSuivi($signalement, SuiviCategory::SIGNALEMENT_EDITED_FO);
        $description = $suivi->getDescription(raw: true);
        $this->assertStringContainsString('Des modifications ont été apportées par', $description);
        $this->assertStringContainsString('<li>Nom : Dupont &lt;b&gt;</li>', $description);
        $this->assertStringContainsString('<li>Téléphone : <i>(valeur supprimée)</i></li>', $description);
        $this->assertStringContainsString('Commentaire : ligne 1<br', $description);
        $this->assertStringContainsString('<strong>Autres modifications</strong>', $description);
        $this->assertStringContainsString('<li>2 '.SuiviDelayedType::FO_FILE_DELETED->label().'</li>', $description);
        $this->assertTrue($suivi->getIsVisibleForUsager());
        $this->assertNull($suivi->getPartner());
    }

    public function testCreateSuiviFromSuiviDelayedListBackOfficeOccupationLogement(): void
    {
        [$signalement, $user] = $this->getSignalementAndUserForFiles();

        $this->suiviManager->createSuiviFromSuiviDelayedList([
            $this->createSuiviDelayed($signalement, $user, SuiviCategory::SIGNALEMENT_EDITED_BO, SuiviDelayedType::BO_EDIT_OCCUPATION_LOGEMENT, ['logementVacant' => true]),
            $this->createSuiviDelayed($signalement, $user, SuiviCategory::SIGNALEMENT_EDITED_BO, SuiviDelayedType::BO_EDIT_ADDRESS),
        ]);
        $this->entityManager->flush();

        $suivi = $this->findLastSuivi($signalement, SuiviCategory::SIGNALEMENT_EDITED_BO);
        $description = $suivi->getDescription(raw: true);
        $this->assertStringContainsString('<li>Le logement a été marqué comme vacant.</li>', $description);
        $this->assertStringContainsString('<li>'.htmlspecialchars(SuiviDelayedType::BO_EDIT_ADDRESS->label(), \ENT_QUOTES).'</li>', $description);
        // Pas de titre « Autres modifications » côté back-office
        $this->assertStringNotContainsString('Autres modifications', $description);
        $this->assertFalse($suivi->getIsVisibleForUsager());
        $this->assertSame($user->getPartnerInTerritoryOrFirstOne($signalement->getAddress()->getTerritory()), $suivi->getPartner());
    }

    public function testCreateSuiviFromSuiviDelayedListLogementOccupe(): void
    {
        [$signalement, $user] = $this->getSignalementAndUserForFiles();

        $this->suiviManager->createSuiviFromSuiviDelayedList([
            $this->createSuiviDelayed($signalement, $user, SuiviCategory::SIGNALEMENT_EDITED_BO, SuiviDelayedType::BO_EDIT_OCCUPATION_LOGEMENT, ['logementVacant' => false]),
        ]);
        $this->entityManager->flush();

        $description = $this->findLastSuivi($signalement, SuiviCategory::SIGNALEMENT_EDITED_BO)->getDescription(raw: true);
        $this->assertStringContainsString('<li>Le logement a été marqué comme occupé.</li>', $description);
    }

    /**
     * @param array<mixed>|null $changes
     */
    private function createSuiviDelayed(
        Signalement $signalement,
        User $user,
        SuiviCategory $category,
        SuiviDelayedType $type,
        ?array $changes = null,
    ): SuiviDelayed {
        return (new SuiviDelayed())
            ->setSignalement($signalement)
            ->setUser($user)
            ->setSuiviCategory($category)
            ->setSuiviDelayedType($type)
            ->setChanges($changes);
    }

    private function findLastSuivi(Signalement $signalement, SuiviCategory $category): Suivi
    {
        /** @var Suivi $suivi */
        $suivi = $this->entityManager->getRepository(Suivi::class)->findOneBy(
            ['signalement' => $signalement, 'category' => $category],
            ['id' => 'DESC']
        );

        return $suivi;
    }
}
