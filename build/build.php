<?php

/**
 * Build script for the Proclaim JSitemap Plugin.
 *
 * Creates proclaim.zip for installation via JSitemap admin UI.
 *
 * Usage:
 *   php build/build.php
 *   composer build
 *
 * @package    Proclaim JSitemap Plugin
 * @copyright  (C) 2026 CWM Team
 * @license    GPL-2.0-or-later
 */

$projectDir  = \dirname(__DIR__);
$buildDir    = __DIR__;
$manifestXml = $projectDir . '/proclaim.xml';

// Files and directories to include in the zip (relative to project root)
$includes = [
    'proclaim.php',
    'proclaim.xml',
    'language/',
    'index.html',
];

echo "Building Proclaim JSitemap Plugin...\n";
echo "Project: {$projectDir}\n";

// Read version from manifest
$version = 'unknown';

if (\file_exists($manifestXml)) {
    $xml = simplexml_load_string(file_get_contents($manifestXml));

    if ($xml && isset($xml->version)) {
        $version = (string) $xml->version;
    }
}

$zipFile = $buildDir . '/proclaim-' . $version . '.zip';

echo "Version: {$version}\n\n";

// Remove previous builds
foreach (\glob($buildDir . '/proclaim-*.zip') as $oldZip) {
    \unlink($oldZip);
    echo "Removed previous build: " . \basename($oldZip) . "\n";
}

// Verify ZipArchive is available
if (!\class_exists('ZipArchive')) {
    echo "ERROR: PHP zip extension is required. Install it with: apt install php-zip (or brew install php)\n";
    exit(1);
}

$zip = new ZipArchive();
$result = $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

if ($result !== true) {
    echo "ERROR: Could not create zip file: {$zipFile}\n";
    exit(1);
}

foreach ($includes as $item) {
    $fullPath = $projectDir . '/' . $item;

    if (\is_dir($fullPath)) {
        addDirectoryToZip($zip, $fullPath, $item);
    } elseif (\is_file($fullPath)) {
        $zip->addFile($fullPath, $item);
        echo "  + {$item}\n";
    } else {
        echo "  WARNING: Not found: {$item}\n";
    }
}

$zip->close();

$size = \round(\filesize($zipFile) / 1024, 1);

echo "\nBuild complete: build/" . \basename($zipFile) . " ({$size} KB)\n";
echo "Install via: JSitemap Admin > Data Sources > Import Plugin\n";

/**
 * Recursively add a directory to a ZipArchive.
 *
 * @param   ZipArchive  $zip       The zip archive
 * @param   string      $dirPath   Absolute path to the directory
 * @param   string      $zipPath   Relative path inside the zip
 *
 * @return  void
 */
function addDirectoryToZip(ZipArchive $zip, string $dirPath, string $zipPath): void
{
    $zip->addEmptyDir($zipPath);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        $filePath    = $file->getPathname();
        $relativePath = $zipPath . \substr($filePath, \strlen($dirPath));

        if ($file->isDir()) {
            $zip->addEmptyDir($relativePath);
        } else {
            $zip->addFile($filePath, $relativePath);
            echo "  + {$relativePath}\n";
        }
    }
}
