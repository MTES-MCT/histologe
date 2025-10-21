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
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2025-11']);
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlDossierBailleur = $router->generate('front_dossier_bailleur');

        $signalementUser = new SignalementBailleur(userIdentifier: $signalement->getUuid());
        $client->loginUser($signalementUser, 'login_bailleur');
        $crawler = $client->request('GET', $urlDossierBailleur);

        $this->assertStringContainsString('Aucune', $crawler->filter('.signalement-card .info')->eq(2)->text());

        $form = $crawler->filter('form[name="reponse_injonction_bailleur"]')->form();
        $form['reponse_injonction_bailleur[reponse]'] = ReponseInjonctionBailleur::REPONSE_OUI;
        $form['reponse_injonction_bailleur[description]'] = '';
        $client->submit($form);

        $this->assertEmailCount(1);

        $this->assertResponseRedirects($urlDossierBailleur);
        $client->followRedirect();
        $crawler = $client->getCrawler();

        $this->assertStringContainsString('Votre réponse a été enregistrée avec succès.', $crawler->filter('.fr-alert.fr-alert--success')->text());
        $this->assertStringContainsString('Oui', $crawler->filter('.signalement-card .info')->eq(2)->text());
        $this->assertStringContainsString('Contrat d\'engagement', $crawler->filter('h2')->eq(2)->text());

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
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2025-11']);
        $signalement->setMailProprio(null);
        $entityManager->flush();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlDossierBailleur = $router->generate('front_dossier_bailleur');

        $signalementUser = new SignalementBailleur(userIdentifier: $signalement->getUuid());
        $client->loginUser($signalementUser, 'login_bailleur');
        $crawler = $client->request('GET', $urlDossierBailleur);

        $this->assertStringContainsString('Monsieur Talau Clément', $crawler->filter('.signalement-card .info')->eq(0)->text());

        $form = $crawler->filter('form[name="reponse_injonction_bailleur"]')->form();
        $form['reponse_injonction_bailleur[reponse]'] = ReponseInjonctionBailleur::REPONSE_OUI_AVEC_AIDE;
        $form['reponse_injonction_bailleur[description]'] = 'Bon allez, dites moi ce que je dois faire.';
        $client->submit($form);

        $this->assertEmailCount(1);

        $this->assertResponseRedirects($urlDossierBailleur);
        $client->followRedirect();
        $crawler = $client->getCrawler();

        $this->assertStringContainsString('Votre réponse a été enregistrée avec succès.', $crawler->filter('.fr-alert.fr-alert--success')->text());
        $this->assertStringContainsString('Oui avec aide', $crawler->filter('.signalement-card .info')->eq(2)->text());
        $this->assertStringContainsString('Contrat d\'engagement', $crawler->filter('h2')->eq(2)->text());
        $this->assertStringContainsString('Coordonnées manquantes', $crawler->filter('h2')->eq(3)->text());

        $signalement = $entityManager->getRepository(Suivi::class)->findBy([
            'signalement' => $signalement->getId(),
            'category' => SuiviCategory::injonctionBailleurCategories()]
        );
        $this->assertEquals(2, count($signalement));
    }

    public function testDossierBailleurSubmitNon(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        /** @var Signalement $signalement */
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2025-11']);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $urlDossierBailleur = $router->generate('front_dossier_bailleur');

        $signalementUser = new SignalementBailleur(userIdentifier: $signalement->getUuid());
        $client->loginUser($signalementUser, 'login_bailleur');
        $crawler = $client->request('GET', $urlDossierBailleur);

        $this->assertStringContainsString('2025-11', $crawler->filter('.signalement-card .info')->eq(1)->text());

        $form = $crawler->filter('form[name="reponse_injonction_bailleur"]')->form();
        $form['reponse_injonction_bailleur[reponse]'] = ReponseInjonctionBailleur::REPONSE_NON;
        $form['reponse_injonction_bailleur[description]'] = 'Même pas peur.';
        $client->submit($form);

        $this->assertEmailCount(1);

        $this->assertResponseRedirects($urlDossierBailleur);
        $client->followRedirect();
        $crawler = $client->getCrawler();

        $this->assertStringContainsString('Votre réponse a été enregistrée avec succès.', $crawler->filter('.fr-alert.fr-alert--success')->text());
        $this->assertStringContainsString('Non', $crawler->filter('.signalement-card .info')->eq(2)->text());

        $signalement = $entityManager->getRepository(Suivi::class)->findBy([
            'signalement' => $signalement->getId(),
            'category' => SuiviCategory::injonctionBailleurCategories()]
        );
        $this->assertEquals(2, count($signalement));
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2025-11']);
        $this->assertEquals(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());
    }
}
