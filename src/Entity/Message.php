<?php

namespace App\Entity;

use App\Traits\SoftCreateUpdateDeleteTrait;

use App\Repository\MessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    use SoftCreateUpdateDeleteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $valeur = null;

    /**
     * 0 = > envoye
     * 1 => recu
     * 2 => lu
     */
    #[ORM\Column]
    private ?int $status = null;


    #[ORM\ManyToOne(inversedBy: 'messages')]
    private ?MessageObject $messageObject = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    private ?GroupeUser $emetteurGroupe = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    private ?CanalUser $emetteurCanal = null;

    #[ORM\OneToMany(mappedBy: 'message', targetEntity: MessageObject::class)]
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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }


    public function getMessageObject(): ?MessageObject
    {
        return $this->messageObject;
    }

    public function setMessageObject(?MessageObject $messageObject): static
    {
        $this->messageObject = $messageObject;

        return $this;
    }

    public function getEmetteurGroupe(): ?GroupeUser
    {
        return $this->emetteurGroupe;
    }

    public function setEmetteurGroupe(?GroupeUser $emetteurGroupe): static
    {
        $this->emetteurGroupe = $emetteurGroupe;

        return $this;
    }

    public function getEmetteurCanal(): ?CanalUser
    {
        return $this->emetteurCanal;
    }

    public function setEmetteurCanal(?CanalUser $emetteurCanal): static
    {
        $this->emetteurCanal = $emetteurCanal;

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
            $messageObject->setMessage($this);
        }

        return $this;
    }

    public function removeMessageObject(MessageObject $messageObject): static
    {
        if ($this->messageObjects->removeElement($messageObject)) {
            // set the owning side to null (unless already changed)
            if ($messageObject->getMessage() === $this) {
                $messageObject->setMessage(null);
            }
        }

        return $this;
    }
}
