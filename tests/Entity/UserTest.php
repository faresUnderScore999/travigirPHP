<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testPasswordIsHashedAutomatically(): void
    {
        $user = new User();
        $plainPassword = 'mySecretPassword123';

        $user->setPassword($plainPassword);

        $this->assertNotSame($plainPassword, $user->getPassword());
        $this->assertTrue(password_verify($plainPassword, $user->getPassword()));
    }

    public function testDefaultRoleUser(): void
    {
        $user = new User();

        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testRolesAreUnique(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_USER']);

        $roles = $user->getRoles();

        $this->assertSame($roles, array_unique($roles));
    }

    public function testUserGettersAndSetters(): void
    {
        $user = new User();

        $user->setUsername('houssem');
        $this->assertSame('houssem', $user->getUsername());

        $user->setEmail('houssem@example.com');
        $this->assertSame('houssem@example.com', $user->getEmail());
        $this->assertSame('houssem@example.com', $user->getUserIdentifier());

        $user->setTel('12345678');
        $this->assertSame('12345678', $user->getTel());

        $user->setImageUrl('http://example.com/avatar.jpg');
        $this->assertSame('http://example.com/avatar.jpg', $user->getImageUrl());

        $date = new \DateTime('2024-01-01');
        $user->setCreatedAt($date);
        $this->assertSame($date, $user->getCreatedAt());
    }
}
