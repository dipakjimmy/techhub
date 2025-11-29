<?php
<?php
// Simple blog index that reads JSON files from ./posts
$postsDir = __DIR__ . '/posts';
$posts = [];
if (is_dir($postsDir)) {
    $files = glob($postsDir . '/*.json');
    foreach ($files as $f) {
        $raw = @file_get_contents($f);
        if ($raw === false) continue;
        // tolerate JS-style comments and trailing commas
        $raw = preg_replace('#//.*#', '', $raw);
        $raw = preg_replace('#/\*.*?\*/#s', '', $raw);
        $raw = preg_replace('/,\s*(\}|])/m', '$1', $raw);
        $data = json_decode($raw, true);
        if (!is_array($data)) continue;
        $posts[] = [
            'title' => $data['title'] ?? $data['name'] ?? pathinfo($f, PATHINFO_FILENAME),
            'date' => $data['date'] ?? '',
            'excerpt' => $data['excerpt'] ?? $data['summary'] ?? '',
            'image' => $data['image'] ?? '',
            'filename' => basename($f),
        ];
    }
    usort($posts, function($a,$b){
        $da = strtotime($a['date']) ?: 0;
        $db = strtotime($b['date']) ?: 0;
        return $db - $da;
    });
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Blog list</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .card-img-top{height:160px;object-fit:cover}
    .sidebar { position: sticky; top: 96px; }
  </style>
</head>
<body>
<div class="container py-4" style="max-width:1100px;">
  <div class="row g-4">
    <main class="col-lg-8">
      <h1 class="mb-3">Blog</h1>
      <div class="row row-cols-1 row-cols-md-2 g-4" id="postsGrid">
<?php foreach ($posts as $p): ?>
        <article class="col">
          <div class="card h-100">
            <?php if ($p['image']): ?>
            <img src="<?php echo htmlspecialchars($p['image']); ?>" class="card-img-top" alt="">
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
              <small class="text-muted"><?php echo htmlspecialchars($p['date']); ?></small>
              <h5 class="card-title mt-1"><?php echo htmlspecialchars($p['title']); ?></h5>
              <p class="card-text"><?php echo htmlspecialchars(strip_tags($p['excerpt'])); ?></p>
              <div class="mt-auto">
                <a href="#" class="btn btn-sm btn-primary" data-filename="<?php echo htmlspecialchars($p['filename']); ?>">Read article</a>
              </div>
            </div>
          </div>
        </article>
<?php endforeach; ?>
      </div>
    </main>

    <aside class="col-lg-4">
      <div class="sidebar">
        <div class="mb-3">
          <label class="form-label">Search</label>
          <input id="searchInput" class="form-control" placeholder="Search posts...">
        </div>
        <div class="card p-3 mb-3">
          <h6>Recent posts</h6>
          <ul id="recentList" class="list-unstyled mb-0">
<?php foreach (array_slice($posts,0,5) as $r): ?>
            <li class="mb-2"><a href="#" class="recent-link" data-filename="<?php echo htmlspecialchars($r['filename']); ?>"><?php echo htmlspecialchars($r['title']); ?></a><br><small class="text-muted"><?php echo htmlspecialchars($r['date']); ?></small></li>
<?php endforeach; ?>
          </ul>
        </div>
      </div>
    </aside>
  </div>
</div>

<!-- Modal to show post -->
<div class="modal fade" id="postModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="modalTitle" class="modal-title"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <img id="modalImage" src="" alt="" style="max-width:100%; margin-bottom:12px; display:none;">
        <div id="modalMeta" class="text-muted mb-2"></div>
        <div id="modalContent"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const modalEl = document.getElementById('postModal');
  const bsModal = new bootstrap.Modal(modalEl);
  function fmtDate(d){ try{ return new Date(d).toLocaleDateString(); }catch(e){return d||'';} }

  async function openPostFile(filename){
    if (!filename) return alert('Post file not set');
    try {
      const res = await fetch('posts/' + filename, {cache:'no-cache'});
      if (!res.ok) throw new Error('Not found');
      const data = await res.json();
      document.getElementById('modalTitle').textContent = data.title || '';
      const img = data.image || '';
      const modalImage = document.getElementById('modalImage');
      if (img) { modalImage.src = img; modalImage.style.display = 'block'; } else modalImage.style.display = 'none';
      document.getElementById('modalMeta').textContent = (data.date ? fmtDate(data.date) + ' · ' : '') + ((data.categories||[]).join(', ')||'');
      document.getElementById('modalContent').innerHTML = data.content || data.excerpt || '<p>No content.</p>';
      bsModal.show();
    } catch (err) {
      console.error(err);
      alert('Could not load post: ' + filename);
    }
  }

  document.querySelectorAll('[data-filename]').forEach(el=>{
    el.addEventListener('click', e=>{
      e.preventDefault();
      openPostFile(el.getAttribute('data-filename'));
    });
  });
  document.querySelectorAll('.recent-link').forEach(el=>{
    el.addEventListener('click', e=>{
      e.preventDefault();
      openPostFile(el.getAttribute('data-filename'));
    });
  });

  // simple search (client-side)
  const search = document.getElementById('searchInput');
  if (search){
    search.addEventListener('input', ()=>{
      const q = search.value.trim().toLowerCase();
      document.querySelectorAll('#postsGrid article').forEach(a=>{
        const txt = a.textContent.toLowerCase();
        a.style.display = q && !txt.includes(q) ? 'none' : '';
      });
    });
  }
})();
</script>
</body>
</html>
```// filepath: c:\xampp\htdocs\techpub\blog\blog-list.php
<?php
// Simple blog index that reads JSON files from ./posts
$postsDir = __DIR__ . '/posts';
$posts = [];
if (is_dir($postsDir)) {
    $files = glob($postsDir . '/*.json');
    foreach ($files as $f) {
        $raw = @file_get_contents($f);
        if ($raw === false) continue;
        // tolerate JS-style comments and trailing commas
        $raw = preg_replace('#//.*#', '', $raw);
        $raw = preg_replace('#/\*.*?\*/#s', '', $raw);
        $raw = preg_replace('/,\s*(\}|])/m', '$1', $raw);
        $data = json_decode($raw, true);
        if (!is_array($data)) continue;
        $posts[] = [
            'title' => $data['title'] ?? $data['name'] ?? pathinfo($f, PATHINFO_FILENAME),
            'date' => $data['date'] ?? '',
            'excerpt' => $data['excerpt'] ?? $data['summary'] ?? '',
            'image' => $data['image'] ?? '',
            'filename' => basename($f),
        ];
    }
    usort($posts, function($a,$b){
        $da = strtotime($a['date']) ?: 0;
        $db = strtotime($b['date']) ?: 0;
        return $db - $da;
    });
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Blog list</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .card-img-top{height:160px;object-fit:cover}
    .sidebar { position: sticky; top: 96px; }
  </style>
</head>
<body>
<div class="container py-4" style="max-width:1100px;">
  <div class="row g-4">
    <main class="col-lg-8">
      <h1 class="mb-3">Blog</h1>
      <div class="row row-cols-1 row-cols-md-2 g-4" id="postsGrid">
<?php foreach ($posts as $p): ?>
        <article class="col">
          <div class="card h-100">
            <?php if ($p['image']): ?>
            <img src="<?php echo htmlspecialchars($p['image']); ?>" class="card-img-top" alt="">
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
              <small class="text-muted"><?php echo htmlspecialchars($p['date']); ?></small>
              <h5 class="card-title mt-1"><?php echo htmlspecialchars($p['title']); ?></h5>
              <p class="card-text"><?php echo htmlspecialchars(strip_tags($p['excerpt'])); ?></p>
              <div class="mt-auto">
                <a href="#" class="btn btn-sm btn-primary" data-filename="<?php echo htmlspecialchars($p['filename']); ?>">Read article</a>
              </div>
            </div>
          </div>
        </article>
<?php endforeach; ?>
      </div>
    </main>

    <aside class="col-lg-4">
      <div class="sidebar">
        <div class="mb-3">
          <label class="form-label">Search</label>
          <input id="searchInput" class="form-control" placeholder="Search posts...">
        </div>
        <div class="card p-3 mb-3">
          <h6>Recent posts</h6>
          <ul id="recentList" class="list-unstyled mb-0">
<?php foreach (array_slice($posts,0,5) as $r): ?>
            <li class="mb-2"><a href="#" class="recent-link" data-filename="<?php echo htmlspecialchars($r['filename']); ?>"><?php echo htmlspecialchars($r['title']); ?></a><br><small class="text-muted"><?php echo htmlspecialchars($r['date']); ?></small></li>
<?php endforeach; ?>
          </ul>
        </div>
      </div>
    </aside>
  </div>
</div>

<!-- Modal to show post -->
<div class="modal fade" id="postModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="modalTitle" class="modal-title"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <img id="modalImage" src="" alt="" style="max-width:100%; margin-bottom:12px; display:none;">
        <div id="modalMeta" class="text-muted mb-2"></div>
        <div id="modalContent"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const modalEl = document.getElementById('postModal');
  const bsModal = new bootstrap.Modal(modalEl);
  function fmtDate(d){ try{ return new Date(d).toLocaleDateString(); }catch(e){return d||'';} }

  async function openPostFile(filename){
    if (!filename) return alert('Post file not set');
    try {
      const res = await fetch('posts/' + filename, {cache:'no-cache'});
      if (!res.ok) throw new Error('Not found');
      const data = await res.json();
      document.getElementById('modalTitle').textContent = data.title || '';
      const img = data.image || '';
      const modalImage = document.getElementById('modalImage');
      if (img) { modalImage.src = img; modalImage.style.display = 'block'; } else modalImage.style.display = 'none';
      document.getElementById('modalMeta').textContent = (data.date ? fmtDate(data.date) + ' · ' : '') + ((data.categories||[]).join(', ')||'');
      document.getElementById('modalContent').innerHTML = data.content || data.excerpt || '<p>No content.</p>';
      bsModal.show();
    } catch (err) {
      console.error(err);
      alert('Could not load post: ' + filename);
    }
  }

  document.querySelectorAll('[data-filename]').forEach(el=>{
    el.addEventListener('click', e=>{
      e.preventDefault();
      openPostFile(el.getAttribute('data-filename'));
    });
  });
  document.querySelectorAll('.recent-link').forEach(el=>{
    el.addEventListener('click', e=>{
      e.preventDefault();
      openPostFile(el.getAttribute('data-filename'));
    });
  });

  // simple search (client-side)
  const search = document.getElementById('searchInput');
  if (search){
    search.addEventListener('input', ()=>{
      const q = search.value.trim().toLowerCase();
      document.querySelectorAll('#postsGrid article').forEach(a=>{
        const txt = a.textContent.toLowerCase();
        a.style.display = q && !txt.includes(q) ? 'none' : '';
      });
    });
  }
})();
</script>
</body>
</html>