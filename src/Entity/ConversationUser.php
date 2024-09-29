<?php

namespace App\Entity;

use App\Traits\SoftCreateUpdateDeleteTrait;

use App\Repository\ConversationUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationUserRepository::class)]
class ConversationUser
{
    use SoftCreateUpdateDeleteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\ManyToOne(inversedBy: 'conversationUsers')]
    private ?User $first = null;

    #[ORM\ManyToOne(inversedBy: 'conversationUsers')]
    private ?User $second = null;

    /**
     * @var Collection<int, MessageUser>
     */
    #[ORM\OneToMany(targetEntity: MessageUser::class, mappedBy: 'conversation')]
    private Collection $messageUsers;

    public function __construct()
    {
        $this->messageUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getFirst(): ?User
    {
        return $this->first;
    }

    public function setFirst(?User $first): static
    {
        $this->first = $first;

        return $this;
    }

    public function getSecond(): ?User
    {
        return $this->second;
    }

    public function setSecond(?User $second): static
    {
        $this->second = $second;

        return $this;
    }

    /**
     * @return Collection<int, MessageUser>
     */
    public function getMessageUsers(): Collection
    {
        return $this->messageUsers;
    }

    public function addMessageUser(MessageUser $messageUser): static
    {
        if (!$this->messageUsers->contains($messageUser)) {
            $this->messageUsers->add($messageUser);
            $messageUser->setConversation($this);
        }

        return $this;
    }

    public function removeMessageUser(MessageUser $messageUser): static
    {
        if ($this->messageUsers->removeElement($messageUser)) {
            // set the owning side to null (unless already changed)
            if ($messageUser->getConversation() === $this) {
                $messageUser->setConversation(null);
            }
        }

        return $this;
    }
}
