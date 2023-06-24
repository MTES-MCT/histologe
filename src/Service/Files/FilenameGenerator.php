<?php

namespace App\Service\Files;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FilenameGenerator
{
    private ?string $title = null;

    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    public function generate(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $this->title = $originalFilename.'.'.$file->guessExtension();
        $safeFilename = $this->slugger->slug($originalFilename);

        return $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function generateSafeName(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);

        return $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
    }
}
