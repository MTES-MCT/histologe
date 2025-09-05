<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SettingsControllerTest extends WebTestCase
{
    public function testSettings(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $client->loginUser($user);

        $router = self::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate('back_settings'));

        $this->assertEquals('200', $client->getResponse()->getStatusCode());
        $responseContent = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('firstname', $responseContent);
        $this->assertArrayHasKey('lastname', $responseContent);
        $this->assertArrayHasKey('roleLabel', $responseContent);
        $this->assertArrayHasKey('territories', $responseContent);
    }

    public function testSettingsWithTerritory(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate('back_settings', ['territoryId' => 13]));

        $this->assertEquals('200', $client->getResponse()->getStatusCode());
        $responseContent = json_decode((string) $client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('partners', $responseContent);
        $this->assertArrayHasKey('epcis', $responseContent);
        $this->assertArrayHasKey('tags', $responseContent);
    }
}
