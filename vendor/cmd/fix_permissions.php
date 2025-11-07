<?php
$root = realpath(__DIR__ . '/../../'); // Go up to project root (e.g. web_app_1)

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$count = 0;

foreach ($iterator as $file) {
    if ($file->isDir()) {
        chmod($file->getPathname(), 0755);
    } else {
        chmod($file->getPathname(), 0644);
    }
    $count++;
}

echo "<pre>✅ Permissions fixed for $count files/directories in: $root</pre>";
