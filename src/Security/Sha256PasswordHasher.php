<?php

namespace App\Security;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * SHA-256 + Base64 hasher — compatible avec l'application JavaFX.
 * Note : sans sel, vulnérable aux rainbow tables. Acceptable pour projet académique.
 */
class Sha256PasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        return base64_encode(hash('sha256', $plainPassword, true));
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        return hash_equals($hashedPassword, $this->hash($plainPassword));
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}
