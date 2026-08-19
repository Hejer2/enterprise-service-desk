<?php

namespace App\Entity;

use App\Repository\SettingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
#[ORM\Table(name: 'settings')]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['setting:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'setting_key', length: 100, unique: true)]
    #[Groups(['setting:read'])]
    private ?string $settingKey = null;

    #[ORM\Column(name: 'setting_value', type: 'text', nullable: true)]
    #[Groups(['setting:read'])]
    private ?string $settingValue = null;

    #[ORM\Column(length: 50)]
    #[Groups(['setting:read'])]
    private ?string $category = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettingKey(): ?string
    {
        return $this->settingKey;
    }

    public function setSettingKey(string $settingKey): static
    {
        $this->settingKey = $settingKey;
        return $this;
    }

    public function getSettingValue(): ?string
    {
        return $this->settingValue;
    }

    public function setSettingValue(?string $settingValue): static
    {
        $this->settingValue = $settingValue;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }
}
