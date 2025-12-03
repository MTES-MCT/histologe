<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\UserSearchFilter;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserSearchFilterControllerTest extends WebTestCase
{
    use SessionHelper;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    public function testSaveSearchInvalidCsrf(): void
    {
        $client = static::createClient();
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_save');

        $userRepository = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']));

        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                '_token' => 'bad_token',
                'name' => 'Test',
                'params' => ['isImported' => 'oui'],
            ])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response['status']);
    }

    public function testSaveSearchEmptyParams(): void
    {
        $client = static::createClient();
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_save');

        $userRepository = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']));
        $csrfToken = $this->generateCsrfToken($client, 'save_search');
        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                '_token' => $csrfToken,
                'name' => 'Test',
                'params' => null,
            ])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response['status']);
    }

    public function testSaveSearchLimitReached(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_save');

        $userRepo = static::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);
        $csrfToken = $this->generateCsrfToken($client, 'save_search');

        for ($i = 0; $i < 5; ++$i) {
            $search = new UserSearchFilter();
            $search->setUser($user);
            $search->setName("Test $i");
            $search->setParams(['x' => $i]);
            $em->persist($search);
        }
        $em->flush();

        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                '_token' => $csrfToken,
                'name' => 'Too Many',
                'params' => ['isImported' => 'oui'],
            ])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response['status']);
    }

    public function testSaveSearchSuccess(): void
    {
        $client = static::createClient();
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_save');

        $userRepo = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepo->findOneBy(['email' => 'admin-01@signal-logement.fr']));
        $csrfToken = $this->generateCsrfToken($client, 'save_search');

        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                '_token' => $csrfToken,
                'name' => 'Recherche Test',
                'params' => ['isImported' => 'oui'],
            ])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $response['status']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('savedSearch', $response['data']);
    }

    public function testDeleteSearchInvalidCsrf(): void
    {
        $client = static::createClient();
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_delete', ['id' => 999]);

        $userRepo = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepo->findOneBy(['email' => 'admin-01@signal-logement.fr']));

        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['_token' => 'bad_token'])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response['status']);
    }

    public function testDeleteSearchNotFound(): void
    {
        $client = static::createClient();
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_delete', ['id' => 999999]);

        $userRepo = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepo->findOneBy(['email' => 'admin-01@signal-logement.fr']));
        $csrfToken = $this->generateCsrfToken($client, 'delete_search');

        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['_token' => $csrfToken])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response['status']);
    }

    public function testDeleteSearchSuccess(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $repo = static::getContainer()->get(UserRepository::class);
        $user = $repo->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $search = new UserSearchFilter();
        $search->setUser($user);
        $search->setName('À supprimer');
        $search->setParams(['demo' => true]);
        $em->persist($search);
        $em->flush();

        $csrfToken = $this->generateCsrfToken($client, 'delete_search');
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_delete', ['id' => $search->getId()]);

        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['_token' => $csrfToken])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $response['status']);
    }

    public function testDeleteSearchNotOwned(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $userRepo = static::getContainer()->get(UserRepository::class);

        $owner = $userRepo->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        $intruder = $userRepo->findOneBy(['email' => 'admin-territoire-69-mdl@signal-logement.fr']);

        $search = new UserSearchFilter();
        $search->setUser($owner);
        $search->setName('Interdit');
        $search->setParams(['demo' => true]);
        $em->persist($search);
        $em->flush();

        $client->loginUser($intruder);

        $csrfToken = $this->generateCsrfToken($client, 'delete_search');
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_delete', ['id' => $search->getId()]);

        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['_token' => $csrfToken])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response['status']);
    }

    public function testEditSearchInvalidCsrf(): void
    {
        $client = static::createClient();
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_edit', ['id' => 1234]);

        $userRepo = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepo->findOneBy(['email' => 'admin-01@signal-logement.fr']));

        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['_token' => 'bad_token', 'name' => 'Popopolop',])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response['status']);
    }

    public function testEditSearchEmptyName(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $userRepo = static::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $search = new UserSearchFilter();
        $search->setUser($user);
        $search->setName('Original');
        $search->setParams(['demo' => true]);
        $em->persist($search);
        $em->flush();

        $csrf = $this->generateCsrfToken($client, 'edit_search');
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_edit', ['id' => $search->getId()]);

        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                '_token' => $csrf,
                'name' => '',
            ])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response['status']);
    }

    public function testEditSearchTooLongName(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $userRepo = static::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $search = new UserSearchFilter();
        $search->setUser($user);
        $search->setName('Original');
        $search->setParams(['demo' => true]);
        $em->persist($search);
        $em->flush();

        $csrf = $this->generateCsrfToken($client, 'edit_search');
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_edit', ['id' => $search->getId()]);

        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                '_token' => $csrf,
                'name' => str_repeat('A', 70),
            ])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response['status']);
    }

    public function testEditSearchNotFound(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get(UserRepository::class);
        $client->loginUser($userRepo->findOneBy(['email' => 'admin-01@signal-logement.fr']));

        $csrf = $this->generateCsrfToken($client, 'edit_search');
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_edit', ['id' => 999999]);

        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                '_token' => $csrf,
                'name' => 'Nouvelle valeur',
            ])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response['status']);
    }

    public function testEditSearchSuccess(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $userRepo = static::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $search = new UserSearchFilter();
        $search->setUser($user);
        $search->setName('Ancien nom');
        $search->setParams(['demo' => true]);
        $em->persist($search);
        $em->flush();

        $csrf = $this->generateCsrfToken($client, 'edit_search');
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_edit', ['id' => $search->getId()]);

        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                '_token' => $csrf,
                'name' => 'Nom modifié',
            ])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $response['status']);
    }

    public function testSaveSearchDuplicateName(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $url = static::getContainer()->get(UrlGeneratorInterface::class)
            ->generate('back_user_search_filters_save');

        $userRepo = static::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        // On crée une recherche existante
        $existing = new UserSearchFilter();
        $existing->setUser($user);
        $existing->setName('Nom Dupliqué');
        $existing->setParams(['demo' => 1]);

        $em->persist($existing);
        $em->flush();

        // On tente d’en créer une seconde avec le même nom
        $csrfToken = $this->generateCsrfToken($client, 'save_search');
        $client->request(
            'POST',
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                '_token' => $csrfToken,
                'name' => 'Nom Dupliqué',
                'params' => ['isImported' => 'oui'],
            ])
        );

        $response = json_decode((string) $client->getResponse()->getContent(), true);

        // Vérifie que l’unicité est bien bloquante
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response['status']);
        $this->assertStringContainsString(
            'déjà enregistré',
            $response['message'] ?? '',
            'Le message doit indiquer un problème d’unicité.'
        );
    }
}
