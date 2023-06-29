<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class BackArchivedAccountControllerTest extends WebTestCase
{
    use SessionHelper;

    public function testAccountList(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_account_index');
        $client->request('GET', $route);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    public function testAccountListWithTerritory(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['zip' => '01']);

        $route = $router->generate('back_account_index', [
            'territory' => $territory->getId(),
        ]);
        $client->request('GET', $route);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
    }

    public function testAccountReactivateActiveUser(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $accountEmail = 'user-01-01@histologe.fr';
        /** @var User $account */
        $account = $userRepository->findOneBy(['email' => $accountEmail]);
        $route = $router->generate('back_account_reactiver', [
            'id' => $account->getId(),
        ]);
        $client->request('GET', $route);
        $this->assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $client->getResponse()->getStatusCode(),
            sprintf('Result value: %d', $client->getResponse()->getStatusCode())
        );
        $this->assertResponseRedirects('/bo/comptes-archives/');
    }

    public function testAccountReactivate(): void
    {
        $faker = Factory::create();

        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
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

        $accountEmail = 'user-01-09@histologe.fr';
        /** @var User $account */
        $account = $userRepository->findOneBy(['email' => $accountEmail]);
        $route = $router->generate('back_account_reactiver', [
            'id' => $account->getId(),
        ]);

        $crawler = $client->request('GET', $route);

        $buttonCrawlerNode = $crawler->selectButton('submit_btn_account');
        $form = $buttonCrawlerNode->form();

        $form['user[prenom]'] = $faker->name();
        $form['user[nom]'] = $faker->lastName();
        $form['user[email]'] = (string) $account->getEmail();
        $form['user[territory]'] = (string) $territory->getId();
        $form['user[partner]'] = (string) $partner->getId();
        $client->submit($form);

        /** @var User $account */
        $account = $userRepository->findOneBy(['email' => $accountEmail]);
        $this->assertEquals(USER::STATUS_ACTIVE, $account->getStatut());
        $this->assertResponseRedirects('/bo/comptes-archives/');
    }

    public function testAccountReactivateError(): void
    {
        $faker = Factory::create();

        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $accountEmail = 'admin-02@histologe.fr';
        /** @var User $account */
        $account = $userRepository->findOneBy(['email' => $accountEmail]);
        $route = $router->generate('back_account_reactiver', [
            'id' => $account->getId(),
        ]);

        $crawler = $client->request('GET', $route);

        $buttonCrawlerNode = $crawler->selectButton('submit_btn_account');
        $form = $buttonCrawlerNode->form();

        $form['user[prenom]'] = $faker->name();
        $form['user[nom]'] = $faker->lastName();
        $form['user[email]'] = (string) $account->getEmail();
        $form['user[territory]'] = '';
        $form['user[partner]'] = '';
        $client->submit($form);

        /** @var User $account */
        $account = $userRepository->findOneBy(['email' => $accountEmail]);
        $this->assertEquals(USER::STATUS_ARCHIVE, $account->getStatut());
    }
}
