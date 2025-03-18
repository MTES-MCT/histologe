<?php

namespace App\Service\Signalement;

use App\Dto\Api\Request\RequestFileInterface;
use App\Entity\File;
use App\Entity\Signalement;
use App\Service\Sanitizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DescriptionFilesBuilder
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function build(Signalement $signalement, RequestFileInterface $suiviRequest): string
    {
        $fileListAsHtml = '';
        $description = Sanitizer::sanitize($suiviRequest->getDescription());
        $filesFiltered = $signalement->getFiles()->filter(function (File $file) use ($suiviRequest) {
            return in_array($file->getUuid(), $suiviRequest->getFiles(), true);
        });

        if ($filesFiltered->count() > 0) {
            $fileListAsHtml = '<ul>';
            /** @var File $file */
            foreach ($filesFiltered as $file) {
                $fileUrl = $this->urlGenerator->generate(
                    'show_file',
                    ['uuid' => $file->getUuid()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $fileListAsHtml .= sprintf(
                    "<li><a class='fr-link' target='_blank' rel='noopener' href='%s'>%s</a>",
                    $fileUrl,
                    $file->getTitle()
                );
            }
            $fileListAsHtml .= '</ul>';
        }

        return $description.$fileListAsHtml;
    }
}
