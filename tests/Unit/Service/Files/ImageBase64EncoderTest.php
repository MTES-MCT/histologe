<?php

namespace App\Tests\Unit\Service\Files;

use App\Service\Files\ImageBase64Encoder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageBase64EncoderTest extends TestCase
{
    public function testEncodeLogsAnExceptionAndReturnsNullWhenImageCannotBeRead(): void
    {
        $bucketPath = sys_get_temp_dir().'/image_base64_encoder_test_'.uniqid();
        $filename = 'missing.png';

        $parameterBag = $this->createStub(ParameterBagInterface::class);
        $parameterBag
            ->method('get')
            ->willReturn($bucketPath);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(sprintf('Impossible de lire le fichier image "%s/%s".', $bucketPath, $filename));

        $encoder = new ImageBase64Encoder($parameterBag, $logger);

        $this->assertNull($encoder->encode($filename));
    }
}
