<?php

namespace App\Entity;

use App\Traits\SoftCreateUpdateDeleteTrait;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements
    UserInterface,
    PasswordAuthenticatedUserInterface
{
    use SoftCreateUpdateDeleteTrait;



    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true, nullable: false)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: "json")]
    private $roles = ['ROLE_USER'];
    /**
     * @var Collection<int, GroupeUser>
     */
    #[ORM\OneToMany(targetEntity: GroupeUser::class, mappedBy: 'muntu')]
    private Collection $groupeUsers;

    /**
     * @var Collection<int, ConversationUser>
     */
    #[ORM\OneToMany(targetEntity: ConversationUser::class, mappedBy: 'emtteur')]
    private Collection $conversationUsers;

    /**
     * @var Collection<int, CanalUser>
     */
    #[ORM\OneToMany(targetEntity: CanalUser::class, mappedBy: 'muntu')]
    private Collection $canalUsers;



    #[ORM\OneToMany(mappedBy: 'user_plateform', targetEntity: UserObject::class)]
    private Collection $userObjects;

    #[ORM\OneToMany(mappedBy: 'emetteur', targetEntity: MessageUser::class)]
    private Collection $messageUsers;

    #[ORM\OneToMany(mappedBy: 'follower', targetEntity: Follow::class)]
    private Collection $follows;

     

    public function __construct()
    {
        $this->userObjects = new ArrayCollection();
        $this->groupeUsers = new ArrayCollection();
        $this->conversationUsers = new ArrayCollection();
        $this->canalUsers = new ArrayCollection();
        $this->messageUsers = new ArrayCollection();
        $this->follows = new ArrayCollection(); 
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }
    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function   getUserIdentifier(): string
    {
        return (string) $this->username;
    }


    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }
    /**
     * @return Collection<int, UserObject>
     */
    public function getUserObjects(): Collection
    {
        return $this->userObjects;
    }

    public function addUserObject(UserObject $userObject): static
    {
        if (!$this->userObjects->contains($userObject)) {
            $this->userObjects->add($userObject);
            $userObject->setUser($this);
        }

        return $this;
    }

    public function removeUserObject(UserObject $userObject): static
    {
        if ($this->userObjects->removeElement($userObject)) {
            // set the owning side to null (unless already changed)
            if ($userObject->getUser() === $this) {
                $userObject->setUser(null);
            }
        }

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
            $groupeUser->setMuntu($this);
        }

        return $this;
    }

    public function removeGroupeUser(GroupeUser $groupeUser): static
    {
        if ($this->groupeUsers->removeElement($groupeUser)) {
            // set the owning side to null (unless already changed)
            if ($groupeUser->getMuntu() === $this) {
                $groupeUser->setMuntu(null);
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
            $canalUser->setMuntu($this);
        }

        return $this;
    }

    public function removeCanalUser(CanalUser $canalUser): static
    {
        if ($this->canalUsers->removeElement($canalUser)) {
            // set the owning side to null (unless already changed)
            if ($canalUser->getMuntu() === $this) {
                $canalUser->setMuntu(null);
            }
        }

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
            $messageUser->setEmetteur($this);
        }

        return $this;
    }

    public function removeMessageUser(MessageUser $messageUser): static
    {
        if ($this->messageUsers->removeElement($messageUser)) {
            // set the owning side to null (unless already changed)
            if ($messageUser->getEmetteur() === $this) {
                $messageUser->setEmetteur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Follow>
     */
    public function getFollows(): Collection
    {
        return $this->follows;
    }

    public function addFollow(Follow $follow): static
    {
        if (!$this->follows->contains($follow)) {
            $this->follows->add($follow);
            $follow->setFollower($this);
        }

        return $this;
    }

    public function removeFollow(Follow $follow): static
    {
        if ($this->follows->removeElement($follow)) {
            // set the owning side to null (unless already changed)
            if ($follow->getFollower() === $this) {
                $follow->setFollower(null);
            }
        }

        return $this;
    }
 
}
