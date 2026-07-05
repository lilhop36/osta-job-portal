<?php
/**
 * Job query service utilities for page-based entrypoints.
 */
class JobService {
    public static function findApprovedJob(PDO $pdo, int $jobId): ?array {
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'approved'");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        return $job ?: null;
    }
}
