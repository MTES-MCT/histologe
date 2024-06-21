<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Service\Esabora\CiviliteMapper;
use App\Service\Esabora\Enum\PersonneQualite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CiviliteMapperTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private SignalementRepository $signalementRepository;

    private const OLD_SIGNALEMENT_UUID = '00000000-0000-0000-2023-000000000008';
    private const NEW_SIGNALEMENT_UUID = '00000000-0000-0000-2023-000000000027';

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->signalementRepository = $this->entityManager->getRepository(Signalement::class);
    }

    public function testMapOccupant(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => self::NEW_SIGNALEMENT_UUID]);
        $this->assertEquals(PersonneQualite::MADAME, CiviliteMapper::mapOccupant($signalement));

        $signalement = $this->signalementRepository->findOneBy(['uuid' => self::OLD_SIGNALEMENT_UUID]);
        $this->assertEquals(PersonneQualite::MADAME_MONSIEUR, CiviliteMapper::mapOccupant($signalement));
    }

    public function testMapProprio(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => self::NEW_SIGNALEMENT_UUID]);
        $this->assertEquals(PersonneQualite::SOCIETE, CiviliteMapper::mapProprio($signalement));

        $signalement = $this->signalementRepository->findOneBy(['uuid' => self::OLD_SIGNALEMENT_UUID]);
        $this->assertNull(CiviliteMapper::mapProprio($signalement));
    }

    public function testMapDeclarant(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => self::NEW_SIGNALEMENT_UUID]);
        $this->assertNull(CiviliteMapper::mapDeclarant($signalement));

        $signalement = $this->signalementRepository->findOneBy(['uuid' => self::OLD_SIGNALEMENT_UUID]);
        $this->assertNull(CiviliteMapper::mapDeclarant($signalement));
    }
}
