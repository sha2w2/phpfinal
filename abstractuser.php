<?php
namespace App\Core;

abstract class AbstractUser {
    protected $id;
    protected $username;
    protected $password;
    protected $encryptionKey;

    abstract public function authenticate(string $password): bool;
    abstract public function getEncryptionKey(): string;
}
?>