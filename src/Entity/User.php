<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
// 1. Import these for security protection
use Symfony\Component\Serializer\Annotation\Ignore;
use SensitiveParameter;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $username = '';

    #[ORM\Column(length: 100, unique: true)]
    private string $email = '';

    // 2. Ignore this from serialization to stop "Unprotected sensitive field"
    #[Ignore]
    #[ORM\Column(length: 255)]
    private string $password = '';

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $tel = null;

    #[ORM\Column(name: 'image_url', length: 500, nullable: true)]
    private ?string $imageUrl = null;

    // 3. Keep this as DATETIMETZ to satisfy the earlier DB config warning
    #[ORM\Column(name: 'created_at', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function eraseCredentials(): void
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    // 4. Ignore the getter to stop the "Public getter exposes sensitive field" warning
    #[Ignore]
    public function getPassword(): string
    {
        return $this->password;
    }

    // 5. Use SensitiveParameter to protect logs and stack traces
    public function setPassword(#[SensitiveParameter] string $password): self
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(?string $tel): self
    {
        $this->tel = $tel;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}