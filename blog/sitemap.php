<?php
// sitemap.php — outputs sitemap.xml for posts in blog/posts
header('Content-Type: application/xml; charset=utf-8');

$base = 'http://' . $_SERVER['HTTP_HOST'] . '/techpub/blog/';
$postsDir = __DIR__ . '/blog/posts';
$files = glob($postsDir . '/*.json');

echo '<?xml version="1.0" encoding="UTF-8"?>', PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', PHP_EOL;

foreach ($files as $f) {
    $j = json_decode(file_get_contents($f), true);
    if (!$j) continue;
    $slug = $j['slug'] ?? pathinfo($f, PATHINFO_FILENAME);
    $loc = $base . rawurlencode($slug) . '/';
    $mod = date('c', filemtime($f));
    echo "  <url>\n";
    echo "    <loc>$loc</loc>\n";
    echo "    <lastmod>$mod</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "  </url>\n";
}
echo '</urlset>';
