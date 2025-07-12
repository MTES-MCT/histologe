<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use App\Service\DashboardTabPanel\TabBodyType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardTabPanelControllerTest extends WebTestCase
{
    /** @dataProvider provideAuthorizedTabBodyType */
    public function testAccessGrantedForAuthorizedUsers(string $tabBodyType): void
    {
        $client = static::createClient();
        $router = self::getContainer()->get('router');
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);
        $url = $router->generate('back_tab_panel_body', ['tabBodyType' => $tabBodyType]);
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    /** @dataProvider provideUnauthorizedTabBodyType */
    public function testAccessDeniedForUnauthorizedUsers(string $tabBodyType): void
    {
        $client = static::createClient();
        $router = self::getContainer()->get('router');
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-partenaire-30@signal-logement.fr']);
        $client->loginUser($user);
        $url = $router->generate('back_tab_panel_body', ['tabBodyType' => $tabBodyType]);

        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403, sprintf('Accès interdit pour la section "%s".', $tabBodyType));
    }

    public function provideAuthorizedTabBodyType(): \Generator
    {
        yield 'with derniers-dossiers' => [TabBodyType::TAB_DATA_TYPE_DERNIER_ACTION_DOSSIERS];
        yield 'with dossiers-form-pro' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_PRO];
        yield 'with dossiers-form-usager' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_USAGER];
        yield 'with dossiers-non-affectation' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_NON_AFFECTATION];
        yield 'with dossiers-ferme-partenaire-tous' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FERME_PARTENAIRE_TOUS];
        yield 'with dossiers-demandes-fermeture-usager' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_DEMANDE_FERMETURE_USAGER];
        yield 'with dossiers-relance-usager-sans-reponse' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_RELANCE_USAGER_SANS_REPONSE];
        yield 'with dossiers-messages-nouveaux' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_NOUVEAUX];
        yield 'with dossiers-messages-apres-fermeture' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_APRES_FERMETURE];
        yield 'with dossiers-messages-usagers-sans-reponse' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_USAGERS_SANS_REPONSE];
        yield 'with dossiers-sans-activite-partenaire' => [TabBodyType::TAB_DATA_TYPE_SANS_ACTIVITE_PARTENAIRE];
    }

    public function provideUnauthorizedTabBodyType(): \Generator
    {
        yield 'with dossiers-form-pro' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_PRO];
        yield 'with dossiers-form-usager' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_USAGER];
        yield 'with dossiers-ferme-partenaire-tous' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_FERME_PARTENAIRE_TOUS];
        yield 'with dossiers-demandes-fermeture-usager' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_DEMANDE_FERMETURE_USAGER];
        yield 'with dossiers-relance-usager-sans-reponse' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_RELANCE_USAGER_SANS_REPONSE];
        yield 'with dossiers-messages-apres-fermeture' => [TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_APRES_FERMETURE];
    }
}
