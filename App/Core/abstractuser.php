<?php
namespace App\Core;

abstract class AbstractUser
{
    protected int $id;
    protected string $username;
    protected string $password;
    protected string $encryptionKey;

    abstract public function authenticate(string $username, string $password): bool;
    abstract public function getEncryptionKey(): string;
    abstract public function getUsername(): string;
    abstract public function getId(): int;
}