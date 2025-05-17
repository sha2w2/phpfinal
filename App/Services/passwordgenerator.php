<?php
namespace App\Services;

class PasswordGenerator {
    public function generate(
        int $length = 12,
        bool $useUppercase = true,
        bool $useLowercase = true,
        bool $useNumbers = true,
        bool $useSpecial = true,
        ?array $customParams = null
    ): string {
        // Handle custom parameters if provided
        if ($customParams !== null) {
            return $this->generateWithCustomParams($length, $customParams);
        }

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

    private function generateWithCustomParams(int $length, array $params): string {
        $password = '';
        $remainingLength = $length;
        
        // Process each character type with required counts
        foreach ($params as $type => $count) {
            if ($count <= 0) continue;
            
            $chars = $this->getCharsForType($type);
            for ($i = 0; $i < $count; $i++) {
                $password .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $remainingLength -= $count;
        }
        
        // Fill remaining length with random characters from all allowed types
        if ($remainingLength > 0) {
            $allChars = '';
            foreach ($params as $type => $count) {
                if ($count > 0) {
                    $allChars .= $this->getCharsForType($type);
                }
            }
            
            for ($i = 0; $i < $remainingLength; $i++) {
                $password .= $allChars[random_int(0, strlen($allChars) - 1)];
            }
        }
        
        // Shuffle the password to mix character types
        return str_shuffle($password);
    }

    private function getCharsForType(string $type): string {
        return match($type) {
            'lowercase' => 'abcdefghijklmnopqrstuvwxyz',
            'uppercase' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'numbers' => '0123456789',
            'special' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
            default => throw new \InvalidArgumentException("Invalid character type: $type")
        };
    }

    public function generateWithExactCounts(
        int $lowercase = 0,
        int $uppercase = 0,
        int $numbers = 0,
        int $special = 0
    ): string {
        $totalLength = $lowercase + $uppercase + $numbers + $special;
        if ($totalLength <= 0) {
            throw new \InvalidArgumentException('Total length must be greater than 0');
        }

        return $this->generateWithCustomParams($totalLength, [
            'lowercase' => $lowercase,
            'uppercase' => $uppercase,
            'numbers' => $numbers,
            'special' => $special
        ]);
    }

    public function calculateStrength(string $password): string {
        $score = 0;
        $length = strlen($password);
        
        // Length score
        $score += min(4, floor($length / 3));
        
        // Character variety
        $variety = 0;
        if (preg_match('/[a-z]/', $password)) $variety++;
        if (preg_match('/[A-Z]/', $password)) $variety++;
        if (preg_match('/[0-9]/', $password)) $variety++;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $variety++;
        $score += ($variety - 1) * 2;
        
        // Determine strength level
        if ($score < 3) return 'Weak';
        if ($score < 6) return 'Moderate';
        if ($score < 8) return 'Strong';
        return 'Very Strong';
    }
}