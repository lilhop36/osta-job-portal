<?php
/**
 * Authentication service utilities for future controller extraction.
 */
class AuthService {
    public static function findActiveUserByEmail(PDO $pdo, string $email): ?array {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}
