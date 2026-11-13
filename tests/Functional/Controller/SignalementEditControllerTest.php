<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProfileOccupant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Enum\SuiviDelayedType;
use App\Entity\Enum\TravauxMiseEnConformite;
use App\Entity\Model\InformationProcedure;
use App\Entity\Model\SituationFoyer;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\SuiviDelayed;
use App\Manager\UserManager;
use App\Tests\SessionHelper;
use App\Tests\UserHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SignalementEditControllerTest extends WebTestCase
{
    use SessionHelper;
    use UserHelper;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    public static function provideStatusSignalement(): \Generator
    {
        yield 'Actif' => [SignalementStatus::ACTIVE->value];
        yield 'Fermé' => [SignalementStatus::CLOSED->value];
        yield 'Refusé' => [SignalementStatus::REFUSED->value];
        yield 'Archivé' => [SignalementStatus::ARCHIVED->value];
        yield 'Brouillon' => [SignalementStatus::DRAFT->value];
        yield 'Brouillon de signalement' => [SignalementStatus::DRAFT_ARCHIVED->value];
        yield 'En médiation' => [SignalementStatus::INJONCTION_BAILLEUR->value];
        yield 'Injonction clôturée' => [SignalementStatus::INJONCTION_CLOSED->value];
    }

    public static function provideProfileDeclarant(): \Generator
    {
        yield 'LOCATAIRE' => [ProfileDeclarant::LOCATAIRE];
        yield 'BAILLEUR' => [ProfileDeclarant::BAILLEUR];
        yield 'BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT];
        yield 'TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER];
        yield 'TIERS_PRO' => [ProfileDeclarant::TIERS_PRO];
        yield 'SERVICE_SECOURS' => [ProfileDeclarant::SERVICE_SECOURS];
    }

    #[DataProvider('provideStatusSignalement')]
    public function testDisplaySuiviSignalementCompleteByStatus(string $status): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => $status,
            // 'isUsagerAbandonProcedure' => null,
        ]);
        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_bailleur', [
            'code' => $signalement->getCodeSuivi(),
        ]);

        $client->request('GET', $url);

        if (in_array($status, [SignalementStatus::DRAFT->value, SignalementStatus::DRAFT_ARCHIVED->value, SignalementStatus::ARCHIVED->value])) {
            $this->assertResponseRedirects('/authentification/'.$signalement->getCodeSuivi());
        } elseif (in_array($status, [SignalementStatus::ACTIVE->value, SignalementStatus::NEED_VALIDATION->value, SignalementStatus::INJONCTION_BAILLEUR->value])) {
            $this->assertResponseIsSuccessful();
            $this->assertSelectorExists('form#form-usager-complete-dossier');
            $this->assertSelectorTextContains('form#form-usager-complete-dossier button[type=submit]', 'Enregistrer');
            $this->assertSelectorExists('h1');
            $this->assertSelectorTextContains('h1', 'Compléter les informations');
        } else {
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        }
    }

    #[DataProvider('provideProfileDeclarant')]
    public function testDisplaySuiviSignalementCompleteByProfile(ProfileDeclarant $profile): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'profileDeclarant' => $profile,
            'statut' => SignalementStatus::ACTIVE,
        ]);
        if (!$signalement) {
            // $this->markTestSkipped('Aucun signalement trouvé pour le profile '.$profile->value);
            $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
                'profileDeclarant' => ProfileDeclarant::LOCATAIRE,
                'statut' => SignalementStatus::ACTIVE,
            ]);
            $signalement->setProfileDeclarant($profile);
            $entityManager->flush();
        }
        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_bailleur', [
            'code' => $signalement->getCodeSuivi(),
        ]);

        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form#form-usager-complete-dossier');
        $this->assertSelectorTextContains('form#form-usager-complete-dossier button[type=submit]', 'Enregistrer');
        $this->assertSelectorExists('h1');
        $this->assertSelectorTextContains('h1', 'Compléter les informations');
    }

    public function testSubmitSuiviSignalementCompleteAdresseLogement(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'uuid' => '00000000-0000-0000-2026-000000000001',
        ]);
        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_adresse_logement', [
            'code' => $signalement->getCodeSuivi(),
        ]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'adresse_logement[escalierOccupant]' => 'AAAA',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorExists('.fr-error-text');
        $this->assertSelectorTextContains('.fr-error-text', 'L\'escalier doit contenir au maximum 3 caractères.');

        $form = $crawler->selectButton('Enregistrer')->form([
            'adresse_logement[escalierOccupant]' => 'A',
            'adresse_logement[numAppartOccupant]' => '42',
            'adresse_logement[adresseAutreOccupant]' => 'Lieu-dit de la Patate',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());

        $suiviDelayedRepository = $entityManager->getRepository(SuiviDelayed::class);
        $suiviDelayed = $suiviDelayedRepository->findOneBy([
            'signalement' => $signalement,
            'suiviCategory' => SuiviCategory::SIGNALEMENT_EDITED_FO,
        ]);
        $this->assertNotNull($suiviDelayed);
        $this->assertEquals(SuiviDelayedType::FO_EDIT_ADRESSE_LOGEMENT, $suiviDelayed->getSuiviDelayedType());
        $changes = $suiviDelayed->getChanges();
        $this->assertEquals(3, count($changes));
        $this->assertArrayHasKey('Escalier', $changes);
        $this->assertArrayHasKey('Numéro d\'appartement', $changes);
        $this->assertArrayHasKey('Autre', $changes);

        $this->assertEquals('A', $changes['Escalier']);
        $this->assertEquals('42', $changes['Numéro d\'appartement']);
        $this->assertEquals('Lieu-dit de la Patate', $changes['Autre']);
    }

    public function testSubmitSuiviSignalementCompleteBailleur(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => SignalementStatus::ACTIVE,
        ]);
        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_bailleur', [
            'code' => $signalement->getCodeSuivi(),
        ]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'coordonnees_bailleur[mailProprio]' => 'nouvel.email@example.org',
            'coordonnees_bailleur[adresseProprio]' => '12 rue du Test',
            'coordonnees_bailleur[codePostalProprio]' => '75000',
            'coordonnees_bailleur[villeProprio]' => 'Paris',
            'coordonnees_bailleur[telProprio]' => '0102030405',
            'coordonnees_bailleur[telProprioSecondaire]' => '0607080910',
            'coordonnees_bailleur[infoProcedureBailMoyen]' => 'EMAIL',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());

        $suiviDelayedRepository = $entityManager->getRepository(SuiviDelayed::class);
        $suiviDelayed = $suiviDelayedRepository->findOneBy([
            'signalement' => $signalement,
            'suiviCategory' => SuiviCategory::SIGNALEMENT_EDITED_FO,
        ]);
        $this->assertEquals('nouvel.email@example.org', $signalement->getMailProprio());
        $this->assertNotNull($suiviDelayed);
        $this->assertEquals(SuiviDelayedType::FO_EDIT_COORDONNEES_BAILLEUR, $suiviDelayed->getSuiviDelayedType());
        $changes = $suiviDelayed->getChanges();
        $this->assertEquals(6, count($changes));

        $this->assertArrayHasKey('E-mail', $changes);
        $this->assertArrayHasKey('Adresse', $changes);
        $this->assertArrayHasKey('Code postal', $changes);
        $this->assertArrayHasKey('Ville', $changes);
        $this->assertArrayHasKey('Téléphone', $changes);
        $this->assertArrayHasKey('Téléphone secondaire', $changes);
    }

    public static function provideProfilesForCoordonneesBailleurProprioAverti(): \Generator
    {
        yield 'LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, true];
        yield 'BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, false];
        yield 'TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, false];
        yield 'TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, false];
    }

    #[DataProvider('provideProfilesForCoordonneesBailleurProprioAverti')]
    public function testSubmitSuiviSignalementCompleteBailleurProprioAvertiByProfile(
        ProfileDeclarant $profileDeclarant,
        bool $isRequired,
    ): void {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $this->requestCoordonneesBailleurForm($client, $profileDeclarant);

        $expectedMention = $isRequired ? '(obligatoire si le propriétaire est averti)' : '(facultatif)';
        $this->assertSelectorTextContains('label[for="coordonnees_bailleur_proprioAvertiAt"]', $expectedMention);
        $this->assertSelectorTextContains('label[for="coordonnees_bailleur_infoProcedureBailReponse"]', $expectedMention);

        $form = $crawler->selectButton('Enregistrer')->form([
            'coordonnees_bailleur[isProprioAverti]' => '1',
            'coordonnees_bailleur[proprioAvertiAt]' => '',
            'coordonnees_bailleur[infoProcedureBailReponse]' => '',
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        // Le moyen de contact reste obligatoire pour tous les profils
        $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez indiquer le moyen de contact utilisé pour avertir le propriétaire.');
        if ($isRequired) {
            $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez indiquer la date d\'avertissement du propriétaire.');
            $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez indiquer la réponse du propriétaire.');
        } else {
            $this->assertAnySelectorTextNotContains('.fr-error-text', 'Veuillez indiquer la date d\'avertissement du propriétaire.');
            $this->assertAnySelectorTextNotContains('.fr-error-text', 'Veuillez indiquer la réponse du propriétaire.');
        }
    }

    public static function provideProprioNonAverti(): \Generator
    {
        yield 'Propriétaire non averti' => ['0'];
        yield 'Ne sait pas si le propriétaire est averti' => ['nsp'];
    }

    #[DataProvider('provideProprioNonAverti')]
    public function testSubmitSuiviSignalementCompleteBailleurProprioNonAvertiForLocataire(string $isProprioAverti): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        // Le choix "Je ne sais pas" n'est proposé que si la valeur n'est pas encore renseignée
        $signalement->setIsProprioAverti(null);
        $entityManager->flush();

        $crawler = $this->requestCoordonneesBailleurForm($client, ProfileDeclarant::LOCATAIRE);

        $form = $crawler->selectButton('Enregistrer')->form([
            'coordonnees_bailleur[isProprioAverti]' => $isProprioAverti,
            'coordonnees_bailleur[proprioAvertiAt]' => '',
            'coordonnees_bailleur[infoProcedureBailReponse]' => '',
        ]);
        $this->submitFormWithoutFields($client, $form, 'coordonnees_bailleur', ['infoProcedureBailMoyen']);

        // Aucune des informations d'avertissement n'est exigée
        $errors = $client->getCrawler()->filter('.fr-error-text')->each(static fn (Crawler $node) => $node->text());
        $this->assertNotContains('Veuillez indiquer le moyen de contact utilisé pour avertir le propriétaire.', $errors);
        $this->assertNotContains('Veuillez indiquer la date d\'avertissement du propriétaire.', $errors);
        $this->assertNotContains('Veuillez indiquer la réponse du propriétaire.', $errors);
    }

    private function requestCoordonneesBailleurForm(KernelBrowser $client, ProfileDeclarant $profileDeclarant): Crawler
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $signalement->setProfileDeclarant($profileDeclarant);
        $entityManager->flush();
        $client->loginUser($this->getSignalementUser($signalement, UserManager::DECLARANT), 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_bailleur', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        return $crawler;
    }

    public function testSubmitSuiviSignalementCompleteOccupantWithEmptyMailOccupant(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $signalementUser = $this->getSignalementUser($signalement, UserManager::DECLARANT);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_occupant', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'coordonnees_occupant[civiliteOccupant]' => 'mr',
            'coordonnees_occupant[nomOccupant]' => 'NomOccupant',
            'coordonnees_occupant[prenomOccupant]' => 'PrenomOccupant',
            'coordonnees_occupant[mailOccupantTemp]' => '',
            'coordonnees_occupant[telOccupant]' => '',
            'coordonnees_occupant[telOccupantBis]' => '',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());

        $this->assertNull($signalement->getMailOccupant());
        $this->assertNull($signalement->getMailOccupantTemp());
        $this->assertEmailCount(0);

        $suiviDelayedRepository = $entityManager->getRepository(SuiviDelayed::class);
        $suivi = $suiviDelayedRepository->findBy(['signalement' => $signalement, 'suiviCategory' => SuiviCategory::SIGNALEMENT_EDITED_FO]);
        $this->assertCount(1, $suivi);
    }

    public function testSubmitSuiviSignalementCompleteAgence(): void
    {
        $fieldValue = 'cest-remy@auber.fr';
        $signalement = $this->doTestAgenceAndSyndic('front_suivi_signalement_complete_agence', 'coordonnees_agence[mailAgence]', $fieldValue, SuiviDelayedType::FO_EDIT_COORDONNEES_AGENCE);
        $this->assertEquals($fieldValue, $signalement->getMailAgence());
    }

    public function testSubmitSuiviSignalementCompleteSyndic(): void
    {
        $fieldValue = 'cest-remy@auber.fr';
        $signalement = $this->doTestAgenceAndSyndic('front_suivi_signalement_complete_syndic', 'coordonnees_syndic[mailSyndic]', $fieldValue, SuiviDelayedType::FO_EDIT_COORDONNEES_SYNDIC);
        $this->assertEquals($fieldValue, $signalement->getMailSyndic());
    }

    private function doTestAgenceAndSyndic(string $urlName, string $fieldName, string $fieldValue, SuiviDelayedType $suiviDelayedType): Signalement
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => SignalementStatus::ACTIVE,
        ]);
        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate($urlName, [
            'code' => $signalement->getCodeSuivi(),
        ]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            $fieldName => 'AAAAAA',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorExists('.fr-error-text');
        $this->assertSelectorTextContains('.fr-error-text', "n'est pas valide.");

        $form = $crawler->selectButton('Enregistrer')->form([
            $fieldName => $fieldValue,
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());

        $suiviDelayedRepository = $entityManager->getRepository(SuiviDelayed::class);
        $suiviDelayed = $suiviDelayedRepository->findOneBy([
            'signalement' => $signalement,
            'suiviCategory' => SuiviCategory::SIGNALEMENT_EDITED_FO,
        ]);
        $this->assertNotNull($suiviDelayed);
        $this->assertEquals($suiviDelayedType, $suiviDelayed->getSuiviDelayedType());
        $changes = $suiviDelayed->getChanges();
        $this->assertEquals(1, count($changes));

        return $signalement;
    }

    public function testSubmitSuiviSignalementCompleteSituationFoyer(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $signalementUser = $this->getSignalementUser($signalement, UserManager::DECLARANT);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_situation_foyer', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'usager_situation_foyer[isLogementSocial]' => '1',
            'usager_situation_foyer[isRelogement]' => '1',
            'usager_situation_foyer[numAllocataire]' => 'More than 25 characters for num allocataire',
        ]);

        $client->submit($form);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorExists('.fr-error-text');
        $this->assertAnySelectorTextContains('.fr-error-text', 'doit comporter au maximum 25 caractères.');

        $form = $crawler->selectButton('Enregistrer')->form([
            'usager_situation_foyer[isLogementSocial]' => '1',
            'usager_situation_foyer[isRelogement]' => '1',
            'usager_situation_foyer[numAllocataire]' => '11223344',
            'usager_situation_foyer[allocataire]' => 'oui',
            'usager_situation_foyer[caisseAllocation]' => 'MSA',
            'usager_situation_foyer[dateNaissanceAllocataire]' => '1985-06-15',
            'usager_situation_foyer[montantAllocation]' => '555',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());

        $suiviDelayedRepository = $entityManager->getRepository(SuiviDelayed::class);
        $suivi = $suiviDelayedRepository->findOneBy([
            'signalement' => $signalement,
            'suiviCategory' => SuiviCategory::SIGNALEMENT_EDITED_FO,
        ]);
        $this->assertNotNull($suivi);
        $this->assertEquals(SuiviDelayedType::FO_EDIT_SITUATION_FOYER, $suivi->getSuiviDelayedType());
        $changes = $suivi->getChanges();
        $this->assertEquals(6, count($changes));
        $this->assertArrayHasKey('Logement social', $changes);
        $this->assertArrayHasKey('Relogement', $changes);
        $this->assertArrayHasKey('Numéro d\'allocataire / de dossier', $changes);
        $this->assertArrayHasKey('Montant de l\'allocation', $changes);
        $this->assertArrayHasKey('Date de naissance de l\'occupant', $changes);
        $this->assertequals('11223344', $changes['Numéro d\'allocataire / de dossier']);
        $this->assertequals('555', $changes['Montant de l\'allocation']);
    }

    public function testSubmitSuiviSignalementCompleteSituationFoyerAllocataireRequiresDetails(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $client->loginUser($this->getSignalementUser($signalement, UserManager::DECLARANT), 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_situation_foyer', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'usager_situation_foyer[isLogementSocial]' => '1',
            'usager_situation_foyer[isRelogement]' => '1',
            'usager_situation_foyer[allocataire]' => 'oui',
            'usager_situation_foyer[dateNaissanceAllocataire]' => '',
            'usager_situation_foyer[numAllocataire]' => '',
        ]);
        $this->submitFormWithoutFields($client, $form, 'usager_situation_foyer', ['caisseAllocation']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez préciser la caisse d\'allocation.');
        $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez préciser la date de naissance de l\'occupant.');
        $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez préciser le numéro d\'allocataire / de dossier.');

        // Les informations d'allocation ne sont pas exigées si l'occupant n'est pas allocataire
        $form = $crawler->selectButton('Enregistrer')->form([
            'usager_situation_foyer[isLogementSocial]' => '1',
            'usager_situation_foyer[isRelogement]' => '1',
            'usager_situation_foyer[allocataire]' => 'non',
            'usager_situation_foyer[dateNaissanceAllocataire]' => '',
            'usager_situation_foyer[numAllocataire]' => '',
        ]);
        $this->submitFormWithoutFields($client, $form, 'usager_situation_foyer', ['caisseAllocation']);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
    }

    public static function provideProfileDeclarantForSituationFoyerRequiredFields(): \Generator
    {
        yield 'LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, true];
        yield 'BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, true];
        yield 'TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, false];
        yield 'TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, false];
    }

    #[DataProvider('provideProfileDeclarantForSituationFoyerRequiredFields')]
    public function testSubmitSuiviSignalementCompleteSituationFoyerRequiredFieldsByProfile(
        ProfileDeclarant $profileDeclarant,
        bool $isRequired,
    ): void {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $signalement->setProfileDeclarant($profileDeclarant);
        $entityManager->flush();
        $client->loginUser($this->getSignalementUser($signalement, UserManager::DECLARANT), 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_situation_foyer', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $labelRelogement = $crawler->filter('#usager_situation_foyer_isRelogement')->closest('fieldset')->filter('legend');
        $labelAllocataire = $crawler->filter('#usager_situation_foyer_allocataire')->closest('fieldset')->filter('legend');
        $this->assertSame($isRequired, $labelRelogement->filter('.text-required')->count() > 0);
        $this->assertSame($isRequired, $labelAllocataire->filter('.text-required')->count() > 0);
        $this->assertSame(!$isRequired, str_contains($labelRelogement->text(), '(facultatif)'));
        $this->assertSame(!$isRequired, str_contains($labelAllocataire->text(), '(facultatif)'));

        $form = $crawler->selectButton('Enregistrer')->form([
            'usager_situation_foyer[isLogementSocial]' => '1',
        ]);
        $this->submitFormWithoutFields($client, $form, 'usager_situation_foyer', ['isRelogement', 'allocataire', 'caisseAllocation']);

        if ($isRequired) {
            $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
            $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez préciser si une demande de relogement a été faite.');
            $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez préciser si l\'occupant est allocataire.');
        } else {
            $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        }
    }

    public static function provideSituationFoyerNspValues(): \Generator
    {
        yield 'Valeur nsp : choix "Je ne sais pas" proposé' => ['nsp', true];
        yield 'Valeur oui : choix "Je ne sais pas" absent' => ['oui', false];
        yield 'Valeur vide : choix "Je ne sais pas" absent' => [null, false];
    }

    #[DataProvider('provideSituationFoyerNspValues')]
    public function testDisplaySuiviSignalementCompleteSituationFoyerNspChoice(?string $value, bool $hasNspChoice): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $situationFoyer = $signalement->getSituationFoyer() ? clone $signalement->getSituationFoyer() : new SituationFoyer();
        $situationFoyer
            ->setTravailleurSocialQuitteLogement($value)
            ->setTravailleurSocialPreavisDepart($value)
            ->setTravailleurSocialAccompagnement($value);
        $signalement->setSituationFoyer($situationFoyer);
        $informationProcedure = $signalement->getInformationProcedure() ? clone $signalement->getInformationProcedure() : new InformationProcedure();
        $informationProcedure->setInfoProcedureDepartApresTravaux($value);
        $signalement->setInformationProcedure($informationProcedure);
        $entityManager->flush();
        $client->loginUser($this->getSignalementUser($signalement, UserManager::DECLARANT), 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_situation_foyer', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        foreach (['souhaiteQuitterLogement', 'preavisDepartDepose', 'accompagnementTravailleurSocial', 'departApresTravaux'] as $field) {
            $nspRadio = $crawler->filter('input[name="usager_situation_foyer['.$field.']"][value="nsp"]');
            $this->assertSame($hasNspChoice ? 1 : 0, $nspRadio->count(), $field);
            if ($hasNspChoice) {
                $this->assertSame('checked', $nspRadio->attr('checked'), $field);
            }
        }
    }

    public static function provideProfileDeclarantForCoordonneesOccupantRequiredFields(): \Generator
    {
        yield 'LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, true, true];
        yield 'BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, false, true];
        yield 'TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, false, false];
        yield 'TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, false, false];
    }

    #[DataProvider('provideProfileDeclarantForCoordonneesOccupantRequiredFields')]
    public function testSubmitSuiviSignalementCompleteOccupantRequiredFieldsByProfile(
        ProfileDeclarant $profileDeclarant,
        bool $isCiviliteRequired,
        bool $isTelRequired,
    ): void {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $signalement->setProfileDeclarant($profileDeclarant);
        $signalement->setMailOccupant('occupant@example.org');
        $entityManager->flush();
        $client->loginUser($this->getSignalementUser($signalement, UserManager::DECLARANT), 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_occupant', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $labelCivilite = $crawler->filter('label[for="coordonnees_occupant_civiliteOccupant"]');
        $labelTel = $crawler->filter('label[for="coordonnees_occupant_telOccupant"]');
        $this->assertSame($isCiviliteRequired, $labelCivilite->filter('.text-required')->count() > 0);
        $this->assertSame($isTelRequired, $labelTel->filter('.text-required')->count() > 0);
        $this->assertSame(!$isCiviliteRequired, str_contains($labelCivilite->text(), '(facultatif)'));
        $this->assertSame(!$isTelRequired, str_contains($labelTel->text(), '(facultatif)'));

        $form = $crawler->selectButton('Enregistrer')->form([
            'coordonnees_occupant[civiliteOccupant]' => '',
            'coordonnees_occupant[nomOccupant]' => 'NomOccupant',
            'coordonnees_occupant[prenomOccupant]' => 'PrenomOccupant',
            'coordonnees_occupant[mailOccupantTemp]' => 'occupant@example.org',
            'coordonnees_occupant[telOccupant]' => '',
            'coordonnees_occupant[telOccupantBis]' => '',
        ]);
        $client->submit($form);

        if (!$isCiviliteRequired && !$isTelRequired) {
            $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');

            return;
        }

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        if ($isCiviliteRequired) {
            $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez préciser la civilité.');
        } else {
            $this->assertAnySelectorTextNotContains('.fr-error-text', 'Veuillez préciser la civilité.');
        }
        $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez préciser le numéro de téléphone.');
    }

    /**
     * Soumet le formulaire en retirant certains champs de la requête,
     * pour simuler des boutons radio qu'aucune option ne coche.
     *
     * @param array<string> $fieldsToRemove
     */
    private function submitFormWithoutFields(KernelBrowser $client, Form $form, string $formName, array $fieldsToRemove): void
    {
        $values = $form->getPhpValues();
        foreach ($fieldsToRemove as $field) {
            unset($values[$formName][$field]);
        }
        $client->request($form->getMethod(), $form->getUri(), $values);
    }

    public function testSubmitSuiviSignalementCompleteAssurance(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $signalementUser = $this->getSignalementUser($signalement, UserManager::DECLARANT);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_assurance', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $labelAssuranceContactee = $crawler->filter('#procedure_assurance_infoProcedureAssuranceContactee')->closest('fieldset')->filter('legend');
        $this->assertCount(1, $labelAssuranceContactee->filter('.text-required'));

        $form = $crawler->selectButton('Enregistrer')->form([
            'procedure_assurance[infoProcedureAssuranceContactee]' => 'oui',
            'procedure_assurance[infoProcedureReponseAssurance]' => 'Prise en charge refusée.',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        $informationProcedure = $signalement->getInformationProcedure();
        $this->assertEquals('oui', $informationProcedure->getInfoProcedureAssuranceContactee());
        $this->assertEquals('Prise en charge refusée.', $informationProcedure->getInfoProcedureReponseAssurance());
    }

    public static function provideInvalidProcedureAssurance(): \Generator
    {
        yield 'Assurance contactée non renseignée' => [null, '', 'Veuillez préciser si l\'assurance a été contactée.'];
        yield 'Assurance contactée sans réponse' => ['oui', '', 'Veuillez préciser la réponse de l\'assurance.'];
        yield 'Réponse trop longue' => ['oui', str_repeat('a', 256), 'doit comporter au maximum 255 caractères.'];
    }

    #[DataProvider('provideInvalidProcedureAssurance')]
    public function testSubmitSuiviSignalementCompleteAssuranceInvalid(
        ?string $assuranceContactee,
        string $reponseAssurance,
        string $expectedError,
    ): void {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $client->loginUser($this->getSignalementUser($signalement, UserManager::DECLARANT), 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_assurance', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $values = ['procedure_assurance[infoProcedureReponseAssurance]' => $reponseAssurance];
        if (null !== $assuranceContactee) {
            $values['procedure_assurance[infoProcedureAssuranceContactee]'] = $assuranceContactee;
        }
        $form = $crawler->selectButton('Enregistrer')->form($values);
        $fieldsToRemove = null === $assuranceContactee ? ['infoProcedureAssuranceContactee'] : [];
        $this->submitFormWithoutFields($client, $form, 'procedure_assurance', $fieldsToRemove);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertAnySelectorTextContains('.fr-error-text', $expectedError);
    }

    public static function provideProcedureAssuranceWithoutReponse(): \Generator
    {
        yield 'Non' => ['non'];
        yield 'Pas d\'assurance logement' => ['pas_assurance_logement'];
        yield 'Ne sait pas' => ['nsp'];
    }

    #[DataProvider('provideProcedureAssuranceWithoutReponse')]
    public function testSubmitSuiviSignalementCompleteAssuranceWithoutReponse(string $assuranceContactee): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $client->loginUser($this->getSignalementUser($signalement, UserManager::DECLARANT), 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_assurance', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'procedure_assurance[infoProcedureAssuranceContactee]' => $assuranceContactee,
            'procedure_assurance[infoProcedureReponseAssurance]' => '',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        $this->assertEquals($assuranceContactee, $signalement->getInformationProcedure()->getInfoProcedureAssuranceContactee());
    }

    public function testSubmitSuiviSignalementCompleteInformationsGenerales(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $signalementUser = $this->getSignalementUser($signalement, UserManager::DECLARANT);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_informations_generales', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'informations_generales[nbOccupantsLogement]' => '12',
            'informations_generales[nbEnfantsDansLogement]' => '0',
            'informations_generales[dpe]' => 'oui',
            'informations_generales[classeEnergetique]' => 'C',
            'informations_generales[bail]' => 'non',
            'informations_generales[etatDesLieux]' => 'nsp',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        $this->assertEquals('12', $signalement->getNbOccupantsLogement());
        $typeCompositionLogement = $signalement->getTypeCompositionLogement();
        $this->assertEquals('oui', $typeCompositionLogement->getBailDpeDpe());
        $this->assertEquals('C', $typeCompositionLogement->getBailDpeClasseEnergetique());
        $this->assertEquals('non', $typeCompositionLogement->getBailDpeBail());
        $this->assertEquals('nsp', $typeCompositionLogement->getBailDpeEtatDesLieux());
    }

    public static function provideProfilesForInformationsGeneralesBailEtatDesLieux(): \Generator
    {
        yield 'Déclarant locataire' => [ProfileDeclarant::LOCATAIRE, ProfileOccupant::LOCATAIRE, true];
        yield 'Déclarant tiers, occupant locataire' => [ProfileDeclarant::TIERS_PARTICULIER, ProfileOccupant::LOCATAIRE, true];
        yield 'Déclarant tiers pro, sans profil occupant' => [ProfileDeclarant::TIERS_PRO, null, true];
        yield 'Déclarant bailleur' => [ProfileDeclarant::BAILLEUR, ProfileOccupant::LOCATAIRE, true];
        yield 'Déclarant bailleur occupant' => [ProfileDeclarant::BAILLEUR_OCCUPANT, null, false];
        yield 'Déclarant tiers, occupant bailleur occupant' => [ProfileDeclarant::TIERS_PARTICULIER, ProfileOccupant::BAILLEUR_OCCUPANT, false];
        yield 'Déclarant tiers pro, occupant bailleur occupant' => [ProfileDeclarant::TIERS_PRO, ProfileOccupant::BAILLEUR_OCCUPANT, false];
    }

    #[DataProvider('provideProfilesForInformationsGeneralesBailEtatDesLieux')]
    public function testSubmitSuiviSignalementCompleteInformationsGeneralesBailEtatDesLieuxByProfile(
        ProfileDeclarant $profileDeclarant,
        ?ProfileOccupant $profileOccupant,
        bool $isDisplayedAndRequired,
    ): void {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $signalement->setProfileDeclarant($profileDeclarant);
        $signalement->setProfileOccupant($profileOccupant);
        $typeCompositionLogement = $signalement->getTypeCompositionLogement() ? clone $signalement->getTypeCompositionLogement() : new TypeCompositionLogement();
        $typeCompositionLogement->setBailDpeBail(null)->setBailDpeEtatDesLieux(null);
        $signalement->setTypeCompositionLogement($typeCompositionLogement);
        $entityManager->flush();
        $client->loginUser($this->getSignalementUser($signalement, UserManager::DECLARANT), 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_informations_generales', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        foreach (['bail', 'etatDesLieux'] as $fieldName) {
            $field = $crawler->filter('#informations_generales_'.$fieldName);
            $this->assertSame($isDisplayedAndRequired ? 1 : 0, $field->count(), $fieldName);
            if ($isDisplayedAndRequired) {
                $this->assertCount(1, $field->closest('fieldset')->filter('legend .text-required'), $fieldName);
            }
        }

        $form = $crawler->selectButton('Enregistrer')->form([
            'informations_generales[dateEntree]' => '2020-01-15',
            'informations_generales[nbOccupantsLogement]' => '2',
            'informations_generales[nbEnfantsDansLogement]' => '0',
            'informations_generales[dpe]' => 'oui',
            'informations_generales[classeEnergetique]' => 'C',
        ]);
        $client->submit($form);

        if ($isDisplayedAndRequired) {
            $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
            $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez indiquer si un contrat de location (bail) existe.');
            $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez indiquer si un état des lieux a été réalisé.');

            return;
        }

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
    }

    public function testSubmitSuiviSignalementCompleteInformationsGeneralesKeepsBailEtatDesLieuxForBailleurOccupant(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $signalement->setProfileDeclarant(ProfileDeclarant::BAILLEUR_OCCUPANT);
        $typeCompositionLogement = $signalement->getTypeCompositionLogement() ? clone $signalement->getTypeCompositionLogement() : new TypeCompositionLogement();
        $typeCompositionLogement->setBailDpeBail('oui')->setBailDpeEtatDesLieux('non');
        $signalement->setTypeCompositionLogement($typeCompositionLogement);
        $entityManager->flush();
        $client->loginUser($this->getSignalementUser($signalement, UserManager::DECLARANT), 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_informations_generales', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $form = $crawler->selectButton('Enregistrer')->form([
            'informations_generales[dateEntree]' => '2020-01-15',
            'informations_generales[nbOccupantsLogement]' => '2',
            'informations_generales[nbEnfantsDansLogement]' => '0',
            'informations_generales[dpe]' => 'oui',
            'informations_generales[classeEnergetique]' => 'C',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        // Les champs n'étant pas affichés, les valeurs existantes ne doivent pas être écrasées
        $this->assertEquals('oui', $signalement->getTypeCompositionLogement()->getBailDpeBail());
        $this->assertEquals('non', $signalement->getTypeCompositionLogement()->getBailDpeEtatDesLieux());
    }

    public static function provideProfilesForInformationsGeneralesDateEntree(): \Generator
    {
        yield 'LOCATAIRE' => [ProfileDeclarant::LOCATAIRE, true];
        yield 'BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT, true];
        yield 'BAILLEUR' => [ProfileDeclarant::BAILLEUR, false];
        yield 'TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER, false];
        yield 'TIERS_PRO' => [ProfileDeclarant::TIERS_PRO, false];
    }

    #[DataProvider('provideProfilesForInformationsGeneralesDateEntree')]
    public function testSubmitSuiviSignalementCompleteInformationsGeneralesDateEntreeByProfile(
        ProfileDeclarant $profileDeclarant,
        bool $isRequired,
    ): void {
        self::ensureKernelShutdown();
        $client = static::createClient();
        [$crawler, $signalement] = $this->requestInformationsGeneralesForm($client, $profileDeclarant);

        $labelDateEntree = $crawler->filter('label[for="informations_generales_dateEntree"]');
        $this->assertSame($isRequired, $labelDateEntree->filter('.text-required')->count() > 0);
        $this->assertSame(!$isRequired, str_contains($labelDateEntree->text(), '(facultatif)'));

        $form = $this->fillInformationsGeneralesForm($crawler, [
            'informations_generales[dateEntree]' => '',
        ]);
        $client->submit($form);

        if ($isRequired) {
            $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
            $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez préciser la date d\'entrée dans le logement.');

            return;
        }

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
    }

    public function testSubmitSuiviSignalementCompleteInformationsGeneralesClasseEnergetique(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        [$crawler, $signalement] = $this->requestInformationsGeneralesForm($client, ProfileDeclarant::LOCATAIRE);

        $this->assertCount(1, $crawler->filter('label[for="informations_generales_classeEnergetique"] .text-required'));

        // DPE présent : la classe énergétique est obligatoire
        $form = $this->fillInformationsGeneralesForm($crawler, [
            'informations_generales[dpe]' => 'oui',
            'informations_generales[classeEnergetique]' => '',
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez indiquer la classe énergétique du logement si un DPE est présent.');

        // Pas de DPE : la classe énergétique reste facultative
        $form = $this->fillInformationsGeneralesForm($crawler, [
            'informations_generales[dpe]' => 'non',
            'informations_generales[classeEnergetique]' => '',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
    }

    public function testSubmitSuiviSignalementCompleteInformationsGeneralesNbEnfants(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        [$crawler, $signalement] = $this->requestInformationsGeneralesForm($client, ProfileDeclarant::LOCATAIRE);

        $this->assertCount(1, $crawler->filter('label[for="informations_generales_nbEnfantsDansLogement"] .text-required'));

        // Nombre d'enfants non renseigné
        $form = $this->fillInformationsGeneralesForm($crawler, [
            'informations_generales[nbEnfantsDansLogement]' => '',
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez saisir le nombre d\'enfants.');

        // Des enfants dans le logement : la présence d'enfants de 6 ans ou moins est obligatoire
        $form = $this->fillInformationsGeneralesForm($crawler, [
            'informations_generales[nbEnfantsDansLogement]' => '2',
        ]);
        $this->submitFormWithoutFields($client, $form, 'informations_generales', ['enfantsDansLogementMoinsSixAns']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertAnySelectorTextContains('.fr-error-text', 'Veuillez indiquer si des enfants de 6 ans ou moins vivent dans le logement.');

        // Aucun enfant : 0 est accepté et la question sur les enfants de 6 ans ou moins reste facultative
        $form = $this->fillInformationsGeneralesForm($crawler, [
            'informations_generales[nbEnfantsDansLogement]' => '0',
        ]);
        $this->submitFormWithoutFields($client, $form, 'informations_generales', ['enfantsDansLogementMoinsSixAns']);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = static::getContainer()->get('doctrine')->getManager()->getRepository(Signalement::class)->find($signalement->getId());
        $this->assertEquals('0', $signalement->getTypeCompositionLogement()->getCompositionLogementNombreEnfants());
    }

    /**
     * Affiche le formulaire des informations générales du signalement 2022-14 pour le profil déclarant donné.
     *
     * @return array{0: Crawler, 1: Signalement}
     */
    private function requestInformationsGeneralesForm(KernelBrowser $client, ProfileDeclarant $profileDeclarant): array
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $signalement->setProfileDeclarant($profileDeclarant);
        $signalement->setProfileOccupant(ProfileOccupant::LOCATAIRE);
        $entityManager->flush();
        $client->loginUser($this->getSignalementUser($signalement, UserManager::DECLARANT), 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_informations_generales', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        return [$crawler, $signalement];
    }

    /**
     * Remplit tous les champs obligatoires présents dans le formulaire des informations générales.
     *
     * @param array<string, string> $overrides
     */
    private function fillInformationsGeneralesForm(Crawler $crawler, array $overrides = []): Form
    {
        $values = array_merge([
            'informations_generales[dateEntree]' => '2020-01-15',
            'informations_generales[nbOccupantsLogement]' => '2',
            'informations_generales[nbEnfantsDansLogement]' => '0',
            'informations_generales[bail]' => 'oui',
            'informations_generales[etatDesLieux]' => 'oui',
            'informations_generales[dpe]' => 'oui',
            'informations_generales[classeEnergetique]' => 'C',
        ], $overrides);

        $form = $crawler->selectButton('Enregistrer')->form();
        // Certains champs (bail, état des lieux) ne sont pas affichés selon le profil
        $form->setValues(array_filter($values, static fn (string $field) => $form->has($field), \ARRAY_FILTER_USE_KEY));

        return $form;
    }

    public function testSubmitSuiviSignalementCompleteTypeComposition(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $signalementUser = $this->getSignalementUser($signalement, UserManager::DECLARANT);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_type_composition', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'type_composition[natureLogement]' => 'maison',
            'type_composition[superficie]' => '123',
            'type_composition[pieceUnique]' => 'piece_unique',
            'type_composition[pieceAVivre9m]' => 'nsp',
            'type_composition[cuisine]' => 'oui',
            'type_composition[salleDeBain]' => 'oui',
            'type_composition[wc]' => 'oui',
            'type_composition[wcCuisine]' => 'non',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        $this->assertEquals('maison', $signalement->getNatureLogement());
        $this->assertEquals('123', $signalement->getSuperficie());
        $typeCompositionLogement = $signalement->getTypeCompositionLogement();
        $this->assertEquals('piece_unique', $typeCompositionLogement->getCompositionLogementPieceUnique());

        $suiviDelayedRepository = $entityManager->getRepository(SuiviDelayed::class);
        $suiviDelayed = $suiviDelayedRepository->findOneBy([
            'signalement' => $signalement,
            'suiviCategory' => SuiviCategory::SIGNALEMENT_EDITED_FO,
        ]);
        $this->assertNotNull($suiviDelayed);
        $this->assertEquals(SuiviDelayedType::FO_EDIT_TYPE_COMPOSITION, $suiviDelayed->getSuiviDelayedType());
        $changes = $suiviDelayed->getChanges();
        $this->assertEquals(2, count($changes));
    }

    public static function provideInvalidTypeComposition(): \Generator
    {
        yield 'Nature "autre" sans précision' => [
            ['type_composition[natureLogement]' => 'autre', 'type_composition[natureAutrePrecision]' => ''],
            [],
            'Veuillez indiquer des précisions sur la nature du logement.',
        ];
        yield 'Appartement sans localisation' => [
            ['type_composition[natureLogement]' => 'appartement', 'type_composition[appartementAvecFenetres]' => 'oui'],
            ['appartementEtage'],
            'Veuillez indiquer la localisation de l\'appartement.',
        ];
        yield 'Pas de cuisine, cuisine collective non renseignée' => [
            ['type_composition[cuisine]' => 'non'],
            ['cuisineCollective'],
            'Veuillez indiquer si le logement a une cuisine collective.',
        ];
        yield 'Pas de salle de bain, salle de bain collective non renseignée' => [
            ['type_composition[salleDeBain]' => 'non'],
            ['salleDeBainCollective'],
            'Veuillez indiquer si le logement a une salle de bain collective.',
        ];
        yield 'Pas de toilettes, toilettes collectives non renseignées' => [
            ['type_composition[wc]' => 'non'],
            ['wcCollective'],
            'Veuillez indiquer si le logement a des toilettes collectives.',
        ];
        yield 'Cuisine et toilettes, même pièce non renseignée' => [
            ['type_composition[cuisine]' => 'oui', 'type_composition[wc]' => 'oui'],
            ['wcCuisine'],
            'Veuillez indiquer si la cuisine et les toilettes sont dans la même pièce.',
        ];
    }

    /**
     * @param array<string, string> $values
     * @param array<string>         $fieldsToRemove
     */
    #[DataProvider('provideInvalidTypeComposition')]
    public function testSubmitSuiviSignalementCompleteTypeCompositionInvalid(array $values, array $fieldsToRemove, string $expectedError): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $this->requestTypeCompositionForm($client);

        $form = $this->fillTypeCompositionForm($crawler, $values);
        $this->submitFormWithoutFields($client, $form, 'type_composition', $fieldsToRemove);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertAnySelectorTextContains('.fr-error-text', $expectedError);
    }

    public static function provideValidConditionalTypeComposition(): \Generator
    {
        yield 'Nature "autre" avec précision' => [
            ['type_composition[natureLogement]' => 'autre', 'type_composition[natureAutrePrecision]' => 'Péniche'],
            [],
        ];
        yield 'Appartement avec localisation' => [
            ['type_composition[natureLogement]' => 'appartement', 'type_composition[appartementEtage]' => 'RDC', 'type_composition[appartementAvecFenetres]' => 'oui'],
            [],
        ];
        yield 'Maison sans localisation ni précision' => [
            ['type_composition[natureLogement]' => 'maison', 'type_composition[natureAutrePrecision]' => ''],
            ['appartementEtage'],
        ];
        yield 'Pas de cuisine, de salle de bain ni de toilettes, accès collectifs renseignés' => [
            [
                'type_composition[cuisine]' => 'non',
                'type_composition[cuisineCollective]' => 'oui',
                'type_composition[salleDeBain]' => 'non',
                'type_composition[salleDeBainCollective]' => 'non',
                'type_composition[wc]' => 'non',
                'type_composition[wcCollective]' => 'oui',
            ],
            ['wcCuisine'],
        ];
        yield 'Équipements présents, accès collectifs non renseignés' => [
            ['type_composition[cuisine]' => 'oui', 'type_composition[salleDeBain]' => 'oui', 'type_composition[wc]' => 'oui'],
            ['cuisineCollective', 'salleDeBainCollective', 'wcCollective'],
        ];
        yield 'Cuisine sans toilettes, même pièce non renseignée' => [
            ['type_composition[cuisine]' => 'oui', 'type_composition[wc]' => 'non', 'type_composition[wcCollective]' => 'oui'],
            ['wcCuisine'],
        ];
    }

    /**
     * @param array<string, string> $values
     * @param array<string>         $fieldsToRemove
     */
    #[DataProvider('provideValidConditionalTypeComposition')]
    public function testSubmitSuiviSignalementCompleteTypeCompositionConditionalFieldsValid(array $values, array $fieldsToRemove): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $this->requestTypeCompositionForm($client);

        $form = $this->fillTypeCompositionForm($crawler, $values);
        $this->submitFormWithoutFields($client, $form, 'type_composition', $fieldsToRemove);

        $this->assertResponseRedirects();
    }

    public function testDisplaySuiviSignalementCompleteTypeCompositionRequiredLabels(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $this->requestTypeCompositionForm($client);

        $this->assertCount(1, $crawler->filter('label[for="type_composition_natureAutrePrecision"] .text-required'));
        $this->assertCount(1, $crawler->filter('label[for="type_composition_appartementEtagePrecision"] .text-required'));
        $this->assertCount(1, $crawler->filter('#type_composition_appartementEtage')->closest('fieldset')->filter('legend .text-required'));
    }

    private function requestTypeCompositionForm(KernelBrowser $client): Crawler
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-14']);
        $client->loginUser($this->getSignalementUser($signalement, UserManager::DECLARANT), 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_type_composition', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        return $crawler;
    }

    /**
     * Remplit les champs obligatoires du formulaire de type et composition du logement (maison, équipements présents).
     *
     * @param array<string, string> $overrides
     */
    private function fillTypeCompositionForm(Crawler $crawler, array $overrides = []): Form
    {
        return $crawler->selectButton('Enregistrer')->form(array_merge([
            'type_composition[natureLogement]' => 'maison',
            'type_composition[superficie]' => '123',
            'type_composition[pieceUnique]' => 'piece_unique',
            'type_composition[pieceAVivre9m]' => 'oui',
            'type_composition[cuisine]' => 'oui',
            'type_composition[salleDeBain]' => 'oui',
            'type_composition[wc]' => 'oui',
            'type_composition[wcCuisine]' => 'non',
        ], $overrides));
    }

    public function testCompleteTravauxMiseEnConformiteAccess(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        // Le kernel est rebooté entre chaque requête par défaut : sans désactiver
        // ce comportement, le MockClock injecté serait perdu à la seconde requête.
        $client->disableReboot();

        // On fige la date de clôture sur le 15 du mois : les dates manipulées ensuite tombent les 14/15, ce qui évite les débordements de fin de mois
        $closedAt = (new \DateTimeImmutable('-6 months'))->modify('first day of this month')->modify('+14 days')->setTime(10, 0);

        $container = static::getContainer();
        $mockClock = new MockClock($closedAt->modify('+6 months'));
        $container->set(ClockInterface::class, $mockClock);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-1']);
        $signalement->setClosedAt($closedAt);
        $entityManager->flush();

        $signalementUser = $this->getSignalementUser($signalement, UserManager::DECLARANT);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_travaux_mise_en_conformite', ['code' => $signalement->getCodeSuivi()]);

        // Jour anniversaire des 6 mois : accessible
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        // La veille : pas encore accessible
        $mockClock->modify('-1 day');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testSubmitCompleteTravauxMiseEnConformiteOui(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-1']);
        $signalementUser = $this->getSignalementUser($signalement, UserManager::DECLARANT);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_travaux_mise_en_conformite', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Valider ma réponse')->form([
            'travaux_mise_en_conformite[travauxMiseEnConformiteUsager]' => 'OUI',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        $this->assertEquals(TravauxMiseEnConformite::OUI, $signalement->getTravauxMiseEnConformiteUsager());

        $lastSuivi = $entityManager->getRepository(Suivi::class)->findOneBy(['signalement' => $signalement], ['createdAt' => 'DESC']);
        $this->assertNotNull($lastSuivi);
        $this->assertEquals(SuiviCategory::MESSAGE_USAGER_TRAVAUX_MISE_EN_CONFORMITE, $lastSuivi->getCategory());
        $this->assertEquals('L&#039;usager a indiqué que les travaux ont bien été faits.', $lastSuivi->getDescription());

        $crawler = $client->request('GET', $url);
        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
    }

    public function testSubmitCompleteTravauxMiseEnConformiteNon(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-1']);
        $signalementUser = $this->getSignalementUser($signalement, UserManager::DECLARANT);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_travaux_mise_en_conformite', ['code' => $signalement->getCodeSuivi()]);

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Valider ma réponse')->form([
            'travaux_mise_en_conformite[travauxMiseEnConformiteUsager]' => 'NON',
            'travaux_mise_en_conformite[travauxMiseEnConformiteUsagerCommentaire]' => 'Marre marre marre.',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        $this->assertEquals(TravauxMiseEnConformite::NON, $signalement->getTravauxMiseEnConformiteUsager());

        $lastSuivi = $entityManager->getRepository(Suivi::class)->findOneBy(['signalement' => $signalement], ['createdAt' => 'DESC']);
        $this->assertNotNull($lastSuivi);
        $this->assertEquals(SuiviCategory::MESSAGE_USAGER_POST_CLOTURE, $lastSuivi->getCategory());
        $this->assertEquals('L&#039;usager a indiqué que les travaux n&#039;ont pas été faits.<br />Commentaire : Marre marre marre.', $lastSuivi->getDescription());

        $crawler = $client->request('GET', $url);
        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
    }
}
