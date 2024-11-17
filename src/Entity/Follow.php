<?php

namespace App\Entity;

use App\Repository\FollowRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FollowRepository::class)]
class Follow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 255)]
    private ?string $nameContact = null;

    #[ORM\Column(length: 255)]
    private ?string $surnameContact = null;

    #[ORM\ManyToOne(inversedBy: 'follows')]
    private ?User $following = null;

    #[ORM\ManyToOne(inversedBy: 'followCurrentUsers')]
    private ?User $currentUser = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameContact(): ?string
    {
        return $this->nameContact;
    }

    public function setNameContact(string $nameContact): static
    {
        $this->nameContact = $nameContact;

        return $this;
    }

    public function getSurnameContact(): ?string
    {
        return $this->surnameContact;
    }

    public function setSurnameContact(string $surnameContact): static
    {
        $this->surnameContact = $surnameContact;

        return $this;
    }

    public function getFollowing(): ?User
    {
        return $this->following;
    }

    public function setFollowing(?User $following): static
    {
        $this->following = $following;

        return $this;
    }

    public function getCurrentUser(): ?User
    {
        return $this->currentUser;
    }

    public function setCurrentUser(?User $currentUser): static
    {
        $this->currentUser = $currentUser;

        return $this;
    }
}
