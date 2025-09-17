<?php

namespace App\Tests\Functional\Repository;

use App\Entity\File;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\FileRepository;
use App\Service\ListFilters\SearchTerritoryFiles;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FileRepositoryTest extends KernelTestCase
{
    private FileRepository $fileRepository;

    private EntityManagerInterface $entityManager;

    public const USER_ADMIN = 'admin-01@signal-logement.fr';
    public const USER_ADMIN_TERRITORY_13 = 'admin-territoire-13-01@signal-logement.fr';

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->fileRepository = $this->entityManager->getRepository(File::class);
    }

    public function testFindFilteredPaginatedWithoutUser(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $filters = new SearchTerritoryFiles($user);

        $paginator = $this->fileRepository->findFilteredPaginated(
            $filters,
            null,
            20,
            null
        );
        $this->assertCount(6, $paginator);
        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertSame('1_Demande_de_transmission_d_une_copie_d_un_DPE.docx', $paginator->getIterator()[0]->getFilename());
    }

    public function testFindFilteredPaginatedWithExclusiveTerritory(): void
    {
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '13']);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN_TERRITORY_13]);
        $filters = new SearchTerritoryFiles($user);
        $filters->setTerritory($territory);
        $territories = [];
        $territories[$territory->getId()] = $territory;
        $paginator = $this->fileRepository->findFilteredPaginated(
            $filters,
            $territories,
            20,
            null
        );
        $this->assertCount(0, $paginator);
        $this->assertInstanceOf(Paginator::class, $paginator);
    }

    public function testFindFilteredPaginatedWithUser(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $filters = new SearchTerritoryFiles($user);

        $results = $this->fileRepository->findFilteredPaginated(
            $filters,
            null,
            20,
            $user
        );
        $this->assertIsArray($results);
        $this->assertCount(4, $results);
        $this->assertArrayHasKey('items', $results);
        $this->assertArrayHasKey('total', $results);
        $this->assertArrayHasKey('page', $results);
        $this->assertArrayHasKey('perPage', $results);
        $this->assertSame(6, $results['total']);
        $this->assertSame(1, $results['page']);
        $this->assertSame(20, $results['perPage']);
        $this->assertInstanceOf(File::class, $results['items'][0]);
        $this->assertSame('1_Demande_de_transmission_d_une_copie_d_un_DPE.docx', $results['items'][0]->getFilename());
    }
}
