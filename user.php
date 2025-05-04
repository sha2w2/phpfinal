<?php
namespace App\Models;

use App\Core\authinterface;
use App\Core\abstractuser;
use App\Core\loggertrait;
use App\Core\database;

class user extends abstractuser implements authinterface {
    use loggertrait;

    private $db;
    private $encryptionMethod = 'aes-256-cbc';

    public function __construct(database $db) {
        $this->db = $db;
    }

    public function register(string $username, string $password): bool {
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
        
        $result = $stmt->execute();
        $this->logActivity("User registration " . ($result ? "successful" : "failed") . " for $username");
        return $result;
    }

    public function login(string $username, string $password): bool {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT id, password, encryption_key FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($id, $hashedPassword, $encryptedKey);
        $stmt->fetch();
        $stmt->close();
        
        if (password_verify($password, $hashedPassword)) {
            // Decrypt the master key
            $this->encryptionKey = $this->decryptKey($encryptedKey, $password);
            
            if ($this->encryptionKey !== false) {
                $_SESSION['id'] = $id;
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

    public function logout(): void {
        $username = $_SESSION['username'] ?? '';
        session_unset();
        session_destroy();
        $this->logActivity("User $username logged out");
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function changePassword(string $oldPassword, string $newPassword): bool {
        if (!$this->isLoggedIn()) {
            $this->logActivity("Password change failed - user not logged in");
            return false;
        }
        
        $conn = $this->db->getConnection();
        
        // Verify old password first
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_SESSION['id']);
        $stmt->execute();
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();
        $stmt->close();
        
        if (!password_verify($oldPassword, $hashedPassword)) {
            $this->logActivity("Password change failed - incorrect old password for user ID: {$_SESSION['id']}");
            return false;
        }
        
        // Get current encrypted key
        $sql = "SELECT encryption_key FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_SESSION['id']);
        $stmt->execute();
        $stmt->bind_result($encryptedKey);
        $stmt->fetch();
        $stmt->close();
        
        // Decrypt with old password
        $decryptedKey = $this->decryptKey($encryptedKey, $oldPassword);
        if ($decryptedKey === false) {
            $this->logActivity("Password change failed - key decryption failed for user ID: {$_SESSION['id']}");
            return false;
        }
        
        // Re-encrypt with new password
        $newEncryptedKey = $this->encryptKey($decryptedKey, $newPassword);
        $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Update database
        $sql = "UPDATE users SET password = ?, encryption_key = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $newHashedPassword, $newEncryptedKey, $_SESSION['id']);
        $result = $stmt->execute();
        
        if ($result) {
            // Update session with new key
            $_SESSION['encryption_key'] = $decryptedKey;
            $this->logActivity("Password successfully changed for user ID: {$_SESSION['id']}");
        } else {
            $this->logActivity("Password change database update failed for user ID: {$_SESSION['id']}");
        }
        
        return $result;
    }

    public function getEncryptionKey(): string {
        return $_SESSION['encryption_key'] ?? '';
    }

    private function usernameExists(string $username): bool {
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

    private function generateEncryptionKey(): string {
        return openssl_random_pseudo_bytes(32);
    }

    private function encryptKey(string $key, string $password): string {
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

    private function decryptKey(string $encryptedKey, string $password): string|false {
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
?>