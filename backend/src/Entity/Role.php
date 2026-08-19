<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: 'roles')]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['role:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['role:read', 'user:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    #[Groups(['role:read', 'user:read'])]
    private ?string $displayName = null;

    #[ORM\ManyToMany(targetEntity: Permission::class, inversedBy: 'roles', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'role_permissions')]
    #[Groups(['role:read'])]
    private Collection $permissions;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;
        return $this;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
        }

        return $this;
    }

    public function removePermission(Permission $permission): static
    {
        $this->permissions->removeElement($permission);
        return $this;
    }
}
