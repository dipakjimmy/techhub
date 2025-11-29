<?php
// single.php — render a single blog post from posts/<slug>.json
// Supports clean URLs like /blog/slug-name/ via .htaccess rewrite
ini_set('display_errors', 0);
error_reporting(E_ALL);

$base = __DIR__;
$postsDir = $base . '/posts';

// Helpers
function safe($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Extract slug from URL - handle both query string and clean URLs
$slug = '';
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $slug = preg_replace('/[^A-Za-z0-9\-\_]/', '', $_GET['slug']);
    $slug = trim($slug);
}

// Also try to extract from REQUEST_URI for clean URLs (fallback)
if (empty($slug) && isset($_SERVER['REQUEST_URI'])) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // Extract slug from path like /techpub/blog/slug-name/ or /blog/slug-name/
    if (preg_match('#/([A-Za-z0-9\-\_]+)/?$#', $uri, $matches)) {
        $slug = $matches[1];
        // Exclude common paths
        if (!in_array(strtolower($slug), ['blog', 'posts', 'single.php', 'index.php', 'blog-list.php'])) {
            $slug = trim($slug);
        } else {
            $slug = '';
        }
    }
}

if (empty($slug)) {
    http_response_code(400);
    $error = "No post specified.";
    // Determine base URL dynamically
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $scheme . '://' . $host . '/techpub/blog/';
    echo "<!doctype html><html><head><meta charset='utf-8'><title>Missing post</title></head><body><h1>Missing post</h1><p>$error</p><p><a href='$baseUrl'>Back to list</a></p></body></html>";
    exit;
}

// Find post by slug - search through all JSON files to match slug field
$filename = null;
$files = glob($postsDir . '/*.json');
foreach ($files as $file) {
    $raw = @file_get_contents($file);
    if ($raw === false) continue;
    
    $data = json_decode($raw, true);
    if (!is_array($data)) continue;
    
    $postSlug = $data['slug'] ?? '';
    $fileSlug = pathinfo($file, PATHINFO_FILENAME);
    
    // Match by slug field in JSON, or by filename if slug matches
    if ($postSlug === $slug || $fileSlug === $slug) {
        $filename = $file;
        break;
    }
}

if (!$filename || !file_exists($filename)) {
    http_response_code(404);
    // Determine base URL dynamically
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $scheme . '://' . $host . '/techpub/blog/';
    echo "<!doctype html><html><head><meta charset='utf-8'><title>Post not found</title></head><body><h1>Post not found</h1><p>The post '<strong>" . safe($slug) . "</strong>' does not exist.</p><p><a href='$baseUrl'>Back to list</a></p></body></html>";
    exit;
}

// read json
$raw = file_get_contents($filename);
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(500);
    // Determine base URL dynamically
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $scheme . '://' . $host . '/techpub/blog/';
    echo "<!doctype html><html><head><meta charset='utf-8'><title>Error</title></head><body><h1>Invalid post</h1><p>Could not parse post file.</p><p><a href='$baseUrl'>Back to list</a></p></body></html>";
    exit;
}

// defaults if missing
$title = $data['title'] ?? 'Untitled';
$date  = $data['date'] ?? date('Y-m-d', filemtime($filename));
$image = $data['image'] ?? '';
$excerpt = $data['excerpt'] ?? '';
$content = $data['content'] ?? '';
$categories = $data['categories'] ?? [];
$tags = $data['tags'] ?? [];

// Get the actual slug from data (in case filename differs)
$postSlug = $data['slug'] ?? $slug;

// Build canonical URL and base URL dynamically
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $scheme . '://' . $host . '/techpub/blog/';
$canonicalUrl = $baseUrl . rawurlencode($postSlug) . '/';

// Fetch recent posts for sidebar (excluding current post)
$recentPosts = [];
$allFiles = glob($postsDir . '/*.json');
foreach ($allFiles as $file) {
    if ($file === $filename) continue; // Skip current post
    
    $raw = @file_get_contents($file);
    if ($raw === false) continue;
    
    $postData = json_decode($raw, true);
    if (!is_array($postData)) continue;
    
    $recentPosts[] = [
        'title' => $postData['title'] ?? 'Untitled',
        'slug' => $postData['slug'] ?? pathinfo($file, PATHINFO_FILENAME),
        'date' => $postData['date'] ?? date('Y-m-d', filemtime($file)),
        'excerpt' => $postData['excerpt'] ?? ''
    ];
}

