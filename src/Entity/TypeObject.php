<?php

namespace App\Entity;

use App\Traits\SoftCreateUpdateDeleteTrait;

use App\Repository\TypeObjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeObjectRepository::class)]
class TypeObject
{
    use SoftCreateUpdateDeleteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    /**
     * @var Collection<int, MessageObject>
     */
    #[ORM\OneToMany(targetEntity: MessageObject::class, mappedBy: 'typeObject')]
    private Collection $messageObjects;

    public function __construct()
    {
        $this->messageObjects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

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
            $messageObject->setTypeObject($this);
        }

        return $this;
    }

    public function removeMessageObject(MessageObject $messageObject): static
    {
        if ($this->messageObjects->removeElement($messageObject)) {
            // set the owning side to null (unless already changed)
            if ($messageObject->getTypeObject() === $this) {
                $messageObject->setTypeObject(null);
            }
        }

        return $this;
    }
}
