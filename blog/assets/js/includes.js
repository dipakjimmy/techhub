document.addEventListener("DOMContentLoaded", () => {
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

  // Load DESKTOP topbar
  const topbarEl = document.getElementById("header-topbar-include");
  if (topbarEl) {
    fetch(basePath + "components/header-topbar.html")
      .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.text();
      })
      .then(html => {
        topbarEl.innerHTML = html;
        // initialize nav behavior after injection
        initNavActive();
      })
      .catch(err => {
        console.error("Failed to load header-topbar:", err);
        topbarEl.innerHTML = '<div style="padding: 20px; text-align: center;">Navigation loading...</div>';
      });
  }

  // Load MOBILE HEADER
  const mobileEl = document.getElementById("header-mobile-include");
  if (mobileEl) {
    fetch(basePath + "components/header-mobile.html")
      .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.text();
      })
      .then(html => {
        mobileEl.innerHTML = html;
        initMobileHeader(); // activate mobile menu
        // also init nav behavior for mobile header
        initNavActive();
      })
      .catch(err => {
        console.error("Failed to load header-mobile:", err);
        mobileEl.innerHTML = '<div style="padding: 10px; text-align: center; font-size: 14px;">Mobile menu loading...</div>';
      });
  }

});

// Mobile nav script (unchanged except small close-on-click addition)
function initMobileHeader() {
  const toggle = document.getElementById("menuToggle");
  const nav = document.getElementById("mainNav");

  if (!toggle || !nav) return;

  toggle.addEventListener("click", () => {
    toggle.classList.toggle("active");
    nav.classList.toggle("show");
  });
}

/* ---------- NEW: Active-nav + persistence ---------- */
function initNavActive() {
  const nav = document.getElementById("mainNav");
  if (!nav) return;

  // Use event delegation so it works even if <ul>/<li> structure changes
  nav.addEventListener("click", (e) => {
    const a = e.target.closest && e.target.closest("a");
    if (!a || !nav.contains(a)) return;

    // set active class locally (for immediate feedback)
    setActiveLink(a);

    // persist selection (store href relative to site)
    const href = a.getAttribute("href");
    try { localStorage.setItem("activeNavHref", href); } catch (err) { /* ignore storage errors */ }

    // if mobile menu is open, close it (nice UX)
    const toggle = document.getElementById("menuToggle");
    if (toggle && toggle.classList.contains("active")) {
      toggle.classList.remove("active");
      nav.classList.remove("show");
    }

    // Let the link navigate normally (do not preventDefault).
    // If you handle navigation via JS (SPA), you'd call e.preventDefault() and route manually.
  });

  // On load, restore one of:
  // 1) match current location (prefer this),
  // 2) saved href in localStorage,
  // 3) fallback: first nav item.
  restoreActiveLink();
  // Keep active in sync with back/forward navigation
  window.addEventListener("popstate", restoreActiveLink);
  window.addEventListener("hashchange", restoreActiveLink);
}

function setActiveLink(anchorElement) {
  // remove active from any other anchor inside nav
  const nav = document.getElementById("mainNav");
  if (!nav) return;
  nav.querySelectorAll("a.active").forEach(x => x.classList.remove("active"));
  anchorElement.classList.add("active");
}

function restoreActiveLink() {
  const nav = document.getElementById("mainNav");
  if (!nav) return;

  // try matching current URL first (best for link navigation)
  const current = normalizeHref(location.pathname + location.search + location.hash);
  let match = Array.from(nav.querySelectorAll("a")).find(a => {
    const h = normalizeHref(a.getAttribute("href"));
    // match either exact pathname/hash or match last segment (index vs /)
    return h === current || (h && current.endsWith(h)) || (h === '/' && (current === '/index.html' || current === '/'));
  });

  // fallback to saved href in localStorage
  if (!match) {
    const saved = (function(){
      try { return localStorage.getItem("activeNavHref"); } catch (err) { return null; }
    })();
    if (saved) {
      match = Array.from(nav.querySelectorAll("a")).find(a => normalizeHref(a.getAttribute("href")) === normalizeHref(saved));
    }
  }

  // final fallback: first link
  if (!match) match = nav.querySelector("a");

  if (match) setActiveLink(match);
}

// Normalize hrefs so comparisons are consistent
function normalizeHref(href) {
  if (!href) return "";
  // remove origin if present, remove leading ./ and resolve index.html vs /
  try {
    // if href is absolute, convert to relative path+hash
    const url = new URL(href, location.origin);
    let path = url.pathname || "/";
    if (path === "/") {
      // keep as "/"
    } else {
      // strip trailing slash for consistent comparisons
      path = path.replace(/\/$/, "");
    }
    // include hash if present
    if (url.hash) path += url.hash;
    return path + (url.search || "");
  } catch (e) {
    // on invalid URL (like just "#section") return as-is
    return href.replace(/^\.\//, "");
  }
}

  // Load home-hero feature
  fetch("components/home-hero.html")
    .then(res => res.text())
    .then(html => {
      document.getElementById("home-hero-include").innerHTML = html;
      // initialize nav behavior after injection
      initNavActive();
    });
