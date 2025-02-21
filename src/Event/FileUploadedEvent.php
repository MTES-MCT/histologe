<?php

namespace App\Event;

use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class FileUploadedEvent extends Event
{
    public const string NAME = 'file.uploaded';

    public array $filesPushed = [];

    public function __construct(
        private readonly Signalement $signalement,
        private readonly User $user,
        private readonly array $files,
    ) {
    }

    public function getSignalement(): Signalement
    {
        return $this->signalement;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getFilesPushed(): array
    {
        return $this->filesPushed;
    }

    public function setFilesPushed(array $filesPushed): self
    {
        $this->filesPushed = $filesPushed;

        return $this;
    }
}
