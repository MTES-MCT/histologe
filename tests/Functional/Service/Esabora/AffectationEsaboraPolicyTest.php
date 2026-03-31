<?php

namespace App\Tests\Functional\Service\Esabora;

use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Service\Interconnection\Esabora\AffectationEsaboraPolicy;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AffectationEsaboraPolicyTest extends KernelTestCase
{
    private PartnerRepository $partnerRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->partnerRepository = self::getContainer()->get(PartnerRepository::class);
    }

    /**
     * @dataProvider providePartnerIds
     */
    public function testHasUrlConflict(array $partnerIds, bool $result): void
    {
        $affectationEsaboraPolicy = new AffectationEsaboraPolicy($this->partnerRepository);
        self::assertSame($result, $affectationEsaboraPolicy->hasUrlConflict($partnerIds));
    }

    /**
     * @dataProvider providePartnerName
     */
    public function testCanBeAffected(string $partnerName, bool $result): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2024-10']);
        $partner = $this->partnerRepository->findOneBy(['nom' => $partnerName]);

        $affectationEsaboraPolicy = new AffectationEsaboraPolicy($this->partnerRepository);
        self::assertSame($result, $affectationEsaboraPolicy->canBeAffected($signalement, $partner));
    }

    public function providePartnerIds(): \Generator
    {
        yield 'Partners with same url' => [[7, 94], true];
        yield 'Partners with different url' => [[6, 7], false];
        yield 'Partners with no url' => [[1, 2], false];
        yield 'Partners with empty array' => [[], false];
    }

    public function providePartnerName(): \Generator
    {
        yield 'PARTENAIRE SCHS VIA SANTÉ HABITAT' => ['PARTENAIRE SCHS VIA SANTÉ HABITAT', false];
        yield 'PARTENAIRE 13-05 ESABORA SCHS' => ['PARTENAIRE 13-05 ESABORA SCHS', true];
        yield 'PARTENAIRE 13-01' => ['PARTENAIRE 13-01', true];
    }
}
