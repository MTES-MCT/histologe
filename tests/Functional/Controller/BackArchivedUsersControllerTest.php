<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class BackArchivedUsersControllerTest extends WebTestCase
{
    use SessionHelper;

    public function testAccountList(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_archived_users_index');
        $crawler = $client->request('GET', $route);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            \sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
        $this->assertEquals(1, $crawler->filter('h2:contains("9 comptes archivés ou sans territoire et/ou partenaire trouvés")')->count());
    }

    public function testAccountListWithTerritory(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['zip' => '01']);

        $route = $router->generate('back_archived_users_index', [
            'territory' => $territory->getId(),
        ]);
        $client->request('GET', $route);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            \sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    public function testAccountReactivateActiveUser(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $accountEmail = 'user-01-01@signal-logement.fr';
        /** @var User $account */
        $account = $userRepository->findOneBy(['email' => $accountEmail]);
        $route = $router->generate('back_archived_users_reactiver', [
            'id' => $account->getId(),
        ]);
        $client->request('GET', $route);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            \sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
        $this->assertResponseRedirects('/bo/comptes-archives/');
    }

    public function testAccountReactivate(): void
    {
        $faker = Factory::create();

        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $accountEmail = 'user-01-09@signal-logement.fr';
        /** @var User $account */
        $account = $userRepository->findArchivedUserByEmail($accountEmail);
        $route = $router->generate('back_archived_users_reactiver', [
            'id' => $account->getId(),
        ]);

        $user = $account->getUserPartners()->first();
        if (!$user) {
            $this->fail('No user found for the account');
        }
        $partner = $user->getPartner();

        $crawler = $client->request('GET', $route);

        $buttonCrawlerNode = $crawler->selectButton('submit_btn_account');
        $form = $buttonCrawlerNode->form();

        $form['user[prenom]'] = $faker->name();
        $form['user[nom]'] = $faker->lastName();
        $form['user[email]'] = (string) $account->getEmail();
        $form['user[territory]'] = (string) $partner->getTerritory()->getId();
        $form['user[tempPartner]'] = (string) $partner->getId();
        $client->submit($form);

        /** @var User $account */
        $account = $userRepository->findOneBy(['email' => $accountEmail]);
        $this->assertEquals(UserStatus::ACTIVE, $account->getStatut());
        $this->assertResponseRedirects('/bo/comptes-archives/');
    }

    public function testAccountReactivateDuplicateEmail(): void
    {
        $faker = Factory::create();

        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['zip' => '01']);

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = static::getContainer()->get(PartnerRepository::class);
        $partner = $partnerRepository->findOneBy([
            'territory' => $territory->getId(),
            'isArchive' => '0',
        ]);

        $accountEmail = 'user-01-06@signal-logement.fr'.User::SUFFIXE_ARCHIVED;
        /** @var User $account */
        $account = $userRepository->findArchivedUserByEmail($accountEmail);
        $route = $router->generate('back_archived_users_reactiver', [
            'id' => $account->getId(),
        ]);

        $crawler = $client->request('GET', $route);

        $buttonCrawlerNode = $crawler->selectButton('submit_btn_account');
        $form = $buttonCrawlerNode->form();

        $form['user[prenom]'] = $faker->name();
        $form['user[nom]'] = $faker->lastName();
        $form['user[email]'] = (string) $account->getEmail();
        $form['user[territory]'] = (string) $territory->getId();
        $form['user[tempPartner]'] = '';
        $client->submit($form);

        /** @var User $account */
        $account = $userRepository->findArchivedUserByEmail($accountEmail);
        $this->assertEquals(UserStatus::ARCHIVE, $account->getStatut());
    }

    public function testAccountReactivateError(): void
    {
        $faker = Factory::create();

        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $accountEmail = 'admin-02@signal-logement.fr';
        /** @var User $account */
        $account = $userRepository->findArchivedUserByEmail($accountEmail);
        $route = $router->generate('back_archived_users_reactiver', [
            'id' => $account->getId(),
        ]);

        $crawler = $client->request('GET', $route);

        $buttonCrawlerNode = $crawler->selectButton('submit_btn_account');
        $form = $buttonCrawlerNode->form();

        $form['user[prenom]'] = $faker->name();
        $form['user[nom]'] = $faker->lastName();
        $form['user[email]'] = (string) $account->getEmail();
        $form['user[territory]'] = '';
        $form['user[tempPartner]'] = '';
        $client->submit($form);

        /** @var User $account */
        $account = $userRepository->findArchivedUserByEmail($accountEmail);
        $this->assertEquals(UserStatus::ARCHIVE, $account->getStatut());
    }

    public function testAccountReactivateAnonymizedUser(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        /** @var User $account */
        $account = $userRepository->findAnonymizedUsers()[0];
        $route = $router->generate('back_archived_users_reactiver', [
            'id' => $account->getId(),
        ]);

        $client->request('GET', $route);
        $this->assertResponseRedirects('/bo/comptes-archives/');
    }

    public function testAccountReactivateUnlinkedUser(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['zip' => '01']);

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = static::getContainer()->get(PartnerRepository::class);
        $partner = $partnerRepository->findOneBy([
            'territory' => $territory->getId(),
            'isArchive' => '0',
        ]);

        $accountEmail = 'user-unlinked@signal-logement.fr';
        /** @var User $account */
        $account = $userRepository->findOneBy(['email' => $accountEmail]);
        $route = $router->generate('back_archived_users_reactiver', [
            'id' => $account->getId(),
        ]);

        $crawler = $client->request('GET', $route);

        $buttonCrawlerNode = $crawler->selectButton('submit_btn_account');
        $form = $buttonCrawlerNode->form();

        $form['user[prenom]'] = (string) $account->getPrenom();
        $form['user[nom]'] = (string) $account->getNom();
        $form['user[email]'] = (string) $account->getEmail();
        $form['user[territory]'] = (string) $territory->getId();
        $client->submit($form);

        $crawler = $client->getCrawler();
        $buttonCrawlerNode = $crawler->selectButton('submit_btn_account');
        $form = $buttonCrawlerNode->form();

        $form['user[tempPartner]'] = (string) $partner->getId();
        $client->submit($form);

        /** @var User $account */
        $account = $userRepository->findOneBy(['email' => $accountEmail]);
        $this->assertEquals(UserStatus::ACTIVE, $account->getStatut());
        $this->assertResponseRedirects('/bo/comptes-archives/');
    }
}
