document.addEventListener("DOMContentLoaded", function () {
  var accueil = document.getElementById("accueil");
  var nav = document.querySelector(".site-nav");
  var menuToggle = document.getElementById("menu-toggle");
  var body = document.body;

  if (!accueil || !nav || !menuToggle || !body) {
    return;
  }

  function updateMenuMode() {
    var accueilBottom = accueil.offsetTop + accueil.offsetHeight;
    var shouldCompact = window.scrollY >= accueilBottom;

    nav.classList.toggle("is-compact", shouldCompact);
    body.classList.toggle("nav-is-compact", shouldCompact);

    if (!shouldCompact) {
      menuToggle.checked = false;
    }
  }

  window.addEventListener("scroll", updateMenuMode, { passive: true });
  window.addEventListener("resize", updateMenuMode);

  document.addEventListener("click", function (event) {
    var target = event.target;
    if (target && target.closest(".nav-overlay a")) {
      menuToggle.checked = false;
    }
  });

  updateMenuMode();
});
