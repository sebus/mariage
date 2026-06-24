document.addEventListener("DOMContentLoaded", function () {
  new fullpage("#fullpage", {
    autoScrolling: true,
    navigation: true,
    anchors: ["accueil", "infos", "contact"],
    scrollingSpeed: 700,
    responsiveWidth: 768,
  });
});
