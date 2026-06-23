<?php

namespace App\Tests\Functional\Repository\Query\SignalementList;

use App\Entity\User;
use App\Repository\Query\SignalementList\ExportIterableQuery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ExportIterableQueryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ExportIterableQuery $exportIterableQuery;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->exportIterableQuery = self::getContainer()->get(ExportIterableQuery::class);
    }

    public function testStreamWithPhotosAndDocuments(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $selectedColumns = [
            'REFERENCE',
            'PHOTOS',
            'DOCUMENTS',
        ];

        $generator = $this->exportIterableQuery->stream($user, [], $selectedColumns);
        $results = iterator_to_array($generator);

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertArrayHasKey('id', $result);
            $this->assertArrayHasKey('reference', $result);
            $this->assertArrayHasKey('photosName', $result);
            $this->assertArrayHasKey('documentsName', $result);
        }
    }
}
