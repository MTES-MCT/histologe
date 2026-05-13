<?php

namespace App\Tests\Unit\Service\Mailer;

use App\Service\Mailer\ContextDateTimeNormalizer;
use PHPUnit\Framework\TestCase;

class ContextDateTimeNormalizerTest extends TestCase
{
    private ContextDateTimeNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ContextDateTimeNormalizer();
    }

    public function testNormalizeSimpleDateTime(): void
    {
        $context = [
            'date' => [
                'date' => '2026-05-19 12:00:00.000000',
                'timezone' => 'UTC',
                'timezone_type' => 3,
            ],
        ];

        $result = $this->normalizer->normalize($context);

        $this->assertInstanceOf(\DateTime::class, $result['date']);
        $this->assertEquals('2026-05-19 12:00:00', $result['date']->format('Y-m-d H:i:s'));
        $this->assertEquals('UTC', $result['date']->getTimezone()->getName());
    }

    public function testNormalizeNestedDateTime(): void
    {
        $context = [
            'params' => [
                'date' => [
                    'date' => '2026-05-19 12:00:00.000000',
                    'timezone' => 'Europe/Paris',
                    'timezone_type' => 3,
                ],
                'name' => 'Test Event',
            ],
        ];

        $result = $this->normalizer->normalize($context);

        $this->assertIsArray($result['params']);
        $this->assertInstanceOf(\DateTime::class, $result['params']['date']);
        $this->assertEquals('2026-05-19 12:00:00', $result['params']['date']->format('Y-m-d H:i:s'));
        $this->assertEquals('Europe/Paris', $result['params']['date']->getTimezone()->getName());
        $this->assertEquals('Test Event', $result['params']['name']);
    }

    public function testNormalizeMultipleDateTimes(): void
    {
        $context = [
            'date' => [
                'date' => '2026-05-19 12:00:00.000000',
                'timezone' => 'UTC',
                'timezone_type' => 3,
            ],
            'params' => [
                'date' => [
                    'date' => '2026-05-20 14:30:00.000000',
                    'timezone' => 'Europe/Paris',
                    'timezone_type' => 3,
                ],
            ],
        ];

        $result = $this->normalizer->normalize($context);

        $this->assertInstanceOf(\DateTime::class, $result['date']);
        $this->assertEquals('2026-05-19 12:00:00', $result['date']->format('Y-m-d H:i:s'));

        $this->assertInstanceOf(\DateTime::class, $result['params']['date']);
        $this->assertEquals('2026-05-20 14:30:00', $result['params']['date']->format('Y-m-d H:i:s'));
    }

    public function testNormalizeWithNonDateTimeArrays(): void
    {
        $context = [
            'name' => 'Test Event',
            'url' => 'https://example.com',
            'tags' => ['tag1', 'tag2'],
            'metadata' => [
                'type' => 'club_event',
                'priority' => 'high',
            ],
        ];

        $result = $this->normalizer->normalize($context);

        $this->assertEquals($context, $result);
    }

    public function testNormalizeMixedContext(): void
    {
        $context = [
            'name' => 'Club Event',
            'date' => [
                'date' => '2026-05-19 12:00:00.000000',
                'timezone' => 'UTC',
                'timezone_type' => 3,
            ],
            'url' => 'https://example.com',
            'params' => [
                'timezone' => 'Europe/Paris',
                'date' => [
                    'date' => '2026-05-19 12:00:00.000000',
                    'timezone' => 'UTC',
                    'timezone_type' => 3,
                ],
            ],
        ];

        $result = $this->normalizer->normalize($context);

        $this->assertEquals('Club Event', $result['name']);
        $this->assertEquals('https://example.com', $result['url']);
        $this->assertInstanceOf(\DateTime::class, $result['date']);
        $this->assertInstanceOf(\DateTime::class, $result['params']['date']);
        $this->assertEquals('Europe/Paris', $result['params']['timezone']);
    }

    public function testNormalizeEmptyArray(): void
    {
        $context = [];

        $result = $this->normalizer->normalize($context);

        $this->assertEquals([], $result);
    }

    public function testNormalizeInvalidDateTime(): void
    {
        $context = [
            'date' => [
                'date' => 'invalid-date-format',
                'timezone' => 'Invalid/Timezone',
                'timezone_type' => 3,
            ],
            'name' => 'Test',
        ];

        $result = $this->normalizer->normalize($context);

        // Should keep the original array structure if conversion fails
        $this->assertIsArray($result['date']);
        $this->assertEquals('Test', $result['name']);
    }

    public function testNormalizeRealClubEventContext(): void
    {
        // Real-world example from the failed_email table
        $context = [
            'raw' => false,
            'url' => 'https://app.livestorm.co/mte/club-partenaires-communes',
            'date' => [
                'date' => '2026-05-19 12:00:00.000000',
                'timezone' => 'UTC',
                'timezone_type' => 3,
            ],
            'name' => 'Club Partenaires - Communes/SCHS/EPCI/CCAS',
            'params' => [
                'url' => 'https://app.livestorm.co/mte/club-partenaires-communes',
                'date' => [
                    'date' => '2026-05-19 12:00:00.000000',
                    'timezone' => 'UTC',
                    'timezone_type' => 3,
                ],
                'name' => 'Club Partenaires - Communes/SCHS/EPCI/CCAS',
                'timezone' => 'Europe/Paris',
            ],
            'template' => 'club_event_email',
            'timezone' => 'Europe/Paris',
            'tagHeader' => 'Pro Club Event',
        ];

        $result = $this->normalizer->normalize($context);

        $this->assertInstanceOf(\DateTime::class, $result['date']);
        $this->assertInstanceOf(\DateTime::class, $result['params']['date']);
        $this->assertEquals('Club Partenaires - Communes/SCHS/EPCI/CCAS', $result['name']);
        $this->assertEquals('Europe/Paris', $result['timezone']);
        $this->assertEquals('club_event_email', $result['template']);
    }
}
