<?php

namespace App\Event;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class FileUploadedEvent extends Event
{
    public const string NAME = 'file.uploaded';

    /**
     * @var array<string>
     */
    public array $filesPushed = [];

    /**
     * @param array<mixed> $files
     */
    public function __construct(
        private readonly Signalement $signalement,
        private readonly User $user,
        private readonly array $files,
        private readonly Partner $partner,
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

    /**
     * @return array<mixed>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @return array<mixed>
     */
    public function getFilesPushed(): array
    {
        return $this->filesPushed;
    }

    /**
     * @param array<mixed> $filesPushed
     */
    public function setFilesPushed(array $filesPushed): self
    {
        $this->filesPushed = $filesPushed;

        return $this;
    }

    public function getPartner(): Partner
    {
        return $this->partner;
    }
}
