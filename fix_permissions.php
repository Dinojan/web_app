<?php
$root = __DIR__;

// Set folders to 0755
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->isDir()) {
        chmod($file->getPathname(), 0755);
    } else {
        chmod($file->getPathname(), 0644);
    }
}

echo "✅ Permissions updated successfully.\n";
