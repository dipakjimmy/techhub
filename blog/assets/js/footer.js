// assets/js/footer.js
console.log("footer.js: loaded");

document.addEventListener("DOMContentLoaded", function () {
  console.log("footer.js: DOMContentLoaded");

  const placeholder = document.getElementById("footer-include");
  if (!placeholder) {
    console.error("footer.js: placeholder #footer-include not found in DOM");
    return;
  }

  // Get base path - use window.BASE_PATH if set, otherwise calculate from current location
  const basePath = window.BASE_PATH || (() => {
    const path = window.location.pathname;
    // If we're on a single post page (has a slug at the end), go back to blog root
    // Pattern: /techpub/blog/slug-name/ -> /techpub/blog/
    if (path.match(/\/techpub\/blog\/[^\/]+\/$/)) {
      return '/techpub/blog/';
    }
    // If path ends with a single segment (slug), remove it
    const parts = path.split('/').filter(p => p);
    if (parts.length > 0 && !path.endsWith('/index.html') && !path.endsWith('/index.php')) {
      // Check if last part looks like a slug (not a file)
      const lastPart = parts[parts.length - 1];
      if (lastPart && !lastPart.includes('.')) {
        // Remove the last part (slug) and return the base
        return '/' + parts.slice(0, -1).join('/') + '/';
      }
    }
    // Otherwise use the current directory
    return path.endsWith('/') ? path : path.substring(0, path.lastIndexOf('/') + 1);
  })();

  const footerPath = basePath + "components/footer.html";

  console.log("footer.js: fetching footer from:", footerPath);

  fetch(footerPath)
    .then(res => {
      console.log("footer.js: fetch response", res.status, res.ok);
      if (!res.ok) throw new Error("HTTP " + res.status);
      return res.text();
    })
    .then(html => {
      placeholder.innerHTML = html;
      console.log("footer.js: footer inserted");
    })
    .catch(err => {
      console.error("footer.js: failed to load footer:", err);
      placeholder.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Footer loading...</div>';
    });
});
