<?php

namespace App\Entity;

use App\Traits\SoftCreateUpdateDeleteTrait;

use App\Repository\MessageUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageUserRepository::class)]
class MessageUser
{
    use SoftCreateUpdateDeleteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $valeur = null;

    #[ORM\ManyToOne(inversedBy: 'messageUsers')]
    private ?ConversationUser $conversation = null;
    /**
     * 0 = > envoye
     * 1 => recu
     * 2 => lu
     */
    #[ORM\Column]
    private ?int $status = null;

     

    #[ORM\ManyToOne(inversedBy: 'messageUsers')]
    private ?User $emetteur = null;

    #[ORM\OneToMany(mappedBy: 'message_user', targetEntity: MessageObject::class)]
    private Collection $messageObjects;

    public function __construct()
    {
        $this->messageObjects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): static
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getConversation(): ?ConversationUser
    {
        return $this->conversation;
    }

    public function setConversation(?ConversationUser $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }
 
    public function getEmetteur(): ?User
    {
        return $this->emetteur;
    }

    public function setEmetteur(?User $emetteur): static
    {
        $this->emetteur = $emetteur;

        return $this;
    }

    /**
     * @return Collection<int, MessageObject>
     */
    public function getMessageObjects(): Collection
    {
        return $this->messageObjects;
    }

    public function addMessageObject(MessageObject $messageObject): static
    {
        if (!$this->messageObjects->contains($messageObject)) {
            $this->messageObjects->add($messageObject);
            $messageObject->setMessageUser($this);
        }

        return $this;
    }

    public function removeMessageObject(MessageObject $messageObject): static
    {
        if ($this->messageObjects->removeElement($messageObject)) {
            // set the owning side to null (unless already changed)
            if ($messageObject->getMessageUser() === $this) {
                $messageObject->setMessageUser(null);
            }
        }

        return $this;
    }
}