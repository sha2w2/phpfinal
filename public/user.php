<?php
namespace App\Models;

use App\Core\AuthInterface;
use App\Core\AbstractUser;
use App\Core\LoggerTrait;
use App\Core\Database;

class User extends AbstractUser implements AuthInterface
{
    use LoggerTrait;

    private Database $db;
    private string $encryptionMethod = 'aes-256-cbc';

    public function __construct(Database $db) 
    {
        $this->db = $db;
    }

    public function authenticate(string $username, string $password): bool 
    {
        return $this->login($username, $password);
    }

    public function getUsername(): string 
    {
        return $this->username ?? ($_SESSION['username'] ?? '');
    } 

    public function getId(): int 
    {
        return $this->id ?? ($_SESSION['id'] ?? 0);
    }

    public function getEncryptionKey(): string 
    {
        return $this->encryptionKey ?? ($_SESSION['encryption_key'] ?? '');
    }

    public function register(string $username, string $password): bool 
    {
        $conn = $this->db->getConnection();
        
        // Check if username already exists
        if ($this->usernameExists($username)) {
            $this->logActivity("Registration failed - username $username already exists");
            return false;
        }
        
        // Generate and encrypt the master key
        $this->encryptionKey = $this->generateEncryptionKey();
        $encryptedKey = $this->encryptKey($this->encryptionKey, $password);
        
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO users (username, password, encryption_key) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $hashedPassword, $encryptedKey);
        
        if ($stmt->execute()) {
            $this->id = $stmt->insert_id;
            $this->username = $username;
            $this->logActivity("User registration successful for $username");
            return true;
        }
        
        $this->logActivity("User registration failed for $username");
        return false;
    }

    public function login(string $username, string $password): bool 
    {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT id, password, encryption_key FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user && password_verify($password, $user['password'])) {
            // Decrypt the master key
            $this->encryptionKey = $this->decryptKey($user['encryption_key'], $password);
            
            if ($this->encryptionKey !== false) {
                $this->id = $user['id'];
                $this->username = $username;
                
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['encryption_key'] = $this->encryptionKey;
                $_SESSION['logged_in'] = true;
                
                $this->logActivity("User $username logged in successfully");
                return true;
            }
        }
        
        $this->logActivity("Failed login attempt for $username");
        return false;
    }

    public function logout(): void 
    {
        $username = $this->username;
        $this->id = 0;
        $this->username = '';
        $this->encryptionKey = '';
        
        session_unset();
        session_destroy();
        $this->logActivity("User $username logged out");
    }

    public function isLoggedIn(): bool 
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function changePassword(string $oldPassword, string $newPassword): bool 
    {
        if (!$this->isLoggedIn()) {
            $this->logActivity("Password change failed - user not logged in");
            return false;
        }
        
        $conn = $this->db->getConnection();
        
        // Verify old password first
        $sql = "SELECT password, encryption_key FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            $this->logActivity("Password change failed - incorrect old password for user ID: {$this->id}");
            return false;
        }
        
        // Decrypt with old password
        $decryptedKey = $this->decryptKey($user['encryption_key'], $oldPassword);
        if ($decryptedKey === false) {
            $this->logActivity("Password change failed - key decryption failed for user ID: {$this->id}");
            return false;
        }
        
        // Re-encrypt with new password
        $newEncryptedKey = $this->encryptKey($decryptedKey, $newPassword);
        $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Update database
        $sql = "UPDATE users SET password = ?, encryption_key = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $newHashedPassword, $newEncryptedKey, $this->id);
        $result = $stmt->execute();
        
        if ($result) {
            $this->encryptionKey = $decryptedKey;
            $_SESSION['encryption_key'] = $decryptedKey;
            $this->logActivity("Password successfully changed for user ID: {$this->id}");
        } else {
            $this->logActivity("Password change database update failed for user ID: {$this->id}");
        }
        
        return $result;
    }

    private function usernameExists(string $username): bool 
    {
        $conn = $this->db->getConnection();
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    private function generateEncryptionKey(): string 
    {
        return openssl_random_pseudo_bytes(32);
    }

    private function encryptKey(string $key, string $password): string 
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->encryptionMethod));
        $encrypted = openssl_encrypt(
            $key,
            $this->encryptionMethod,
            $password,
            0,
            $iv
        );
        return base64_encode($iv . $encrypted);
    }

    private function decryptKey(string $encryptedKey, string $password): string|false 
    {
        $data = base64_decode($encryptedKey);
        $ivLength = openssl_cipher_iv_length($this->encryptionMethod);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt(
            $encrypted,
            $this->encryptionMethod,
            $password,
            0,
            $iv
        );
    }
}