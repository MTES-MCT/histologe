<?php

namespace App\Tests\Functional\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EsaboraServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private HttpClientInterface $client;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->client = self::getContainer()->get(HttpClientInterface::class);
    }

    public function testPushDossier(): void
    {
        $this->assertTrue(true);
    }
}
