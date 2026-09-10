<?php

namespace App\Tests\Functional\Service\Esabora;

use App\Repository\PartnerRepository;
use App\Service\Interconnection\Esabora\AffectationEsaboraPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AffectationEsaboraPolicyTest extends KernelTestCase
{
    private PartnerRepository $partnerRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->partnerRepository = static::getContainer()->get(PartnerRepository::class);
    }

    /**
     * @param array<int> $partnerIds
     */
    #[DataProvider('providePartnerIds')]
    public function testHasUrlConflict(array $partnerIds, bool $result): void
    {
        $affectationEsaboraPolicy = new AffectationEsaboraPolicy(true);
        $partners = $this->partnerRepository->findByIds(array_values($partnerIds));
        $this->assertSame($result, $affectationEsaboraPolicy->hasUrlConflict($partners));
    }

    public static function providePartnerIds(): \Generator
    {
        yield 'Partners with same url' => [[7, 94], true];
        yield 'Partners with different url' => [[6, 7], false];
        yield 'Partners with no url' => [[1, 2], false];
        yield 'Partners with empty array' => [[], false];
    }
}
