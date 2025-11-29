<?php
// save_post.php — accept categories & tags, auto excerpt if missing
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$base = __DIR__;
$postsDir = $base . '/posts';

// create directory if missing
if (!is_dir($postsDir)) {
    if (!mkdir($postsDir, 0777, true)) {
        http_response_code(500);
        echo json_encode(['status'=>'error','message'=>'Failed to create posts directory']);
        exit;
    }
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Invalid JSON payload']);
    exit;
}

// validation
if (empty($data['title']) || empty($data['content'])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Title and content are required']);
    exit;
}

// normalize slug
$slug = $data['slug'] ?? '';
$slug = trim($slug);
if ($slug === '') {
    $slug = preg_replace('/[^a-z0-9\-]/i', '-', strtolower($data['title']));
}
$slug = preg_replace('/-+/', '-', $slug);
$slug = trim($slug, '-');
if ($slug === '') $slug = 'post-' . time();

// prevent path traversal
$slug = basename($slug);

// collect fields
$title = $data['title'];
$date = $data['date'] ?? date('Y-m-d');
$image = $data['image'] ?? '';
$categories = [];
$tags = [];

// accept arrays or comma-separated strings
if (!empty($data['categories'])) {
    $categories = is_array($data['categories']) ? $data['categories'] : array_filter(array_map('trim', explode(',', (string)$data['categories'])));
}
if (!empty($data['tags'])) {
    $tags = is_array($data['tags']) ? $data['tags'] : array_filter(array_map('trim', explode(',', (string)$data['tags'])));
}

$excerpt = $data['excerpt'] ?? '';
$content = $data['content'];

// if excerpt empty, generate from stripped text (first 160 chars)
if (trim($excerpt) === '') {
    $plain = strip_tags($content);
    $excerpt = mb_substr(trim(preg_replace('/\s+/', ' ', $plain)), 0, 160, 'UTF-8');
}

$post = [
    'title' => $title,
    'slug' => $slug,
    'date' => $date,
    'image' => $image,
    'categories' => array_values($categories),
    'tags' => array_values($tags),
    'excerpt' => $excerpt,
    'content' => $content
];

// write file (avoid overwrite collision by appending timestamp if exists)
$filepath = $postsDir . '/' . $slug . '.json';
if (file_exists($filepath)) {
    // if existing, create unique filename with timestamp
    $filepath = $postsDir . '/' . $slug . '-' . time() . '.json';
}

// write with exclusive lock
$written = false;
$fp = fopen($filepath, 'c');
if ($fp) {
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        rewind($fp);
        $ok = fwrite($fp, json_encode($post, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        fflush($fp);
        flock($fp, LOCK_UN);
        $written = $ok !== false;
    }
    fclose($fp);
}

if (!$written) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Failed to save post']);
    exit;
}

// success
echo json_encode(['status'=>'success','file' => str_replace($base,'',$filepath), 'slug'=>$slug]);
exit;
