<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResourceControllerTest extends WebTestCase
{
    public function testGetResourceList()
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'admin-01@histologe.fr',
        ]);
        $client->loginUser($user, 'api');
        $client->request('GET', '/api/resources');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
