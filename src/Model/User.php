<?php

namespace App\Model;

class User
{
    private int $id;
    private string $username;
    private string $fullName;
    private ?string $bio;
    private ?string $photoUrl;
    private int $isActive;

    // Constructor vacío por defecto
    public function __construct()
    {
        $this->id = 0;
        $this->username = '';
        $this->fullName = '';
        $this->bio = null;
        $this->photoUrl = null;
        $this->isActive = 1;
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

    public function getUsername(): string
    {
        return $this->username;
    }
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }
    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }
    public function setBio(?string $bio): void
    {
        $this->bio = $bio;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }
    public function setPhotoUrl(?string $photoUrl): void
    {
        $this->photoUrl = $photoUrl;
    }

    public function getIsActive(): int
    {
        return $this->isActive;
    }
    public function setIsActive(int $isActive): void
    {
        $this->isActive = $isActive;
    }
}
