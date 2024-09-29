<?php
// src/EventListener/SoftDeleteListener.php
namespace App\Traits; // SoftDeletableTrait.php

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait SoftCreateUpdateDeleteTrait
{
    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $deletedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt ?? new \DateTime();
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(): self
    {
        $this->deletedAt = new \DateTime();
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(): self
    {
        $this->updatedAt = new \DateTime();
        return $this;
    }
}
