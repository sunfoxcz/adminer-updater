#!/usr/bin/env php8.2
<?php

declare(strict_types=1);

/**
 * Adminer Updater Script
 *
 * Checks for a new Adminer version and updates the specified file.
 *
 * Usage: php update-adminer.php [-v|--verbose] [--] <path>
 *   <path>  Required path to the Adminer PHP file
 *   -v      Show progress output
 */

define('ADMINER_WEBSITE', 'https://www.adminer.org/');
define('ADMINER_DOWNLOAD_URL', 'https://github.com/vrana/adminer/releases/download/v%s/adminer-%s.php');

/**
 * Reads the required Adminer file path and optional verbose flag.
 */
function parseArguments(array $arguments): array
{
    $localPath = null;
    $verbose = false;
    $optionsEnabled = true;

    foreach ($arguments as $argument) {
        if ($optionsEnabled && $argument === '--') {
            $optionsEnabled = false;
        } elseif ($optionsEnabled && in_array($argument, ['-v', '--verbose'], true)) {
            $verbose = true;
        } elseif ($optionsEnabled && ($argument[0] ?? '') === '-') {
            throw new InvalidArgumentException("Unknown option: $argument");
        } elseif ($localPath !== null) {
            throw new InvalidArgumentException('Specify only one path to the Adminer PHP file.');
        } else {
            $localPath = $argument;
        }
    }

    if ($localPath === null || $localPath === '') {
        throw new InvalidArgumentException('Specify the path to the Adminer PHP file.');
    }

    if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $localPath)
        || substr($localPath, -1) === '/'
        || in_array(basename($localPath), ['.', '..'], true)
        || (file_exists($localPath) && !is_file($localPath))
    ) {
        throw new InvalidArgumentException("Expected a local file path: $localPath");
    }

    return [$localPath, $verbose];
}

/**
 * Output info message (only in verbose mode).
 */
function info(string $message): void
{
    global $verbose;
    if ($verbose) {
        echo $message . "\n";
    }
}

/**
 * Output error message (always, to stderr).
 */
function error(string $message): void
{
    fwrite(STDERR, "Error: $message\n");
}

/**
 * Fetches the latest version from Adminer website.
 */
function getLatestVersion(): ?string
{
    $html = @file_get_contents(ADMINER_WEBSITE);

    if ($html === false) {
        error('Unable to fetch Adminer website.');
        return null;
    }

    // Look for version pattern in download links like "adminer-5.4.1.php"
    if (preg_match('/adminer-(\d+\.\d+\.\d+)\.php/', $html, $matches)) {
        return $matches[1];
    }

    // Alternative: look for version in release notes or headings
    if (preg_match('/Adminer\s+(\d+\.\d+\.\d+)/', $html, $matches)) {
        return $matches[1];
    }

    error('Unable to parse version from Adminer website.');
    return null;
}

/**
 * Gets the currently installed version from the specified file.
 */
function getInstalledVersion(string $localPath): ?string
{
    if (!file_exists($localPath)) {
        info('No Adminer installation found at ' . $localPath);
        return null;
    }

    $content = file_get_contents($localPath);

    if ($content === false) {
        error('Unable to read ' . $localPath);
        return null;
    }

    // Look for VERSION="x.x.x" pattern
    if (preg_match('/VERSION\s*=\s*["\'](\d+\.\d+\.\d+)["\']/', $content, $matches)) {
        return $matches[1];
    }

    // Alternative: look for @version x.x.x in comments
    if (preg_match('/@version\s+(\d+\.\d+\.\d+)/', $content, $matches)) {
        return $matches[1];
    }

    error('Unable to determine installed version.');
    return null;
}

/**
 * Downloads a file from URL to destination path.
 */
function downloadFile(string $url, string $destination): bool
{
    info("Downloading from: $url");

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Adminer-Updater/1.0\r\n",
            'follow_location' => true,
            'timeout' => 60,
        ],
    ]);

    $content = @file_get_contents($url, false, $context);

    if ($content === false) {
        error('Failed to download file from ' . $url);
        return false;
    }

    // Verify it looks like PHP/Adminer file
    if (strpos($content, '<?') === false || strpos($content, 'Adminer') === false) {
        error("Downloaded content doesn't appear to be valid Adminer file.");
        return false;
    }

    $result = file_put_contents($destination, $content);

    if ($result === false) {
        error("Failed to write file to $destination");
        return false;
    }

    info('Downloaded ' . number_format(strlen($content)) . ' bytes.');
    return true;
}

/**
 * Main update function.
 */
function updateAdminer(string $localPath): int
{
    info('=== Adminer Updater ===');
    info('');

    // Get latest version
    info('Checking latest version...');
    $latestVersion = getLatestVersion();

    if ($latestVersion === null) {
        return 1;
    }

    info("Latest version: $latestVersion");

    // Get installed version
    info('Checking installed version...');
    $installedVersion = getInstalledVersion($localPath);

    if ($installedVersion === null) {
        info('No existing installation found. Installing fresh.');
        $installedVersion = '0.0.0';
    } else {
        info("Installed version: $installedVersion");
    }

    // Compare versions
    if (version_compare($latestVersion, $installedVersion) <= 0) {
        info('Adminer is up to date. No update needed.');
        return 0;
    }

    info("New version available! Updating $installedVersion -> $latestVersion");

    // Ensure the destination directory exists
    $destinationDir = dirname($localPath);
    if (!is_dir($destinationDir)) {
        if (!mkdir($destinationDir, 0755, true)) {
            error("Unable to create directory $destinationDir");
            return 1;
        }
        info("Created directory: $destinationDir");
    }

    // Download new version
    $downloadUrl = sprintf(ADMINER_DOWNLOAD_URL, $latestVersion, $latestVersion);
    $tempFile = $localPath . '.tmp';

    if (!downloadFile($downloadUrl, $tempFile)) {
        @unlink($tempFile);
        return 1;
    }

    // Backup old file if exists
    if (file_exists($localPath)) {
        $backupFile = $localPath . '.bak';
        if (!rename($localPath, $backupFile)) {
            info('Warning: Could not create backup file.');
        } else {
            info("Backup created: $backupFile");
        }
    }

    // Move temp file to final location
    if (!rename($tempFile, $localPath)) {
        error('Failed to move downloaded file to final location.');
        return 1;
    }

    info("Adminer successfully updated to version $latestVersion");
    return 0;
}

try {
    [$localPath, $verbose] = parseArguments(array_slice($argv, 1));
} catch (InvalidArgumentException $exception) {
    error($exception->getMessage());
    fwrite(STDERR, "Usage: php update-adminer.php [-v|--verbose] [--] <path>\n");
    exit(1);
}

exit(updateAdminer($localPath));
