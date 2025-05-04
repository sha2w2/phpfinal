<?php
namespace app\core;

abstract class abstractuser {
    protected $id;
    protected $username;
    protected $password;
    protected $encryptionkey;

    abstract public function authenticate(string $password): bool;
    abstract public function getencryptionkey(): string;
    abstract public function getusername(): string;
    abstract public function getid(): int;
}