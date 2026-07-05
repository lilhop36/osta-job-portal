<?php
$root = dirname(__DIR__);
$excluded = [DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR];
$failures = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    foreach ($excluded as $fragment) {
        if (strpos($path, $fragment) !== false) {
            continue 2;
        }
    }

    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path);
    exec($command, $output, $code);
    if ($code !== 0) {
        $failures[] = $path;
        echo implode(PHP_EOL, $output) . PHP_EOL;
    }
    $output = [];
}

if ($failures) {
    fwrite(STDERR, 'PHP lint failed for ' . count($failures) . ' file(s).' . PHP_EOL);
    exit(1);
}

echo 'PHP lint passed for all non-vendor PHP files.' . PHP_EOL;
