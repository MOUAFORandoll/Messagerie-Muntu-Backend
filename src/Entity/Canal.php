<?php

namespace App\Entity;

use App\Traits\SoftCreateUpdateDeleteTrait;

use App\Repository\CanalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CanalRepository::class)]
class Canal
{
    use SoftCreateUpdateDeleteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    /**
     * @var Collection<int, CanalUser>
     */
    #[ORM\OneToMany(targetEntity: CanalUser::class, mappedBy: 'canal')]
    private Collection $canalUsers;

    public function __construct()
    {
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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
            $canalUser->setCanal($this);
        }

        return $this;
    }

    public function removeCanalUser(CanalUser $canalUser): static
    {
        if ($this->canalUsers->removeElement($canalUser)) {
            // set the owning side to null (unless already changed)
            if ($canalUser->getCanal() === $this) {
                $canalUser->setCanal(null);
            }
        }

        return $this;
    }
}
