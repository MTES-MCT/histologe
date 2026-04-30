<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Enum\DocumentType;
use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Service\Signalement\PhotoHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PhotoHelperTest extends KernelTestCase
{
    private SignalementRepository $signalementRepository;

    protected function setUp(): void
    {
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
    }

    public function testGetPhotosBySlug(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-27']);

        $desordrePrecisionSlug = 'desordres_batiment_proprete_interieur';
        $photos = PhotoHelper::getPhotosBySlug($signalement, $desordrePrecisionSlug);
        $this->assertCount(1, $photos);
        $firstKey = array_keys($photos)[0];
        $this->assertEquals(DocumentType::PHOTO_SITUATION, $photos[$firstKey]->getDocumentType());
        $this->assertEquals('Capture-d-ecran-du-2023-06-13-12-58-43-648b2a6b9730f.png', $photos[$firstKey]->getTitle());

        $desordrePrecisionSlug = 'desordres_batiment_isolation_murs';
        $photos = PhotoHelper::getPhotosBySlug($signalement, $desordrePrecisionSlug);
        $this->assertCount(0, $photos);
    }

    public function testGetSortedPhotos(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2023-27']);

        $photos = PhotoHelper::getSortedPhotos($signalement);
        $this->assertCount(3, $photos);
        $this->assertEquals(DocumentType::PHOTO_SITUATION, $photos[0]->getDocumentType());
        $this->assertEquals('Capture-d-ecran-du-2023-06-13-12-58-43-648b2a6b9730f.png', $photos[0]->getTitle());
        $this->assertEquals(DocumentType::AUTRE, $photos[1]->getDocumentType());
        $this->assertEquals(DocumentType::PHOTO_SITUATION, $photos[2]->getDocumentType());
    }
}
