document.addEventListener("DOMContentLoaded", function () {
  new fullpage("#fullpage", {
    licenseKey: "gplv3-license",
    autoScrolling: true,
    navigation: true,
    anchors: ["accueil", "infos", "contact"],
    scrollingSpeed: 700,
    responsiveWidth: 768,
  });
});
