<?php
namespace app\core;

interface authInterface {
    public function register(string $username, string $password): bool;
    public function login(string $username, string $password): bool;
    public function logout(): void;
    public function isLoggedIn(): bool;
}
?>