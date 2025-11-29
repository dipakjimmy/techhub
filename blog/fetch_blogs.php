<?php
// robust fetch_blogs.php — returns {"posts":[...],"errors":[...]}
ini_set('display_errors', 0); // hide direct warnings (we capture errors instead)
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$base = __DIR__;
$dir = $base . '/posts';
$result = ['posts' => [], 'errors' => []];

if (!is_dir($dir)) {
    $result['errors'][] = "posts directory missing: " . $dir;
    echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    exit;
}

$files = glob($dir . '/*.json');
if (!$files) {
    echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    exit;
}

foreach ($files as $f) {
    $raw = @file_get_contents($f);
    if ($raw === false) {
        $result['errors'][] = "Could not read file: " . basename($f);
        continue;
    }
    $item = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $result['errors'][] = basename($f) . " : JSON error: " . json_last_error_msg();
        continue;
    }
    // Normalize minimal fields
    $result['posts'][] = [
        'id' => $item['slug'] ?? pathinfo($f, PATHINFO_FILENAME),
        'title' => $item['title'] ?? 'Untitled',
        'slug' => $item['slug'] ?? pathinfo($f, PATHINFO_FILENAME),
        'date' => $item['date'] ?? date("c", filemtime($f)),
        'excerpt' => $item['excerpt'] ?? '',
        'image' => $item['image'] ?? '',
        'content' => $item['content'] ?? '',
        'categories' => $item['categories'] ?? [],
        'filename' => basename($f)
    ];
}

// sort newest first
usort($result['posts'], function($a,$b){
    return strtotime($b['date']) - strtotime($a['date']);
});

echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
