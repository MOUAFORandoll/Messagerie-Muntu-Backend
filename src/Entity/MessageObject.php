<?php

namespace App\Entity;

use App\Traits\SoftCreateUpdateDeleteTrait;


use App\Repository\MessageObjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageObjectRepository::class)]
class MessageObject
{
    use SoftCreateUpdateDeleteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 2055)]

    private ?string $src = null;

    #[ORM\ManyToOne(inversedBy: 'messageObjects')]
    private ?TypeObject $typeObject = null;

    #[ORM\ManyToOne(inversedBy: 'messageObjects')]
    private ?MessageUser $message_user = null;

    #[ORM\ManyToOne(inversedBy: 'messageObjects')]
    private ?Message $message = null;



    public function __construct() {}

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
    public function getTypeObject(): ?TypeObject
    {
        return $this->typeObject;
    }

    public function setTypeObject(?TypeObject $typeObject): static
    {
        $this->typeObject = $typeObject;

        return $this;
    }

    public function getMessageUser(): ?MessageUser
    {
        return $this->message_user;
    }

    public function setMessageUser(?MessageUser $message_user): static
    {
        $this->message_user = $message_user;

        return $this;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): static
    {
        $this->message = $message;

        return $this;
    }
}
