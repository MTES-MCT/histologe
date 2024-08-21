<?php

namespace App\Tests\Unit\Dto\Request\Signalement;

use App\Dto\Request\Signalement\VisiteRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VisiteRequestTest extends KernelTestCase
{
    /**
     * @dataProvider visiteRequestDataProvider
     *
     * @throws \Exception
     */
    public function testDatesVisiteRequestDTO(
        string $date,
        string $time,
        string $timezone,
        string $idPartner,
        string $expectedLocale,
        string $expectedUTC): void
    {
        self::bootKernel();

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');

        $visiteRequest = new VisiteRequest(
            date: $date,
            time: $time,
            timezone: $timezone,
            idPartner: $idPartner,
        );

        $validationResult = $validator->validate($visiteRequest);
        $this->assertCount(0, $validationResult);

        $this->assertEquals($expectedLocale, $visiteRequest->getDateTimeLocale());
        $this->assertEquals($expectedUTC, $visiteRequest->getDateTimeUTC());
    }

    public function visiteRequestDataProvider(): \Generator
    {
        yield 'France' => [
            'date' => '2024-08-13',
            'time' => '12:00',
            'timezone' => 'Europe/Paris',
            'idPartner' => '1',
            'expectedLocale' => '2024-08-13 12:00',
            'expectedUTC' => '2024-08-13 10:00',
        ];

        yield 'Martinique' => [
            'date' => '2024-08-13',
            'time' => '12:00',
            'timezone' => 'America/Martinique',
            'idPartner' => '2',
            'expectedLocale' => '2024-08-13 12:00',
            'expectedUTC' => '2024-08-13 16:00',
        ];
    }
}
