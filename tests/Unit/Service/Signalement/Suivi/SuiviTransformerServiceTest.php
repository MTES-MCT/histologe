<?php

namespace App\Tests\Unit\Service\Signalement\Suivi;

use App\Entity\Enum\DocumentType;
use App\Entity\Enum\SuiviCategory;
use App\Entity\File;
use App\Entity\Suivi;
use App\Entity\SuiviFile;
use App\Repository\DesordreCritereRepository;
use App\Service\Files\FileDocumentTypeLabelResolver;
use App\Service\Signalement\Suivi\SuiviDescriptionHelper;
use App\Service\Signalement\Suivi\SuiviRecipient;
use App\Service\Signalement\Suivi\SuiviTransformerService;
use CoopTilleuls\UrlSignerBundle\UrlSigner\UrlSignerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class SuiviTransformerServiceTest extends TestCase
{
    public function testEscapesTitlesOfAttachedAndDeletedFiles(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://example.test/file');
        $urlSigner = $this->createStub(UrlSignerInterface::class);
        $urlSigner->method('sign')->willReturn('https://example.test/file');
        $descriptionHelper = $this->createStub(SuiviDescriptionHelper::class);
        $descriptionHelper->method('getDisplayDescription')->willReturn(null);
        $labelResolver = new FileDocumentTypeLabelResolver(
            $this->createStub(DesordreCritereRepository::class),
            $this->createStub(LoggerInterface::class),
        );
        $twig = new Environment(new FilesystemLoader(\dirname(__DIR__, 5).'/templates'));
        $twig->addFunction(new TwigFunction('url', static fn (): string => 'https://example.test/file'));
        $twig->addFunction(new TwigFunction('sign_url', static fn (string $url): string => $url));
        $twig->addFunction(new TwigFunction('file_document_type_label', [$labelResolver, 'getLabel']));

        $service = new SuiviTransformerService(
            $urlGenerator,
            $urlSigner,
            $descriptionHelper,
            $twig,
        );
        $title = '<img src=x onerror=alert(1)> & "été".png';
        $suivi = (new Suivi())->setDescription('Message')->setCategory(SuiviCategory::MESSAGE_PARTNER);
        $suivi->addSuiviFile((new SuiviFile())->setTitle($title)->setFile((new File())->setDocumentType(DocumentType::AUTRE)));
        $suivi->addSuiviFile((new SuiviFile())->setTitle($title));

        $description = $service->transformDescription($suivi, true, SuiviRecipient::DEFAULT);
        $escapedTitle = '&lt;img src=x onerror=alert(1)&gt; &amp; &quot;été&quot;.png';

        self::assertStringContainsString('href="https://example.test/file">'.$escapedTitle.'</a>', $description);
        self::assertStringContainsString('Fichier supprimé ('.$escapedTitle.')', $description);
        self::assertStringNotContainsString($title, $description);
    }
}
