<?php

namespace App\Tests\Unit\Service\Signalement\Export;

use App\Dto\SignalementExport;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Service\Signalement\Export\SignalementExportHeader;
use App\Service\Signalement\Export\SignalementExportLoader;
use App\Tests\UserHelper;
use PHPUnit\Framework\TestCase;

class SignalementExportLoaderTest extends TestCase
{
    use UserHelper;

    public function testLoad()
    {
        $signalementManager = $this->createMock(SignalementManager::class);
        $filters = ['foo' => 'bar'];
        $user = $this->getUserFromRole(User::ROLE_ADMIN);

        $signalementExports = [
            new SignalementExport('2023-01', '31-03-2023', 'nouveau'),
            new SignalementExport('2023-02', '31-03-2023', 'nouveau'),
        ];

        $signalementManager->expects($this->once())
            ->method('findSignalementAffectationIterable')
            ->with($user, $filters)
            ->willReturn($this->getSignalementExportGenerator($signalementExports));

        $expectedOutput = $this->getHeaderAsString()."2023-01;31-03-2023;nouveau;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;\n2023-02;31-03-2023;nouveau;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;\n";

        $loader = new SignalementExportLoader($signalementManager);
        ob_start();
        $loader->load($user, $filters);
        $output = ob_get_clean();
        $this->assertEquals($expectedOutput, $output);
    }

    private function getSignalementExportGenerator(array $signalements): \Generator
    {
        foreach ($signalements as $signalement) {
            yield $signalement;
        }
    }

    private function getHeaderAsString(): string
    {
        $headers = SignalementExportHeader::getHeaders();
        $headers = array_map(function ($header) {
            if (str_contains($header, ' ')) {
                $header = str_replace('"', '""', $header);
                $header = '"'.$header.'"';
            }

            return $header;
        }, $headers);

        return implode(SignalementExportHeader::SEPARATOR, $headers)."\n";
    }
}
