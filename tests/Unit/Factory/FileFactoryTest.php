<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class FileFactoryTest extends TestCase
{
    use FixturesHelper;

    public function testCreateInstance(): void
    {
        $file = (new FileFactory())->createInstanceFrom(
            'sample-123.jpg',
            'sample.jpg',
            File::FILE_TYPE_PHOTO,
            $this->getSignalement(),
            $this->getUser([User::ROLE_USER_PARTNER])
        );
        $this->assertEquals('sample-123.jpg', $file->getFilename());
        $this->assertEquals('sample.jpg', $file->getTitle());
        $this->assertEquals('photo', $file->getFileType());
        $this->assertInstanceOf(Signalement::class, $file->getSignalement());
        $this->assertInstanceOf(User::class, $file->getUploadedBy());
        $this->assertInstanceOf(\DateTimeImmutable::class, $file->getCreatedAt());
        $this->assertEquals(DocumentType::AUTRE, $file->getDocumentType());
    }

    /**
     * @dataProvider provideFileItem
     */
    public function testCreateFromArray(
        array $dataItem,
        string $filename,
        string $fileType,
        DocumentType $documentType
    ): void {
        $signalement = $this->getSignalement();
        $file = (new FileFactory())->createFromFileArray($dataItem, $signalement);

        $this->assertEquals($fileType, $file->getFileType());
        $this->assertEquals($documentType, $file->getDocumentType());
        $this->assertEquals($filename, $file->getFilename());
        if (DocumentType::PHOTO_SITUATION === $file->getDocumentType()
        && (\in_array($dataItem['slug'], $signalement->getDesordreCritereSlugs())
        || \in_array($dataItem['slug'], $signalement->getDesordrePrecisionSlugs()))) {
            $this->assertNotEmpty($file->getDesordreSlug());
        }
    }

    public function provideFileItem(): \Generator
    {
        yield 'DPE document' => [
            [
                'key' => 'documents',
                'file' => 'dummy-filename-dpe.pdf',
                'titre' => 'dummy-filename-dpe-titre.pdf',
                'slug' => 'bail_dpe_dpe_upload',
            ],
            'dummy-filename-dpe.pdf',
            File::FILE_TYPE_DOCUMENT,
            DocumentType::SITUATION_FOYER_DPE,
        ];

        yield 'DPE photo' => [
            [
                'key' => 'photos',
                'file' => 'dummy-filename-dpe.PNG',
                'titre' => 'dummy-filename-dpe-titre.PNG',
                'slug' => 'bail_dpe_dpe_upload',
            ],
            'dummy-filename-dpe.PNG',
            File::FILE_TYPE_DOCUMENT,
            DocumentType::SITUATION_FOYER_DPE,
        ];

        yield 'Etat des lieux document' => [
            [
                'key' => 'documents',
                'file' => 'dummy-filename-dpe.pdf',
                'titre' => 'dummy-filename-dpe-titre.pdf',
                'slug' => 'bail_dpe_etat_des_lieux_upload',
            ],
            'dummy-filename-dpe.pdf',
            File::FILE_TYPE_DOCUMENT,
            DocumentType::SITUATION_FOYER_ETAT_DES_LIEUX,
        ];

        yield 'Bail document' => [
            [
                'key' => 'documents',
                'file' => 'dummy-filename-bail.png',
                'titre' => 'dummy-filename-bail-titre.png',
                'slug' => 'bail_dpe_bail_upload',
            ],
            'dummy-filename-bail.png',
            File::FILE_TYPE_DOCUMENT,
            DocumentType::SITUATION_FOYER_BAIL,
        ];

        yield 'Bail photo' => [
            [
                'key' => 'photos',
                'file' => 'dummy-filename-bail.png',
                'titre' => 'dummy-filename-bail-titre.png',
                'slug' => 'bail_dpe_bail_upload',
            ],
            'dummy-filename-bail.png',
            File::FILE_TYPE_DOCUMENT,
            DocumentType::SITUATION_FOYER_BAIL,
        ];

        yield 'Désordre document' => [
            [
                'key' => 'documents',
                'file' => 'dummy-filename-desordre.pdf',
                'titre' => 'dummy-filename-desordre-titre.pdf',
                'slug' => 'desordres_batiment_isolation_photos_upload',
            ],
            'dummy-filename-desordre.pdf',
            File::FILE_TYPE_DOCUMENT,
            DocumentType::PHOTO_SITUATION,
        ];

        yield 'Désordre photos pdf' => [
            [
                'key' => 'photos',
                'file' => 'dummy-filename-desordre.pdf',
                'titre' => 'dummy-filename-desordre-titre.pdf',
                'slug' => 'desordres_batiment_isolation_photos_upload',
            ],
            'dummy-filename-desordre.pdf',
            File::FILE_TYPE_DOCUMENT,
            DocumentType::PHOTO_SITUATION,
        ];

        yield 'Désordre photos ' => [
            [
                'key' => 'photos',
                'file' => 'dummy-filename-desordre.png',
                'titre' => 'dummy-filename-desordre-titre.png',
                'slug' => 'desordres_batiment_isolation_photos_upload',
            ],
            'dummy-filename-desordre.png',
            File::FILE_TYPE_PHOTO,
            DocumentType::PHOTO_SITUATION,
        ];
    }
}
