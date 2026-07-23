/**
 * Dashboard RSVP - Gestion du tableau et du filtrage
 */

let allData = [];

document.addEventListener("DOMContentLoaded", function () {
  // Charger les données au chargement de la page
  loadDashboardData();

  // Gérer la recherche en temps réel
  const searchInput = document.getElementById("search-input");
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      filterTable(this.value);
    });
  }
});

/**
 * Charger les données depuis l'API
 */
function loadDashboardData() {
  const loading = document.getElementById("loading");
  const error = document.getElementById("error");
  const tableWrapper = document.getElementById("table-wrapper");
  const noResults = document.getElementById("no-results");

  fetch("php/get-dashboard-data.php")
    .then((response) => {
      if (!response.ok) {
        if (response.status === 401) {
          throw new Error("Session expirée. Veuillez vous reconnecter.");
        }
        throw new Error("Erreur lors du chargement des données");
      }
      return response.json();
    })
    .then((result) => {
      if (result.ok) {
        allData = sortByFamille(result.data);
        updateTotals(result.totals);
        renderTable(allData);

        loading.style.display = "none";
        tableWrapper.style.display = "block";
        error.style.display = "none";
        noResults.style.display = "none";
      } else {
        throw new Error(result.message || "Erreur inconnue");
      }
    })
    .catch((err) => {
      console.error("Erreur:", err);
      loading.style.display = "none";
      tableWrapper.style.display = "none";
      error.style.display = "block";
      error.className = "error";
      error.textContent = "❌ " + err.message;

      // Recharger après 5 secondes ou rediriger si session expirée
      if (err.message.includes("Session expirée")) {
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      }
    });
}

/**
 * Mettre à jour les totaux
 */
function updateTotals(totals) {
  document.getElementById("total-familles").textContent = totals.total_familles;
  document.getElementById("total-confirmations-oui").textContent =
    totals.confirmations_oui;
  document.getElementById("total-confirmations-non").textContent =
    totals.confirmations_non;
  document.getElementById("total-confirmations-attente").textContent =
    totals.confirmations_attente;
  document.getElementById("total-taux-reponse").textContent =
    totals.taux_reponse;
  document.getElementById("total-personnes").textContent =
    totals.total_personnes;
  document.getElementById("total-ceremonie").textContent = totals.ceremonie_oui;
  document.getElementById("total-vin-honneur").textContent =
    totals.vin_honneur_oui;
  document.getElementById("total-repas").textContent = totals.repas_oui;
  document.getElementById("total-nuit").textContent = totals.nuit_oui;
}

/**
 * Rendre le tableau avec les données
 */
function renderTable(data) {
  const tbody = document.getElementById("table-body");
  const noResults = document.getElementById("no-results");
  const tableWrapper = document.getElementById("table-wrapper");

  if (data.length === 0) {
    tbody.innerHTML = "";
    tableWrapper.style.display = "none";
    noResults.style.display = "block";
    return;
  }

  tableWrapper.style.display = "block";
  noResults.style.display = "none";

  tbody.innerHTML = data
    .map(
      (row) => `
        <tr>
            <td><strong>${escapeHtml(row.famille)}</strong></td>
            <td><a href="mailto:${escapeHtml(row.email)}">${escapeHtml(row.email)}</a></td>
            <td>
                <span class="status-${getStatusClass(row.confirmation)}">
                    ${row.confirmation}
                </span>
            </td>
            <td>${escapeHtml(row.invites_list)}</td>
            <td>${row.ceremonie}</td>
            <td>${row.vin_honneur}</td>
            <td>${row.repas}</td>
            <td>${row.nuit}</td>
            <td>${formatDate(row.date_confirmation)}</td>
        </tr>
    `,
    )
    .join("");
}

/**
 * Filtrer le tableau en fonction de la recherche
 */
function filterTable(searchTerm) {
  const searchInput = document.getElementById("search-input");
  const noResults = document.getElementById("no-results");
  const tableWrapper = document.getElementById("table-wrapper");

  if (!searchTerm.trim()) {
    renderTable(allData);
    return;
  }

  const term = searchTerm
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");

  const filtered = allData.filter((row) => {
    const famille = row.famille
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "");

    return famille.includes(term);
  });

  renderTable(sortByFamille(filtered));
}

/**
 * Trier par colonne Famille en ordre alphabétique
 */
function sortByFamille(data) {
  return [...data].sort((a, b) => {
    return a.famille.localeCompare(b.famille, "fr");
  });
}

/**
 * Utilitaires
 */

function getStatusClass(status) {
  if (status === "Oui") return "oui";
  if (status === "Non") return "non";
  return "attente";
}

function formatDate(dateStr) {
  if (!dateStr) return "–";

  try {
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return "–";

    return date.toLocaleDateString("fr-FR", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    });
  } catch {
    return "–";
  }
}

function escapeHtml(text) {
  const map = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  };
  return text.replace(/[&<>"']/g, (m) => map[m]);
}

// Refresh automatique toutes les 5 minutes
setInterval(
  function () {
    loadDashboardData();
  },
  5 * 60 * 1000,
);
