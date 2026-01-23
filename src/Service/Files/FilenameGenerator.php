<?php

namespace App\Service\Files;

use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FilenameGenerator
{
    private ?string $title = null;

    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly ClockInterface $clock,
    ) {
    }

    public function generate(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $this->title = $originalFilename.'.'.$file->guessExtension();
        $safeFilename = $this->slugger->slug($originalFilename);
        $prefix = $this->prefixForNow();

        return sprintf(
            '%s/%s-%s.%s',
            $prefix,
            $safeFilename,
            uniqid(),
            $file->guessExtension()
        );
    }

    public function prefixForNow(): string
    {
        return $this->clock->now()->format('Y/m');
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
