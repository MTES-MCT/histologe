<?php

namespace App\Tests\Unit\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\SignalementDraft;
use App\Repository\SignalementDraftRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Signalement\SignalementDraftHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementDraftHelperTest extends KernelTestCase
{
    /**
     * @dataProvider provideDeclarantData
     */
    public function testDeclarantData(string $draftUuid, bool $isTiersDeclarant, string $emailDeclarant): void
    {
        /** @var SignalementDraftRepository $signalementDraftRepository */
        $signalementDraftRepository = static::getContainer()->get(SignalementDraftRepository::class);
        /** @var SignalementDraft $signalementDraft */
        $signalementDraft = $signalementDraftRepository->findOneBy(['uuid' => $draftUuid]);

        /** @var SignalementDraftRequestSerializer $serializer */
        $serializer = static::getContainer()->get(SignalementDraftRequestSerializer::class);
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $serializer->denormalize(
            $signalementDraft->getPayload(),
            SignalementDraftRequest::class
        );

        $this->assertEquals(SignalementDraftHelper::isTiersDeclarant($signalementDraftRequest), $isTiersDeclarant);
    }

    public function provideDeclarantData(): \Generator
    {
        yield 'Locataire' => ['00000000-0000-0000-2023-locataire001', false, 'locataire-01@signal-logement.fr'];
        yield 'Bailleur occupant' => ['00000000-0000-0000-2023-bailleuroc01', false, 'bailleur_occupant-01@signal-logement.fr'];
        yield 'Tiers particulier' => ['00000000-0000-0000-2023-tierspart001', true, 'tiers_particulier-01@signal-logement.fr'];
    }

    /**
     * @dataProvider provideIsPublicData
     */
    public function testIsPublicAndBailleurPrevenuPeriodPassed(string $draftUuid, bool $returnValue): void
    {
        /** @var SignalementDraftHelper $signalementDraftHelper */
        $signalementDraftHelper = static::getContainer()->get(SignalementDraftHelper::class);
        /** @var SignalementDraftRepository $signalementDraftRepository */
        $signalementDraftRepository = static::getContainer()->get(SignalementDraftRepository::class);
        /** @var SignalementDraft $signalementDraft */
        $signalementDraft = $signalementDraftRepository->findOneBy(['uuid' => $draftUuid]);

        $this->assertEquals($signalementDraftHelper->isPublicAndBailleurPrevenuPeriodPassed($signalementDraft), $returnValue);
    }

    public function provideIsPublicData(): \Generator
    {
        yield 'Locataire' => ['00000000-0000-0000-2023-locataire001', true];
        yield 'Bailleur occupant' => ['00000000-0000-0000-2023-bailleuroc01', false];
        yield 'Tiers particulier' => ['00000000-0000-0000-2023-tierspart001', false];
    }

    public function testComputePrevenuBailleurAtWithValidMonthYear(): void
    {
        $result = SignalementDraftHelper::computePrevenuBailleurAt('11/2024');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('2024-11-01', $result->format('Y-m-d'));
        $this->assertSame('00:00:00', $result->format('H:i:s'));
    }

    public function testComputePrevenuBailleurAtWithInvalidFormat(): void
    {
        $result = SignalementDraftHelper::computePrevenuBailleurAt('2024-11');

        $this->assertNull($result);
    }

    public function testComputePrevenuBailleurAtWithNonDateString(): void
    {
        $result = SignalementDraftHelper::computePrevenuBailleurAt('');

        $this->assertNull($result);
    }
}
