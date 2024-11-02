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

    #[ORM\ManyToOne(inversedBy: 'follows')]
    private ?User $follower = null;

    #[ORM\ManyToOne(inversedBy: 'follows')]
    private ?User $following = null;

    #[ORM\Column(length: 255)]
    private ?string $nameContact = null;

    #[ORM\Column(length: 255)]
    private ?string $sunameContact = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFollower(): ?User
    {
        return $this->follower;
    }

    public function setFollower(?User $follower): static
    {
        $this->follower = $follower;

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

    public function getNameContact(): ?string
    {
        return $this->nameContact;
    }

    public function setNameContact(string $nameContact): static
    {
        $this->nameContact = $nameContact;

        return $this;
    }

    public function getSunameContact(): ?string
    {
        return $this->sunameContact;
    }

    public function setSunameContact(string $sunameContact): static
    {
        $this->sunameContact = $sunameContact;

        return $this;
    }
}
