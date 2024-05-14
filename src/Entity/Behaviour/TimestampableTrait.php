<?php

namespace App\Entity\Behaviour;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

trait TimestampableTrait
{
    #[ORM\Column(nullable: true)]
    #[Ignore]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Ignore]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist()]
    #[Ignore]
    public function setCreatedAtValue(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate()]
    #[Ignore]
    public function setUpdatedAtValue(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
