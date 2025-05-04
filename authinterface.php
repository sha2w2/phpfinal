<?php
namespace App\Core;

interface authinterface {
    public function register(string $username, string $password): bool;
    public function login(string $username, string $password): bool;
    public function logout(): void;
    public function isloggedin(): bool;
    public function changepassword(string $oldpassword, string $newpassword): bool;
}