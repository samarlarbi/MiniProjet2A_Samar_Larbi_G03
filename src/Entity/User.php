<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $password = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: WebauthnCredential::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $webauthnCredentials;

    public function __construct(?string $email = null)
    {
        $this->webauthnCredentials = new ArrayCollection();
        if ($email) {
            $this->email = $email;
        }
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getWebauthnCredentials(): Collection
    {
        return $this->webauthnCredentials;
    }

    public function addWebauthnCredential(WebauthnCredential $webauthnCredential): self
    {
        if (!$this->webauthnCredentials->contains($webauthnCredential)) {
            $this->webauthnCredentials->add($webauthnCredential);
            $webauthnCredential->setUser($this);
        }
        return $this;
    }

    public function removeWebauthnCredential(WebauthnCredential $webauthnCredential): self
    {
        if ($this->webauthnCredentials->removeElement($webauthnCredential)) {
            if ($webauthnCredential->getUser() === $this) {
                $webauthnCredential->setUser(null);
            }
        }
        return $this;
    }
}
