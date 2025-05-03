<?php
namespace app\models;

use app\core\authinterface;
use app\core\abstractuser;
use app\core\loggertrait;
use app\core\database;

class user extends abstractuser implements authinterface {
    use loggertrait;

    private $db;
    private $id;
    private $username;
    private $password;
    private $encryptionkey;

    public function __construct(database $db) {
        $this->db = $db;
    }

    public function register(string $username, string $password): bool {
        $conn = $this->db->getconnection();
        
        // generate encryption key
        $this->encryptionkey = openssl_random_pseudo_bytes(32);
        
        // encrypt the key with user's password
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encryptedkey = openssl_encrypt(
            $this->encryptionkey, 
            'aes-256-cbc', 
            $password, 
            0, 
            $iv
        );
        $storablekey = base64_encode($iv . $encryptedkey);
        
        // hash the password
        $hashedpassword = password_hash($password, PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO users (username, password, encryption_key) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $hashedpassword, $storablekey);
        
        $result = $stmt->execute();
        $this->logactivity("user registration attempt for $username");
        
        return $result;
    }

    public function login(string $username, string $password): bool {
        $conn = $this->db->getconnection();
        
        $sql = "SELECT id, username, password, encryption_key FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($this->id, $this->username, $this->password, $encryptedkey);
            $stmt->fetch();
            
            if (password_verify($password, $this->password)) {
                // decrypt the encryption key
                $decoded = base64_decode($encryptedkey);
                $ivlength = openssl_cipher_iv_length('aes-256-cbc');
                $iv = substr($decoded, 0, $ivlength);
                $key = substr($decoded, $ivlength);
                
                $this->encryptionkey = openssl_decrypt(
                    $key, 
                    'aes-256-cbc', 
                    $password, 
                    0, 
                    $iv
                );
                
                session_start();
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $this->id;
                $_SESSION["username"] = $this->username;
                $_SESSION["encryption_key"] = $this->encryptionkey;
                
                $this->logactivity("successful login for $username");
                return true;
            }
        }
        
        $this->logactivity("failed login attempt for $username");
        return false;
    }

    public function logout(): void {
        session_start();
        $_SESSION = array();
        session_destroy();
        $this->logactivity("user logged out");
    }

    public function isloggedin(): bool {
        return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
    }

    public function authenticate(string $password): bool {
        return password_verify($password, $this->password);
    }

    public function getencryptionkey(): string {
        return $this->encryptionkey;
    }
}
?>