// Sort by date, newest first
usort($recentPosts, function($a, $b) {
    $dateA = strtotime($a['date']) ?: 0;
    $dateB = strtotime($b['date']) ?: 0;
    return $dateB - $dateA;
});

// Get top 5 recent posts
$recentPosts = array_slice($recentPosts, 0, 5);

// Simple template using your site's main.css
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo safe($title); ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="<?php echo safe($excerpt); ?>">
  <meta name="keywords" content="<?php echo safe(implode(', ', array_merge($categories, $tags))); ?>">
  
  <!-- Preconnect for faster loading -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">
  <link rel="preconnect" href="https://images.unsplash.com">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  
  <!-- Site CSS -->
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/main.css">
  
  <link rel="canonical" href="<?php echo $canonicalUrl; ?>">
  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="article">
  <meta property="og:title" content="<?php echo safe($title); ?>">
  <meta property="og:description" content="<?php echo safe($excerpt); ?>">
  <meta property="og:url" content="<?php echo $canonicalUrl; ?>">
  <?php if ($image): ?>
  <meta property="og:image" content="<?php echo safe($image); ?>">
  <?php endif; ?>
  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo safe($title); ?>">
  <meta name="twitter:description" content="<?php echo safe($excerpt); ?>">
  <?php if ($image): ?>
  <meta name="twitter:image" content="<?php echo safe($image); ?>">
  <?php endif; ?>
  <style>
    /* small page-specific tweaks so content displays nicely */
    .single-post-wrapper { max-width:1200px; margin:34px auto; padding:0 16px; }
    .post-article-wrapper { max-width:900px; }
    .post-meta { color:#6b6b6b; margin-bottom:10px; font-size:14px; }
    .post-title { font-size:32px; margin:0 0 12px; }
    .post-image { width:100%; max-height:420px; object-fit:cover; border-radius:6px; margin-bottom:14px; display:block; }
    .post-content { line-height:1.8; color:#222; font-size:16px; }
    .post-content h2 { margin-top: 32px; margin-bottom: 16px; color: #0b61a4; }
    .post-content h3 { margin-top: 24px; margin-bottom: 12px; color: #333; }
    .post-content h4 { margin-top: 20px; margin-bottom: 10px; color: #555; }
    .post-content ul, .post-content ol { margin: 16px 0; padding-left: 24px; }
    .post-content li { margin: 8px 0; }
    .post-content p { margin: 16px 0; }
    .back-link { display:inline-block; margin-top:18px; color:#0b61a4; text-decoration:none; font-weight:600; }
    .back-link:hover { text-decoration: underline; }
    .sidebar { position: sticky; top: 150px; }
    /* Loading state */
    #header-topbar-include, #header-mobile-include, #footer-include {
      min-height: 60px;
    }
    
    /* Make headers sticky on single post pages */
    #header-topbar-include {
      position: sticky;
      top: 0;
      z-index: 1001;
    }
    
    #header-mobile-include {
      position: sticky;
      top: 0;
      z-index: 1001;
    }
    
    /* Ensure header elements are sticky */
    .top-bar {
      position: sticky;
      top: 0;
      z-index: 1002;
    }
    
    .main-header {
      position: sticky;
      top: 0;
      z-index: 1001;
    }
    
    /* When top bar exists, position main header below it */
    .top-bar ~ .main-header,
    #header-topbar-include ~ #header-mobile-include .main-header {
      top: 32px; /* Approximate height of top bar */
    }
    
    /* Adjust sidebar position based on header */
    #header-topbar-include ~ #header-mobile-include ~ main .sidebar {
      top: 150px; /* Account for top bar + main header */
    }
    
    @media (max-width: 768px) {
      .sidebar {
        top: 100px; /* Smaller header on mobile */
      }
      
      #header-topbar-include ~ #header-mobile-include ~ main .sidebar {
        top: 100px;
      }
    }
    
    /* Smooth transitions */
    body.loaded * {
      transition: opacity 0.2s ease-in-out;
    }
    /* Prevent FOUC (Flash of Unstyled Content) */
    body:not(.loaded) {
      opacity: 0;
    }
    body.loaded {
      opacity: 1;
      transition: opacity 0.3s ease-in;
    }
    @media (max-width:991px) { 
      .post-title { font-size:24px; } 
      .single-post-wrapper { padding: 0 12px; }
      .sidebar { position: static; margin-top: 32px; }
    }
  </style>
