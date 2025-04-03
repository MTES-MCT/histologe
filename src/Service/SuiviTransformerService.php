<?php

namespace App\Service;

use CoopTilleuls\UrlSignerBundle\UrlSigner\UrlSignerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SuiviTransformerService
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private readonly UrlSignerInterface $urlSigner,
    ) {
    }

    public function transformDescription(?string $description): string
    {
        if (!$description) {
            return '';
        }
        preg_match_all('/href="([^"]+)"/', $description, $matches);
        foreach ($matches[1] as $match) {
            if (str_contains($match, '/show/')) {
                $exploded = explode('/show/', $match);
                $uuid = $exploded[count($exploded) - 1];
                $url = $this->urlGenerator->generate('show_file', ['uuid' => $uuid], UrlGeneratorInterface::ABSOLUTE_URL);
                $url = $this->urlSigner->sign($url); // @phpstan-ignore-line
                $description = str_replace($match, $url, $description);
            }
        }

        return $description;
    }
}
