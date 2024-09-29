<?php

namespace App\Entity;

use App\Traits\SoftCreateUpdateDeleteTrait;

use App\Repository\GroupeUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupeUserRepository::class)]
class GroupeUser
{
    use SoftCreateUpdateDeleteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'groupeUsers')]
    private ?Groupe $groupe = null;

    #[ORM\ManyToOne(inversedBy: 'groupeUsers')]
    private ?User $muntu = null;

    #[ORM\ManyToOne(inversedBy: 'groupeUsers')]
    private ?TypeParticipant $typeUser = null;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'emetteurGroupe')]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    public function setGroupe(?Groupe $groupe): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    public function getMuntu(): ?User
    {
        return $this->muntu;
    }

    public function setMuntu(?User $muntu): static
    {
        $this->muntu = $muntu;

        return $this;
    }

    public function getTypeParticipant(): ?TypeParticipant
    {
        return $this->typeUser;
    }

    public function setTypeParticipant(?TypeParticipant $typeUser): static
    {
        $this->typeUser = $typeUser;

        return $this;
    }


    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setEmetteurGroupe($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getEmetteurGroupe() === $this) {
                $message->setEmetteurGroupe(null);
            }
        }

        return $this;
    }
}