</head>
<body>
  <!-- Header Navigation -->
  <div id="header-topbar-include"></div>
  <div id="header-mobile-include"></div>
  <script>
    // Set base path for components
    window.BASE_PATH = '<?php echo $baseUrl; ?>';
  </script>
  <script src="<?php echo $baseUrl; ?>assets/js/includes.js"></script>

  <main class="single-post-wrapper" role="main">
    <div class="row g-4">
      <!-- Main Article Content -->
      <div class="col-lg-8">
        <article class="post-article-wrapper">
          <div class="post-meta">
            <?php echo date('F j, Y', strtotime($date)); ?>
            <?php if (!empty($categories)): ?>
              <span style="margin: 0 8px;">·</span>
              <?php foreach ($categories as $i => $cat): ?>
                <span style="color: #0b61a4;"><?php echo safe($cat); ?></span><?php if ($i < count($categories) - 1) echo ', '; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <h1 class="post-title"><?php echo safe($title); ?></h1>
          <?php if ($image): ?>
            <img class="post-image" src="<?php echo safe($image); ?>" alt="<?php echo safe($title); ?>" loading="lazy" decoding="async">
          <?php endif; ?>
          <div class="post-content">
            <?php
              // content is allowed to contain HTML (trusted by admin). Output raw, but escape if untrusted.
              echo $content;
            ?>
          </div>
          
          <?php if (!empty($tags)): ?>
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
              <strong>Tags:</strong>
              <?php foreach ($tags as $tag): ?>
                <span style="display: inline-block; background: #f0f0f0; padding: 4px 12px; border-radius: 4px; margin: 4px 4px 4px 0; font-size: 14px;"><?php echo safe($tag); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <a class="back-link" href="<?php echo $baseUrl; ?>">&larr; Back to articles</a>
        </article>
      </div>

      <!-- Sidebar -->
      <aside class="col-lg-4">
        <div class="sidebar">
          <!-- Recent Posts -->
          <?php if (!empty($recentPosts)): ?>
          <div class="service-card" style="padding: 14px; margin-bottom: 16px;">
            <h5 style="margin-bottom: 10px;">Recent posts</h5>
            <ul style="list-style: none; padding: 0; margin: 0;">
              <?php foreach ($recentPosts as $recent): ?>
                <li style="margin-bottom: 12px;">
                  <a href="<?php echo $baseUrl . rawurlencode($recent['slug']) . '/'; ?>" style="color: #0b61a4; text-decoration: none; display: block; margin-bottom: 4px;">
                    <?php echo safe($recent['title']); ?>
                  </a>
                  <div style="font-size: 13px; color: #6b6b6b;">
                    <?php echo date('M j, Y', strtotime($recent['date'])); ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <!-- Categories Widget -->
          <?php
          // Get all categories from all posts
          $allCategories = [];
          foreach ($allFiles as $file) {
              $raw = @file_get_contents($file);
              if ($raw === false) continue;
              $postData = json_decode($raw, true);
              if (!is_array($postData)) continue;
              if (isset($postData['categories']) && is_array($postData['categories'])) {
                  foreach ($postData['categories'] as $cat) {
                      if (!in_array($cat, $allCategories)) {
                          $allCategories[] = $cat;
                      }
                  }
              }
          }
          sort($allCategories);
          ?>
          <?php if (!empty($allCategories)): ?>
          <div class="service-card" style="padding: 16px; margin-bottom: 16px;">
            <h5 style="margin-bottom: 10px;">Categories</h5>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
              <?php foreach ($allCategories as $cat): ?>
                <a href="<?php echo $baseUrl; ?>" class="estimation-btn" style="display: inline-block; padding: 6px 12px; background: #f0f0f0; border-radius: 4px; text-decoration: none; color: #333; font-size: 14px; border: none; cursor: pointer;">
                  <?php echo safe($cat); ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </main>

  <!-- Footer -->
  <footer>
    <div id="footer-include"></div>
  </footer>

  <!-- Scripts -->
  <script src="<?php echo $baseUrl; ?>assets/js/footer.js"></script>
  <script src="<?php echo $baseUrl; ?>assets/js/nav.js"></script>
  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  
  <!-- Smooth scroll and performance optimizations -->
  <script>
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href.length > 1) {
          e.preventDefault();
          const target = document.querySelector(href);
          if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }
      });
    });
    
    // Mark page as loaded
    window.addEventListener('load', function() {
      document.body.classList.add('loaded');
    });
  </script>
</body>
</html>
