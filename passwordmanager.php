<?php
namespace App\Services;

use App\Core\database;

class passwordmanager {
    private $db;
    private $encryptionMethod = 'aes-256-cbc';

    public function __construct(database $db) {
        $this->db = $db;
    }

    public function encryptPassword(string $password, string $encryptionKey): string {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->encryptionMethod));
        $encrypted = openssl_encrypt(
            $password,
            $this->encryptionMethod,
            $encryptionKey,
            0,
            $iv
        );
        return base64_encode($iv . $encrypted);
    }

    public function decryptPassword(string $encryptedPassword, string $encryptionKey): string|false {
        $data = base64_decode($encryptedPassword);
        $ivLength = openssl_cipher_iv_length($this->encryptionMethod);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt(
            $encrypted,
            $this->encryptionMethod,
            $encryptionKey,
            0,
            $iv
        );
    }

    public function generatePassword(
        int $length = 12,
        bool $useUppercase = true,
        bool $useLowercase = true,
        bool $useNumbers = true,
        bool $useSpecial = true
    ): string {
        $chars = '';
        $password = '';
        
        if ($useLowercase) $chars .= 'abcdefghijklmnopqrstuvwxyz';
        if ($useUppercase) $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if ($useNumbers) $chars .= '0123456789';
        if ($useSpecial) $chars .= '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        if (empty($chars)) {
            throw new \InvalidArgumentException('At least one character type must be selected');
        }
        
        $charsLength = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $charsLength - 1)];
        }
        
        return $password;
    }

    public function addPassword(
        int $userId,
        string $serviceName,
        string $serviceUsername,
        string $password,
        string $encryptionKey,
        ?string $url = null,
        ?string $notes = null,
        ?string $category = null
    ): bool {
        $encryptedPassword = $this->encryptPassword($password, $encryptionKey);
        
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO stored_passwords 
                (user_id, service_name, service_username, encrypted_password, url, notes, category, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issssss",
            $userId,
            $serviceName,
            $serviceUsername,
            $encryptedPassword,
            $url,
            $notes,
            $category
        );
        
        return $stmt->execute();
    }

    public function getPasswords(int $userId, string $encryptionKey): array {
        $conn = $this->db->getConnection();
        $sql = "SELECT id, service_name, service_username, encrypted_password, url, notes, category, favorite, created_at, updated_at 
                FROM stored_passwords 
                WHERE user_id = ? 
                ORDER BY service_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $passwords = [];
        while ($row = $result->fetch_assoc()) {
            try {
                $row['password'] = $this->decryptPassword($row['encrypted_password'], $encryptionKey);
                $passwords[] = $row;
            } catch (\Exception $e) {
                // Skip passwords that can't be decrypted
                continue;
            }
        }
        
        return $passwords;
    }

    public function updatePassword(
        int $passwordId,
        string $serviceName,
        string $serviceUsername,
        string $password,
        string $encryptionKey,
        ?string $url = null,
        ?string $notes = null,
        ?string $category = null
    ): bool {
        $encryptedPassword = $this->encryptPassword($password, $encryptionKey);
        
        $conn = $this->db->getConnection();
        $sql = "UPDATE stored_passwords 
                SET service_name = ?, service_username = ?, encrypted_password = ?, url = ?, notes = ?, category = ?, updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssi",
            $serviceName,
            $serviceUsername,
            $encryptedPassword,
            $url,
            $notes,
            $category,
            $passwordId
        );
        
        return $stmt->execute();
    }

    public function deletePassword(int $passwordId): bool {
        $conn = $this->db->getConnection();
        $sql = "DELETE FROM stored_passwords WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $passwordId);
        return $stmt->execute();
    }

    public function toggleFavorite(int $passwordId, bool $favorite): bool {
        $conn = $this->db->getConnection();
        $sql = "UPDATE stored_passwords SET favorite = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $favorite, $passwordId);
        return $stmt->execute();
    }

    public function getPasswordById(int $passwordId, string $encryptionKey): ?array {
        $conn = $this->db->getConnection();
        $sql = "SELECT * FROM stored_passwords WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $passwordId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            try {
                $row['password'] = $this->decryptPassword($row['encrypted_password'], $encryptionKey);
                return $row;
            } catch (\Exception $e) {
                return null;
            }
        }
        
        return null;
    }
}