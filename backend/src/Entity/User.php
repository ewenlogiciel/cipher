<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $twoFactorSecret = null;

    #[ORM\Column]
    private ?bool $twoFactorEnabled = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Vault>
     */
    #[ORM\OneToMany(targetEntity: Vault::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $ownedVaults;

    /**
     * @var Collection<int, VaultMember>
     */
    #[ORM\OneToMany(targetEntity: VaultMember::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $vaultMembers;

    public function __construct()
    {
        $this->ownedVaults = new ArrayCollection();
        $this->vaultMembers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
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
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
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
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function setTwoFactorSecret(?string $twoFactorSecret): static
    {
        $this->twoFactorSecret = $twoFactorSecret;

        return $this;
    }

    public function isTwoFactorEnabled(): ?bool
    {
        return $this->twoFactorEnabled;
    }

    public function setTwoFactorEnabled(bool $twoFactorEnabled): static
    {
        $this->twoFactorEnabled = $twoFactorEnabled;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Vault>
     */
    public function getOwnedVaults(): Collection
    {
        return $this->ownedVaults;
    }

    public function addOwnedVault(Vault $ownedVault): static
    {
        if (!$this->ownedVaults->contains($ownedVault)) {
            $this->ownedVaults->add($ownedVault);
            $ownedVault->setOwner($this);
        }

        return $this;
    }

    public function removeOwnedVault(Vault $ownedVault): static
    {
        if ($this->ownedVaults->removeElement($ownedVault)) {
            // set the owning side to null (unless already changed)
            if ($ownedVault->getOwner() === $this) {
                $ownedVault->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VaultMember>
     */
    public function getVaultMembers(): Collection
    {
        return $this->vaultMembers;
    }

    public function addVaultMember(VaultMember $vaultMember): static
    {
        if (!$this->vaultMembers->contains($vaultMember)) {
            $this->vaultMembers->add($vaultMember);
            $vaultMember->setAuthor($this);
        }

        return $this;
    }

    public function removeVaultMember(VaultMember $vaultMember): static
    {
        if ($this->vaultMembers->removeElement($vaultMember)) {
            // set the owning side to null (unless already changed)
            if ($vaultMember->getAuthor() === $this) {
                $vaultMember->setAuthor(null);
            }
        }

        return $this;
    }
}
