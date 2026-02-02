<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SuiviRepository;
use App\Tests\SessionHelper;
use App\Tests\UserHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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

    public function provideStatusSignalement(): \Generator
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

    public function provideProfileDeclarant(): \Generator
    {
        yield 'LOCATAIRE' => [ProfileDeclarant::LOCATAIRE];
        yield 'BAILLEUR' => [ProfileDeclarant::BAILLEUR];
        yield 'BAILLEUR_OCCUPANT' => [ProfileDeclarant::BAILLEUR_OCCUPANT];
        yield 'TIERS_PARTICULIER' => [ProfileDeclarant::TIERS_PARTICULIER];
        yield 'TIERS_PRO' => [ProfileDeclarant::TIERS_PRO];
        yield 'SERVICE_SECOURS' => [ProfileDeclarant::SERVICE_SECOURS];
    }

    /**
     * @dataProvider provideStatusSignalement
     */
    public function testDisplaySuiviSignalementCompleteByStatus(string $status): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine');
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => $status,
            // 'isUsagerAbandonProcedure' => null,
        ]);
        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
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

    /**
     * @dataProvider provideProfileDeclarant
     */
    public function testDisplaySuiviSignalementCompleteByProfile(ProfileDeclarant $profile): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();
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
        $router = self::getContainer()->get(RouterInterface::class);
        $url = $router->generate('front_suivi_signalement_complete_bailleur', [
            'code' => $signalement->getCodeSuivi(),
        ]);

        $client->request('GET', $url);

        if (in_array($profile, [ProfileDeclarant::BAILLEUR, ProfileDeclarant::BAILLEUR_OCCUPANT])) {
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        } else {
            $this->assertResponseIsSuccessful();
            $this->assertSelectorExists('form#form-usager-complete-dossier');
            $this->assertSelectorTextContains('button[type=submit]', 'Envoyer');
            $this->assertSelectorExists('h1');
            $this->assertSelectorTextContains('h1', 'Compléter les informations');
        }
    }

    public function testSubmitSuiviSignalementComplete(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy([
            'statut' => SignalementStatus::ACTIVE,
        ]);
        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
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
        $entityManager->clear(); // facultatif mais sûr
        $signalement = $entityManager->getRepository(Signalement::class)->find($signalement->getId());

        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $entityManager->getRepository(Suivi::class);
        $suivi = $suiviRepository->findOneBy([
            'signalement' => $signalement,
            'category' => SuiviCategory::SIGNALEMENT_EDITED_FO,
        ]);
        $this->assertEquals('nouvel.email@example.org', $signalement->getMailProprio());
        $this->assertNotNull($suivi);
        $this->assertStringContainsString('a mis à jour les coordonnées', $suivi->getDescription());
    }
}
