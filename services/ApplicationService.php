<?php
/**
 * Application service placeholder for moving application workflow logic out of page files.
 */
class ApplicationService {
    public static function userHasApplication(PDO $pdo, int $userId): bool {
        $stmt = $pdo->prepare("SELECT id FROM centralized_applications WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return (bool) $stmt->fetch();
    }
}
