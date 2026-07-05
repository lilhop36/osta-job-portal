<?php
/**
 * Document service utilities for upload/download hardening.
 */
class DocumentService {
    public static function isAllowedExtension(string $filename, array $allowedExtensions): bool {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedExtensions, true);
    }

    public static function buildStoredFilename(string $prefix, string $extension): string {
        $safePrefix = preg_replace('/[^A-Za-z0-9_-]/', '_', $prefix) ?: 'document';
        return $safePrefix . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($extension);
    }
}
