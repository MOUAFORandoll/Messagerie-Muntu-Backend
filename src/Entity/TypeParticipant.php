<?php

namespace App\Entity;

use App\Traits\SoftCreateUpdateDeleteTrait;

use App\Repository\TypeParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeParticipantRepository::class)]
class TypeParticipant
{
    use SoftCreateUpdateDeleteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    /**
     * @var Collection<int, GroupeUser>
     */
    #[ORM\OneToMany(targetEntity: GroupeUser::class, mappedBy: 'typeUser')]
    private Collection $groupeUsers;

    /**
     * @var Collection<int, CanalUser>
     */
    #[ORM\OneToMany(targetEntity: CanalUser::class, mappedBy: 'typeUser')]
    private Collection $canalUsers;

    public function __construct()
    {
        $this->groupeUsers = new ArrayCollection();
        $this->canalUsers = new ArrayCollection();
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
     * @return Collection<int, GroupeUser>
     */
    public function getGroupeUsers(): Collection
    {
        return $this->groupeUsers;
    }

    public function addGroupeUser(GroupeUser $groupeUser): static
    {
        if (!$this->groupeUsers->contains($groupeUser)) {
            $this->groupeUsers->add($groupeUser);
            $groupeUser->setTypeParticipant($this);
        }

        return $this;
    }

    public function removeGroupeUser(GroupeUser $groupeUser): static
    {
        if ($this->groupeUsers->removeElement($groupeUser)) {
            // set the owning side to null (unless already changed)
            if ($groupeUser->getTypeParticipant() === $this) {
                $groupeUser->setTypeParticipant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CanalUser>
     */
    public function getCanalUsers(): Collection
    {
        return $this->canalUsers;
    }

    public function addCanalUser(CanalUser $canalUser): static
    {
        if (!$this->canalUsers->contains($canalUser)) {
            $this->canalUsers->add($canalUser);
            $canalUser->setTypeParticipant($this);
        }

        return $this;
    }

    public function removeCanalUser(CanalUser $canalUser): static
    {
        if ($this->canalUsers->removeElement($canalUser)) {
            // set the owning side to null (unless already changed)
            if ($canalUser->getTypeParticipant() === $this) {
                $canalUser->setTypeParticipant(null);
            }
        }

        return $this;
    }
}
