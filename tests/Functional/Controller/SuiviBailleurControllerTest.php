<?php

namespace App\Tests\Functional\Controller;

use App\Dto\ReponseInjonctionBailleur;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Security\User\SignalementBailleur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SuiviBailleurControllerTest extends WebTestCase
{
    private const string SIGN_2025_11_UUID = '00000000-0000-0000-2025-000000000011';

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    public function testDossierBailleurSubmitOui(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => self::SIGN_2025_11_UUID]);
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlDossierBailleur = $router->generate('front_dossier_bailleur');
        $uuid = $signalement->getUuid();
        if (empty($uuid)) {
            throw new \RuntimeException('Le signalement n’a pas d’UUID');
        }
        $signalementUser = new SignalementBailleur(userIdentifier: $uuid);
        $client->loginUser($signalementUser, 'login_bailleur');
        $crawler = $client->request('GET', $urlDossierBailleur);

        $this->assertStringContainsString('Aucune', $crawler->filter('.signalement-card .info')->eq(3)->text());

        $form = $crawler->filter('form[name="reponse_injonction_bailleur"]')->form();
        $form['reponse_injonction_bailleur[reponse]'] = ReponseInjonctionBailleur::REPONSE_OUI;
        $form['reponse_injonction_bailleur[description]'] = '';
        $client->submit($form);

        $this->assertEmailCount(1);

        $this->assertResponseRedirects($urlDossierBailleur);
        $client->followRedirect();
        $crawler = $client->getCrawler();

        $this->assertStringContainsString('Votre réponse a été enregistrée avec succès.', $crawler->filter('.fr-alert.fr-alert--success')->text());
        $this->assertStringContainsString('Oui', $crawler->filter('.signalement-card .info')->eq(3)->text());
        $this->assertStringContainsString('Contrat d\'engagement', $crawler->filter('h2')->eq(3)->text());

        $signalement = $entityManager->getRepository(Suivi::class)->findBy([
            'signalement' => $signalement->getId(),
            'category' => SuiviCategory::injonctionBailleurCategories()]
        );
        $this->assertEquals(1, count($signalement));
    }

    public function testDossierBailleurSubmitOuiAide(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => self::SIGN_2025_11_UUID]);
        $signalement->setMailProprio(null);
        $entityManager->flush();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlDossierBailleur = $router->generate('front_dossier_bailleur');
        $uuid = $signalement->getUuid();
        if (empty($uuid)) {
            throw new \RuntimeException('Le signalement n’a pas d’UUID');
        }
        $signalementUser = new SignalementBailleur(userIdentifier: $uuid);
        $client->loginUser($signalementUser, 'login_bailleur');
        $crawler = $client->request('GET', $urlDossierBailleur);

        $this->assertStringContainsString('Monsieur Talau Clément', $crawler->filter('.signalement-card .info')->eq(0)->text());

        $form = $crawler->filter('form[name="reponse_injonction_bailleur"]')->form();
        $form['reponse_injonction_bailleur[reponse]'] = ReponseInjonctionBailleur::REPONSE_OUI_AVEC_AIDE;
        $form['reponse_injonction_bailleur[description]'] = 'Bon allez, dites moi ce que je dois faire. <ul><li>1</li><li>2</li></ul>';
        $client->submit($form);

        $this->assertEmailCount(1);

        $this->assertResponseRedirects($urlDossierBailleur);
        $client->followRedirect();
        $crawler = $client->getCrawler();

        $this->assertStringContainsString('Votre réponse a été enregistrée avec succès.', $crawler->filter('.fr-alert.fr-alert--success')->text());
        $this->assertStringContainsString('Oui avec aide', $crawler->filter('.signalement-card .info')->eq(3)->text());
        $this->assertStringContainsString('Contrat d\'engagement', $crawler->filter('h2')->eq(3)->text());
        $this->assertStringContainsString('Coordonnées manquantes', $crawler->filter('h2')->eq(4)->text());

        $suivi = $entityManager->getRepository(Suivi::class)->findBy([
            'signalement' => $signalement->getId(),
            'category' => SuiviCategory::injonctionBailleurCategories()]
        );
        $this->assertEquals(2, count($suivi));
        $this->assertStringContainsString('Bon allez, dites moi ce que je dois faire. &lt;ul&gt;&lt;li&gt;1&lt;/li&gt;&lt;li&gt;2&lt;/li&gt;&lt;/ul&gt;', $suivi[1]->getDescription());
    }

    public function testDossierBailleurSubmitNon(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => self::SIGN_2025_11_UUID]);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlDossierBailleur = $router->generate('front_dossier_bailleur');
        $uuid = $signalement->getUuid();
        if (empty($uuid)) {
            throw new \RuntimeException('Le signalement n’a pas d’UUID');
        }
        $signalementUser = new SignalementBailleur(userIdentifier: $uuid);
        $client->loginUser($signalementUser, 'login_bailleur');
        $crawler = $client->request('GET', $urlDossierBailleur);

        $this->assertStringContainsString('INJ-2363', $crawler->filter('.signalement-card .info')->eq(2)->text());

        $form = $crawler->filter('form[name="reponse_injonction_bailleur"]')->form();
        $form['reponse_injonction_bailleur[reponse]'] = ReponseInjonctionBailleur::REPONSE_NON;
        $form['reponse_injonction_bailleur[description]'] = 'Même pas peur. <i>test</i>';
        $client->submit($form);

        $this->assertEmailCount(1);

        $this->assertResponseRedirects($urlDossierBailleur);
        $client->followRedirect();
        $crawler = $client->getCrawler();

        $this->assertStringContainsString('Votre réponse a été enregistrée avec succès.', $crawler->filter('.fr-alert.fr-alert--success')->text());
        $this->assertStringContainsString('Non', $crawler->filter('.signalement-card .info')->eq(3)->text());

        $suivi = $entityManager->getRepository(Suivi::class)->findBy([
            'signalement' => $signalement->getId(),
            'category' => SuiviCategory::injonctionBailleurCategories()]
        );
        $this->assertEquals(2, count($suivi));
        $this->assertStringContainsString('Même pas peur. &lt;i&gt;test&lt;/i&gt;', $suivi[1]->getDescription());
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => self::SIGN_2025_11_UUID]);
        $this->assertEquals(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());
        $this->assertStringStartsWith(date('Y').'-', $signalement->getReference());
        $this->assertStringNotContainsString('-TEMPORAIRE', $signalement->getReference());
    }

    public function testDossierBailleurStopProcedure(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => self::SIGN_2025_11_UUID]);

        $suiviReponse = new Suivi();
        $suiviReponse->setSignalement($signalement);
        $suiviReponse->setCategory(SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI);
        $suiviReponse->setDescription('Réponse initiale du bailleur : oui.');
        $suiviReponse->setType(Suivi::TYPE_AUTO);
        $entityManager->persist($suiviReponse);
        $entityManager->flush();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlDossierBailleur = $router->generate('front_dossier_bailleur');
        $uuid = $signalement->getUuid();
        if (empty($uuid)) {
            throw new \RuntimeException('Le signalement n’a pas d’UUID');
        }
        $signalementUser = new SignalementBailleur(userIdentifier: $uuid);
        $client->loginUser($signalementUser, 'login_bailleur');
        $crawler = $client->request('GET', $urlDossierBailleur);
        $this->assertGreaterThan(
            0,
            $crawler->filter('form[name="stop_procedure"]')->count(),
            'Le formulaire d’arrêt de procédure devrait être affiché.'
        );

        $form = $crawler->filter('form[name="stop_procedure"]')->form();
        $form['stop_procedure[description]'] = 'Je préfère passer en procédure classique. <strong>Merci</strong>';
        $client->submit($form);

        $this->assertResponseRedirects($urlDossierBailleur);
        $client->followRedirect();
        $crawler = $client->getCrawler();

        $this->assertStringContainsString(
            'Votre réponse a été enregistrée avec succès.',
            $crawler->filter('.fr-alert.fr-alert--success')->text()
        );

        $suivis = $entityManager->getRepository(Suivi::class)->findBy([
            'signalement' => $signalement->getId(),
            'category' => SuiviCategory::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR,
        ]);
        $this->assertCount(1, $suivis, 'Un suivi de bascule vers procédure classique doit être créé.');

        $suivis = $entityManager->getRepository(Suivi::class)->findBy([
            'signalement' => $signalement->getId(),
            'category' => SuiviCategory::INJONCTION_BAILLEUR_BASCULE_PROCEDURE_PAR_BAILLEUR_COMMENTAIRE,
        ]);
        $this->assertCount(1, $suivis, 'Un suivi de bascule vers procédure classique doit être créé.');
        $this->assertEquals('Je préfère passer en procédure classique. &lt;strong&gt;Merci&lt;/strong&gt;', $suivis[0]->getDescription());

        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => self::SIGN_2025_11_UUID]);
        $this->assertEquals(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());
        $this->assertStringStartsWith(date('Y').'-', $signalement->getReference());
        $this->assertStringNotContainsString('-TEMPORAIRE', $signalement->getReference());
    }
}
