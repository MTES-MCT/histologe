<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\UserManager;
use App\Repository\SuiviRepository;
use App\Tests\SessionHelper;
use App\Tests\UserHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
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

        if (in_array($status, [SignalementStatus::DRAFT->value, SignalementStatus::DRAFT_ARCHIVED->value])) {
            $this->assertResponseRedirects('/authentification/'.$signalement->getCodeSuivi());
        } elseif (in_array($status, [SignalementStatus::ACTIVE->value, SignalementStatus::NEED_VALIDATION->value, SignalementStatus::INJONCTION_BAILLEUR->value])) {
            $this->assertResponseIsSuccessful();
            $this->assertSelectorExists('form#form-usager-complete-dossier');
            $this->assertSelectorTextContains('button[type=submit]', 'Envoyer');
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
        $this->assertSelectorTextContains('button[type=submit]', 'Envoyer');
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
            'statut' => SignalementStatus::ACTIVE,
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

        $form = $crawler->selectButton('Envoyer')->form([
            'adresse_logement[etageOccupant]' => 'AAAAAA',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorExists('.fr-error-text');
        $this->assertSelectorTextContains('.fr-error-text', 'L\'étage doit contenir au maximum 5 caractères.');

        $form = $crawler->selectButton('Envoyer')->form([
            'adresse_logement[etageOccupant]' => '1',
            'adresse_logement[escalierOccupant]' => 'A',
            'adresse_logement[numAppartOccupant]' => '42',
            'adresse_logement[adresseAutreOccupant]' => 'Lieu-dit de la Patate',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());

        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $entityManager->getRepository(Suivi::class);
        $suivi = $suiviRepository->findOneBy([
            'signalement' => $signalement,
            'category' => SuiviCategory::SIGNALEMENT_EDITED_FO,
        ]);
        $this->assertNotNull($suivi);
        $this->assertStringContainsString('adresse du logement', $description = $suivi->getDescription());
        $crawler = new Crawler($description);
        $this->assertEquals(4, $crawler->filter('li')->count());
        $this->assertEquals('1', $signalement->getEtageOccupant());
        $this->assertEquals('A', $signalement->getEscalierOccupant());
        $this->assertEquals('42', $signalement->getNumAppartOccupant());
        $this->assertEquals('Lieu-dit de la Patate', $signalement->getAdresseAutreOccupant());
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

        $form = $crawler->selectButton('Envoyer')->form([
            'coordonnees_bailleur[mailProprio]' => 'nouvel.email@example.org',
            'coordonnees_bailleur[adresseProprio]' => '12 rue du Test',
            'coordonnees_bailleur[codePostalProprio]' => '75000',
            'coordonnees_bailleur[villeProprio]' => 'Paris',
            'coordonnees_bailleur[telProprio]' => '0102030405',
            'coordonnees_bailleur[telProprioSecondaire]' => '0607080910',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());

        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $entityManager->getRepository(Suivi::class);
        $suivi = $suiviRepository->findOneBy([
            'signalement' => $signalement,
            'category' => SuiviCategory::SIGNALEMENT_EDITED_FO,
        ]);
        $this->assertEquals('nouvel.email@example.org', $signalement->getMailProprio());
        $this->assertNotNull($suivi);
        $this->assertStringContainsString('Les coordonnées du bailleur ont été modifiées par', $description = $suivi->getDescription());
        $crawler = new Crawler($description);
        $this->assertEquals(6, $crawler->filter('li')->count());
        $this->assertStringContainsString('E-mail', $description);
        $this->assertStringContainsString('Adresse', $description);
        $this->assertStringContainsString('Code postal', $description);
        $this->assertStringContainsString('Ville', $description);
        $this->assertStringContainsString('Téléphone', $description);
        $this->assertStringContainsString('Téléphone secondaire', $description);
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

        $form = $crawler->selectButton('Envoyer')->form([
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

        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $entityManager->getRepository(Suivi::class);
        $suivi = $suiviRepository->findBy(['signalement' => $signalement, 'category' => SuiviCategory::SIGNALEMENT_EDITED_FO]);
        $this->assertCount(1, $suivi);
    }

    public function testSubmitSuiviSignalementCompleteAgence(): void
    {
        $fieldValue = 'cest-remy@auber.fr';
        $signalement = $this->doTestAgenceAndSyndic('front_suivi_signalement_complete_agence', 'coordonnees_agence[mailAgence]', $fieldValue, 'agence');
        $this->assertEquals($fieldValue, $signalement->getMailAgence());
    }

    public function testSubmitSuiviSignalementCompleteSyndic(): void
    {
        $fieldValue = 'cest-remy@auber.fr';
        $signalement = $this->doTestAgenceAndSyndic('front_suivi_signalement_complete_syndic', 'coordonnees_syndic[mailSyndic]', $fieldValue, 'syndic');
        $this->assertEquals($fieldValue, $signalement->getMailSyndic());
    }

    private function doTestAgenceAndSyndic(string $urlName, string $fieldName, string $fieldValue, string $descriptionValue): Signalement
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

        $form = $crawler->selectButton('Envoyer')->form([
            $fieldName => 'AAAAAA',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorExists('.fr-error-text');
        $this->assertSelectorTextContains('.fr-error-text', "n'est pas valide.");

        $form = $crawler->selectButton('Envoyer')->form([
            $fieldName => $fieldValue,
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $entityManager->getRepository(Suivi::class);
        $suivi = $suiviRepository->findOneBy([
            'signalement' => $signalement,
            'category' => SuiviCategory::SIGNALEMENT_EDITED_FO,
        ]);
        $this->assertNotNull($suivi);
        $this->assertStringContainsString($descriptionValue, $description = $suivi->getDescription());
        $crawler = new Crawler($description);
        $this->assertEquals(1, $crawler->filter('li')->count());

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

        $form = $crawler->selectButton('Envoyer')->form([
            'usager_situation_foyer[isLogementSocial]' => '1',
            'usager_situation_foyer[isRelogement]' => '1',
            'usager_situation_foyer[numAllocataire]' => 'More than 25 characters for num allocataire',
        ]);

        $client->submit($form);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorExists('.fr-error-text');
        $this->assertSelectorTextContains('.fr-error-text', 'doit comporter au maximum 25 caractères.');

        $form = $crawler->selectButton('Envoyer')->form([
            'usager_situation_foyer[isLogementSocial]' => '1',
            'usager_situation_foyer[isRelogement]' => '1',
            'usager_situation_foyer[numAllocataire]' => '11223344',
            'usager_situation_foyer[allocataire]' => 'oui',
            'usager_situation_foyer[montantAllocation]' => '555',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $entityManager->getRepository(Suivi::class);
        $suivi = $suiviRepository->findOneBy([
            'signalement' => $signalement,
            'category' => SuiviCategory::SIGNALEMENT_EDITED_FO,
        ]);
        $this->assertNotNull($suivi);
        $this->assertStringContainsString('situation du foyer', $description = $suivi->getDescription());
        $crawler = new Crawler($description);
        $this->assertEquals(5, $crawler->filter('li')->count());
        $this->assertTrue($signalement->getIsLogementSocial());
        $this->assertTrue($signalement->getIsRelogement());
        $this->assertEquals('11223344', $signalement->getNumAllocataire());
        $this->assertEquals('555', $signalement->getMontantAllocation());
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

        $form = $crawler->selectButton('Envoyer')->form([
            'procedure_assurance[infoProcedureAssuranceContactee]' => 'oui',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        $informationProcedure = $signalement->getInformationProcedure();
        $this->assertEquals('oui', $informationProcedure->getInfoProcedureAssuranceContactee());
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

        $form = $crawler->selectButton('Envoyer')->form([
            'informations_generales[nbOccupantsLogement]' => '12',
            'informations_generales[dpe]' => 'oui',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        $this->assertEquals('12', $signalement->getNbOccupantsLogement());
        $typeCompositionLogement = $signalement->getTypeCompositionLogement();
        $this->assertEquals('oui', $typeCompositionLogement->getBailDpeDpe());
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

        $form = $crawler->selectButton('Envoyer')->form([
            'type_composition[natureLogement]' => 'maison',
            'type_composition[superficie]' => '123',
            'type_composition[pieceUnique]' => 'piece_unique',
            'type_composition[pieceAVivre9m]' => 'nsp',
            'type_composition[cuisine]' => 'oui',
            'type_composition[salleDeBain]' => 'oui',
            'type_composition[wc]' => 'oui',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/suivre-mon-signalement/'.$signalement->getCodeSuivi().'/dossier');
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());
        $this->assertEquals('maison', $signalement->getNatureLogement());
        $this->assertEquals('123', $signalement->getSuperficie());
        $typeCompositionLogement = $signalement->getTypeCompositionLogement();
        $this->assertEquals('piece_unique', $typeCompositionLogement->getCompositionLogementPieceUnique());

        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $entityManager->getRepository(Suivi::class);
        $suivi = $suiviRepository->findOneBy([
            'signalement' => $signalement,
            'category' => SuiviCategory::SIGNALEMENT_EDITED_FO,
        ]);
        $this->assertNotNull($suivi);
        $this->assertStringContainsString('Le type et la composition du logement', $description = $suivi->getDescription());
        $crawler = new Crawler($description);
        $this->assertEquals(2, $crawler->filter('li')->count());
    }
}
