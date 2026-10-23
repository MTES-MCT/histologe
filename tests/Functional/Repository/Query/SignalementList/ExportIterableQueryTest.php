<?php

namespace App\Tests\Functional\Repository\Query\SignalementList;

use App\Dto\SignalementExport;
use App\Entity\Enum\ProcedureCategory;
use App\Entity\Enum\ProcedureType;
use App\Entity\Signalement;
use App\Entity\SignalementProcedure;
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

    public function testStreamWithProcedures(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2024-12']);

        // Les fixtures ne contiennent que des procédures à engager : on ajoute une procédure retenue (fermeture)
        $signalementProcedure = (new SignalementProcedure())
            ->setSignalement($signalement)
            ->setProcedureType(ProcedureType::MISE_EN_SECURITE_PERIL)
            ->setProcedureCategory(ProcedureCategory::PROCEDURE_RETENUE);
        $this->entityManager->persist($signalementProcedure);
        $this->entityManager->flush();

        $selectedColumns = [
            'REFERENCE',
            'PROCEDURES_A_ENGAGER',
            'PROCEDURES_RETENUES',
        ];

        $generator = $this->exportIterableQuery->stream($user, [], $selectedColumns);
        $results = array_column(iterator_to_array($generator, false), null, 'id');

        $this->assertArrayHasKey($signalement->getId(), $results);
        $result = $results[$signalement->getId()];
        $this->assertEquals(
            implode(SignalementExport::SEPARATOR_GROUP_CONCAT, ['Non décence', 'Infraction RSHS/RSD', 'Insalubrité']),
            $result['proceduresAEngager']
        );
        $this->assertEquals('Mise en sécurité / Péril', $result['proceduresRetenues']);

        foreach ($results as $id => $otherResult) {
            if ($id !== $signalement->getId()) {
                $this->assertArrayNotHasKey('proceduresAEngager', $otherResult);
                $this->assertArrayNotHasKey('proceduresRetenues', $otherResult);
            }
        }
    }
}
