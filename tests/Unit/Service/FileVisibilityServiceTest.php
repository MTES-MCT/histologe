<?php

namespace App\Tests\Unit\Service;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\File;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\FileVisibilityService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FileVisibilityServiceTest extends TestCase
{
    private FileVisibilityService $service;

    protected function setUp(): void
    {
        $this->service = new FileVisibilityService();
    }

    public function testReturnsAllFilesForSuperAdmin(): void
    {
        /** @var MockObject&File $file1 */
        $file1 = $this->createMock(File::class);
        /** @var MockObject&File $file2 */
        $file2 = $this->createMock(File::class);

        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $user->method('isSuperAdmin')->willReturn(true);
        $user->method('isTerritoryAdmin')->willReturn(false);

        $result = $this->service->filterFilesForUser([$file1, $file2], $user);

        $this->assertCount(2, $result);
        $this->assertSame([$file1, $file2], array_values($result));
    }

    public function testReturnsAllFilesForTerritoryAdmin(): void
    {
        /** @var MockObject&File $file */
        $file = $this->createMock(File::class);

        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $user->method('isSuperAdmin')->willReturn(false);
        $user->method('isTerritoryAdmin')->willReturn(true);

        $result = $this->service->filterFilesForUser([$file], $user);

        $this->assertCount(1, $result);
    }

    public function testReturnsAllFilesForNullUser(): void
    {
        /** @var MockObject&File $file */
        $file = $this->createMock(File::class);

        $result = $this->service->filterFilesForUser([$file], null);

        $this->assertCount(1, $result);
    }

    public function testReturnsFileWithoutPartnerRestrictions(): void
    {
        $territory = new Territory();
        /** @var MockObject&File $file */
        $file = $this->createMock(File::class);
        $file->method('getPartnerType')->willReturn([]);
        $file->method('getPartnerCompetence')->willReturn([]);
        $file->method('getTerritory')->willReturn($territory);

        $partner = new Partner();

        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $user->method('isSuperAdmin')->willReturn(false);
        $user->method('isTerritoryAdmin')->willReturn(false);
        $user->method('getPartnerInTerritory')->with($territory)->willReturn($partner);

        $result = $this->service->filterFilesForUser([$file], $user);

        $this->assertCount(1, $result);
    }

    public function testFiltersOutFileIfNoPartnerMatches(): void
    {
        $territory = new Territory();

        /** @var MockObject&File $file */
        $file = $this->createMock(File::class);
        $file->method('getPartnerType')->willReturn([]);
        $file->method('getPartnerCompetence')->willReturn([Qualification::VISITES]);
        $file->method('getTerritory')->willReturn($territory);

        $partner = new Partner();

        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $user->method('isSuperAdmin')->willReturn(false);
        $user->method('isTerritoryAdmin')->willReturn(false);
        $user->method('getPartnerInTerritory')->with($territory)->willReturn($partner);

        $result = $this->service->filterFilesForUser([$file], $user);

        $this->assertCount(0, $result);
    }

    public function testReturnsFileIfPartnerMatches(): void
    {
        $territory = new Territory();
        /** @var MockObject&File $file */
        $file = $this->createMock(File::class);
        $file->method('getPartnerType')->willReturn([PartnerType::ARS]);
        $file->method('getPartnerCompetence')->willReturn([Qualification::VISITES]);
        $file->method('getTerritory')->willReturn($territory);

        $partner = new Partner();
        $partner->setType(PartnerType::ARS);
        $partner->setCompetence([Qualification::VISITES, Qualification::ACCOMPAGNEMENT_JURIDIQUE]);

        /** @var MockObject&User $user */
        $user = $this->createMock(User::class);
        $user->method('isSuperAdmin')->willReturn(false);
        $user->method('isTerritoryAdmin')->willReturn(false);
        $user->method('getPartnerInTerritory')->with($territory)->willReturn($partner);

        $result = $this->service->filterFilesForUser([$file], $user);

        $this->assertCount(1, $result);
        $this->assertSame($file, array_values($result)[0]);
    }
}
