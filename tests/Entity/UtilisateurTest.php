<?php

namespace App\Tests\Entity;

use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;

class UtilisateurTest extends TestCase
{
    private Utilisateur $user;

    protected function setUp(): void
    {
        $this->user = new Utilisateur();
    }

    public function testNomPrenom(): void
    {
        $this->user->setNom('Hamdouni')->setPrenom('Taha');
        $this->assertEquals('Hamdouni', $this->user->getNom());
        $this->assertEquals('Taha', $this->user->getPrenom());
    }

    public function testEmail(): void
    {
        $this->user->setEmail('taha@test.com');
        $this->assertEquals('taha@test.com', $this->user->getEmail());
        $this->assertEquals('taha@test.com', $this->user->getUserIdentifier());
    }

    public function testRole(): void
    {
        $this->user->setRole('admin');
        $this->assertEquals('admin', $this->user->getRole());
        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());
    }

    public function testRoleEmploye(): void
    {
        $this->user->setRole('employe');
        $this->assertContains('ROLE_EMPLOYE', $this->user->getRoles());
    }

    public function testAccountLock(): void
    {
        $this->user->setAccountLocked(true);
        $this->assertTrue($this->user->isAccountLocked());

        $this->user->setAccountLocked(false);
        $this->assertFalse($this->user->isAccountLocked());
    }

    public function testFailedLoginAttempts(): void
    {
        $this->user->setFailedLoginAttempts(3);
        $this->assertEquals(3, $this->user->getFailedLoginAttempts());
    }

    public function testTheme(): void
    {
        $this->user->setTheme('dark');
        $this->assertEquals('dark', $this->user->getTheme());

        $this->user->setTheme('light');
        $this->assertEquals('light', $this->user->getTheme());
    }

    public function testDefaultTheme(): void
    {
        $this->assertEquals('light', $this->user->getTheme());
    }

    public function testAvatar(): void
    {
        $this->user->setAvatar('avatar_123.jpg');
        $this->assertEquals('avatar_123.jpg', $this->user->getAvatar());
    }

    public function testLastEmotion(): void
    {
        $this->user->setLastEmotion('happy');
        $this->assertEquals('happy', $this->user->getLastEmotion());
    }

    public function testCin(): void
    {
        $this->user->setCin(12345678);
        $this->assertEquals(12345678, $this->user->getCin());
    }

    public function testStatut(): void
    {
        $this->user->setStatut('actif');
        $this->assertEquals('actif', $this->user->getStatut());
    }

    public function testEraseCredentials(): void
    {
        $this->expectNotToPerformAssertions();
        $this->user->eraseCredentials();
    }
}
