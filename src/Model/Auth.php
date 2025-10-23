<?php

namespace App\Model;

class Auth
{
    private int $id;
    private int $userId;
    private string $email;
    private string $passwordHash;
    private ?string $verificationCode;
    private ?string $verificationExpiresAt;
    private int $isEmailVerified;

    // Constructor vacío por defecto
    public function __construct()
    {
        $this->id = 0;
        $this->userId = 0;
        $this->email = '';
        $this->passwordHash = '';
        $this->verificationCode = null;
        $this->verificationExpiresAt = null;
        $this->isEmailVerified = 0;
    }

    // Getters y Setters
    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }
    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }
    public function setVerificationCode(?string $verificationCode): void
    {
        $this->verificationCode = $verificationCode;
    }

    public function getVerificationExpiresAt(): ?string
    {
        return $this->verificationExpiresAt;
    }
    public function setVerificationExpiresAt(?string $verificationExpiresAt): void
    {
        $this->verificationExpiresAt = $verificationExpiresAt;
    }

    public function getIsEmailVerified(): int
    {
        return $this->isEmailVerified;
    }
    public function setIsEmailVerified(int $isEmailVerified): void
    {
        $this->isEmailVerified = $isEmailVerified;
    }
}
