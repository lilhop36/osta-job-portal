<?php
/**
 * Shared application helpers for redirects, roles, paths, and small UI state.
 */

if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 'admin');
}
if (!defined('ROLE_EMPLOYER')) {
    define('ROLE_EMPLOYER', 'employer');
}
if (!defined('ROLE_APPLICANT')) {
    define('ROLE_APPLICANT', 'applicant');
}

if (!function_exists('app_base_path')) {
    function app_base_path(): string {
        $path = parse_url(defined('SITE_URL') ? SITE_URL : '', PHP_URL_PATH);
        return rtrim($path ?: '', '/');
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string {
        $base = rtrim(defined('SITE_URL') ? SITE_URL : '', '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('is_safe_internal_redirect')) {
    function is_safe_internal_redirect(?string $target): bool {
        if (!$target) {
            return false;
        }

        $target = trim($target);
        if ($target === '' || preg_match('/[\r\n]/', $target)) {
            return false;
        }

        $parts = parse_url($target);
        if ($parts === false) {
            return false;
        }

        if (isset($parts['scheme']) || isset($parts['host']) || str_starts_with($target, '//')) {
            return false;
        }

        return $target[0] === '/' || preg_match('/^[A-Za-z0-9._\-\/?=&%#]+$/', $target) === 1;
    }
}

if (!function_exists('safe_redirect_target')) {
    function safe_redirect_target(?string $target, string $fallback = 'index.php'): string {
        return is_safe_internal_redirect($target) ? $target : $fallback;
    }
}

if (!function_exists('safe_redirect')) {
    function safe_redirect(?string $target, string $fallback = 'index.php'): void {
        header('Location: ' . safe_redirect_target($target, $fallback));
        exit();
    }
}

if (!function_exists('safe_referer_redirect')) {
    function safe_referer_redirect(string $fallback = 'index.php'): void {
        safe_redirect($_SERVER['HTTP_REFERER'] ?? null, $fallback);
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $type, string $message): void {
        $_SESSION[$type . '_message'] = $message;
    }
}

if (!function_exists('clean_download_filename')) {
    function clean_download_filename(string $filename): string {
        $filename = basename($filename);
        return preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'download';
    }
}
