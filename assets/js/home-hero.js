
  // Load home-hero feature
  fetch("components/home-hero.html")
    .then(res => res.text())
    .then(html => {
      document.getElementById("home-hero-include").innerHTML = html;
      // initialize nav behavior after injection
      initNavActive();
    });
