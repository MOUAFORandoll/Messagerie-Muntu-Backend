<?php

namespace App\Entity;

use App\Traits\SoftCreateUpdateDeleteTrait;


use App\Repository\UserObjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserObjectRepository::class)]
class UserObject
{
    use SoftCreateUpdateDeleteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $src = null;

    #[ORM\ManyToOne(inversedBy: 'userObjects')]
    private ?User $user_plateform = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSrc(): ?string
    {
        return $this->src;
    }

    public function setSrc(string $src): static
    {
        $this->src = $src;

        return $this;
    }


    public function getUser(): ?User
    {
        return $this->user_plateform;
    }

    public function setUser(?User $user_plateform): static
    {
        $this->user_plateform = $user_plateform;

        return $this;
    }
}
