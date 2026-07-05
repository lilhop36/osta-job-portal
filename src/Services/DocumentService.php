<?php
declare(strict_types=1);

namespace App\Services;

class DocumentService
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function isAllowedExtension(string $filename, array $allowedExtensions): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedExtensions, true);
    }

    public static function buildStoredFilename(string $prefix, string $extension): string
    {
        $safePrefix = preg_replace('/[^A-Za-z0-9_-]/', '_', $prefix) ?: 'document';
        return $safePrefix . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($extension);
    }

    public static function getFileSizeHuman(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public static function isAllowedMimeType(string $mimeType, array $allowedTypes): bool
    {
        return in_array($mimeType, $allowedTypes, true);
    }
}
