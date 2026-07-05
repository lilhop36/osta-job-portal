<?php
/**
 * Minimal .env loader — no Composer dependency required.
 * Loads KEY=VALUE pairs from .env into getenv()/$_ENV if not already set.
 * Lines starting with # are ignored. Existing environment variables
 * (e.g. set by the real server/host) always take precedence.
 */
if (!function_exists('load_env')) {
    function load_env(string $path): void {
        if (!is_readable($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Strip matching surrounding quotes if present
            if (strlen($value) >= 2 && $value[0] === $value[strlen($value) - 1] && ($value[0] === '"' || $value[0] === "'")) {
                $value = substr($value, 1, -1);
            }
            if (getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

/**
 * env('KEY', 'default') — read a config value with a fallback.
 */
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}

load_env(__DIR__ . '/../.env');
