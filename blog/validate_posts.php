<?php
// validate_posts.php — run in browser to list invalid JSON files
ini_set('display_errors', 1);
error_reporting(E_ALL);

$dir = __DIR__ . '/posts';
if (!is_dir($dir)) {
    echo "posts folder missing: $dir";
    exit;
}
$files = glob($dir.'/*.json');
if (!$files) {
    echo "No JSON files found in posts/";
    exit;
}

echo "<h2>Validate posts/*.json</h2><pre>";
foreach ($files as $f) {
    $s = file_get_contents($f);
    $j = json_decode($s, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo basename($f) . " → JSON ERROR: " . json_last_error_msg() . PHP_EOL;
    } else {
        echo basename($f) . " → ok" . PHP_EOL;
    }
}
echo "</pre>";
