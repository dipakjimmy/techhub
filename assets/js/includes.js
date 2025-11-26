document.addEventListener("DOMContentLoaded", () => {

  // Load DESKTOP topbar
  fetch("components/header-topbar.html")
    .then(res => res.text())
    .then(html => {
      document.getElementById("header-topbar-include").innerHTML = html;
      // initialize nav behavior after injection
      initNavActive();
    });

  // Load MOBILE HEADER
  fetch("components/header-mobile.html")
    .then(res => res.text())
    .then(html => {
      document.getElementById("header-mobile-include").innerHTML = html;
      initMobileHeader(); // activate mobile menu
      // also init nav behavior for mobile header
      initNavActive();
    });

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


// Load softwares-carousal
  fetch("components/softwares-carousal.html")
    .then(res => res.text())
    .then(html => {
      document.getElementById("softwares-carousal-include").innerHTML = html;
      // initialize nav behavior after injection
      initNavActive();
    });
