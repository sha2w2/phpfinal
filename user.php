<?php
class User {
    private $db;
    private $id;
    private $username;
    private $password;
    private $encryption_key;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function register($username, $password) {
        $conn = $this->db->getConnection();
        
        // Generate a random encryption key for the user
        $this->encryption_key = openssl_random_pseudo_bytes(32);
        
        // Encrypt the key with the user's password
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted_key = openssl_encrypt($this->encryption_key, 'aes-256-cbc', $password, 0, $iv);
        $encrypted_key = base64_encode($iv . $encrypted_key);
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, password, encryption_key) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $hashed_password, $encrypted_key);
        
        return $stmt->execute();
    }

    public function login($username, $password) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT id, username, password, encryption_key FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($this->id, $this->username, $this->password, $encrypted_key);
            $stmt->fetch();
            
            if (password_verify($password, $this->password)) {
                // Decrypt the user's encryption key
                $decoded = base64_decode($encrypted_key);
                $iv_length = openssl_cipher_iv_length('aes-256-cbc');
                $iv = substr($decoded, 0, $iv_length);
                $encrypted_key = substr($decoded, $iv_length);
                
                $this->encryption_key = openssl_decrypt($encrypted_key, 'aes-256-cbc', $password, 0, $iv);
                
                session_start();
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $this->id;
                $_SESSION["username"] = $this->username;
                $_SESSION["encryption_key"] = $this->encryption_key;
                
                return true;
            }
        }
        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
    }

    public function logout() {
        session_start();
        $_SESSION = array();
        session_destroy();
    }

    public function getEncryptionKey() {
        return $this->encryption_key;
    }
}
?>