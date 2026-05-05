<?php

namespace App\Tests;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * Test 1: Validate that password hashing works automatically
     * Rule: The password should never be stored in plain text.
     */
    public function testPasswordIsHashedAutomatically()
    {
        $user = new User();
        $plainPassword = 'mySecurePassword123';
        
        $user->setPassword($plainPassword);

        // Assert that the stored password is not the plain text
        $this->assertNotEquals($plainPassword, $user->getPassword());
        
        // Assert that it is a valid BCrypt/Hash
        $this->assertTrue(password_verify($plainPassword, $user->getPassword()));
    }

    /**
     * Test 2: Validate Role Management
     * Rule: Every user must have at least ROLE_USER.
     */
    public function testDefaultRoleUser()
    {
        $user = new User();
        
        // Even with empty roles, it should return ROLE_USER
        $user->setRoles([]);
        
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    /**
     * Test 3: Validate unique roles
     * Rule: Roles should not be duplicated.
     */
    public function testRolesAreUnique()
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_ADMIN']);
        
        // getRoles() handles array_unique internally
        $roles = $user->getRoles();
        
        $count = array_count_values($roles);
        $this->assertEquals(1, $count['ROLE_ADMIN']);
    }

    /**
     * Test 4: Profile Data integrity
     * Rule: Username and Email must be retrievable.
     */
    public function testUserGettersAndSetters()
    {
        $user = new User();
        $email = 'test@travigir.com';
        $username = 'FaresBen';

        $user->setEmail($email);
        $user->setUsername($username);

        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals($email, $user->getUserIdentifier()); // Requirement for UserInterface
    }
}