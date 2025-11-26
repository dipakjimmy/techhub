// assets/js/footer.js
console.log("footer.js: loaded");

document.addEventListener("DOMContentLoaded", function () {
  console.log("footer.js: DOMContentLoaded");

  const placeholder = document.getElementById("footer-include");
  if (!placeholder) {
    console.error("footer.js: placeholder #footer-include not found in DOM");
    return;
  }

  // Adjust this path if your components folder lives elsewhere.
  const footerPath = "components/footer.html"; // <-- try "components/footer.html" first

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
    });
});
