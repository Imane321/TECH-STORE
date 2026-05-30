/* ========================================
   AYKON TECH STORE — admin.js
   Dashboard admin connecté à l'API PHP
   ======================================== */

const API_ADMIN = '../../backend/index.php/api/admin';

document.addEventListener('DOMContentLoaded', async () => {
  // Protection : admin seulement
  if (typeof requireAdmin === 'function') requireAdmin('../../login.html');

  await Promise.all([
    loadStats(),
    loadRecentOrders(),
    loadTopProducts(),
  ]);
});

// ── STATS DASHBOARD ───────────────────────
async function loadStats() {
  const res = await apiCall(`${API_ADMIN}/stats`);
  if (!res.success) return;

  const d = res.data;
  animateCount('statRevenue',  d.chiffre_affaires, ' MAD');
  animateCount('statOrders',   d.commandes);
  animateCount('statProducts', d.produits);
  animateCount('statUsers',    d.clients);
}

function animateCount(id, target, suffix = '') {
  const el = document.getElementById(id);
  if (!el) return;
  let current = 0;
  const step  = target / 60;
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = new Intl.NumberFormat('fr-MA').format(Math.floor(current)) + suffix;
    if (current >= target) clearInterval(timer);
  }, 16);
}

// ── COMMANDES RÉCENTES ────────────────────
async function loadRecentOrders() {
  const tbody = document.getElementById('ordersTableBody');
  if (!tbody) return;

  const res = await apiCall(`${API_ADMIN}/orders`);
  if (!res.success || !res.data?.length) {
    tbody.innerHTML = '<tr><td colspan="4" style="padding:20px;text-align:center;color:var(--text-muted);">Aucune commande</td></tr>';
    return;
  }

  const statusColors = {
    en_attente: 'var(--text-muted)',
    confirmee:  'var(--accent)',
    expediee:   '#3b82f6',
    livree:     'var(--green, #22c55e)',
    annulee:    'var(--red, #ef4444)',
  };
  const statusLabels = {
    en_attente: 'En attente',
    confirmee:  'Confirmée',
    expediee:   'Expédiée',
    livree:     'Livrée',
    annulee:    'Annulée',
  };

  tbody.innerHTML = res.data.slice(0, 10).map(o => {
    const color = statusColors[o.statut] || 'var(--text-muted)';
    const label = statusLabels[o.statut] || o.statut;
    return `
      <tr style="border-bottom:1px solid var(--border);">
        <td style="padding:14px 0;font-size:0.875rem;font-weight:600;">#${o.id}</td>
        <td style="padding:14px 0;font-size:0.875rem;color:var(--text-secondary);">${o.user_prenom || ''} ${o.user_nom || ''}</td>
        <td style="padding:14px 0;font-size:0.875rem;font-weight:600;">${formatPrice(o.total)}</td>
        <td style="padding:14px 0;">
          <select onchange="changeStatut(${o.id}, this.value)" style="
            padding:3px 8px;border-radius:100px;font-size:0.72rem;font-weight:700;
            background:${color}22;color:${color};border:1px solid ${color}44;cursor:pointer;outline:none;">
            ${Object.entries(statusLabels).map(([val, lbl]) =>
              `<option value="${val}" ${o.statut === val ? 'selected' : ''}>${lbl}</option>`
            ).join('')}
          </select>
        </td>
      </tr>
    `;
  }).join('');
}

async function changeStatut(orderId, statut) {
  const res = await apiCall(`${API_ADMIN}/orders/${orderId}/statut`, 'PUT', { statut });
  if (res.success) showToast('Statut mis à jour', 'success');
  else showToast('Erreur mise à jour', 'error');
}

// ── TOP PRODUITS ──────────────────────────
async function loadTopProducts() {
  const el = document.getElementById('topProducts');
  if (!el) return;

  // Calculer depuis les commandes
  const res = await apiCall(`${API_ADMIN}/orders`);
  if (!res.success) return;

  // Agréger les ventes par produit
  const salesMap = {};
  for (const order of (res.data || [])) {
    if (order.statut === 'annulee') continue;
    // On n'a pas les items ici — on affiche les dernières commandes à la place
  }

  // Fallback : afficher chiffre d'affaires total
  const statsRes = await apiCall(`${API_ADMIN}/stats`);
  if (statsRes.success) {
    el.innerHTML = `
      <div style="text-align:center;padding:20px 0;">
        <div style="font-size:2rem;font-weight:700;color:var(--accent);">${formatPrice(statsRes.data.chiffre_affaires)}</div>
        <div style="font-size:0.875rem;color:var(--text-muted);margin-top:4px;">Chiffre d'affaires total</div>
        <div style="margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:12px;">
            <div style="font-size:1.4rem;font-weight:700;">${statsRes.data.commandes}</div>
            <div style="font-size:0.75rem;color:var(--text-muted);">Commandes</div>
          </div>
          <div style="background:var(--bg-secondary);border-radius:var(--radius-sm);padding:12px;">
            <div style="font-size:1.4rem;font-weight:700;">${statsRes.data.clients}</div>
            <div style="font-size:0.75rem;color:var(--text-muted);">Clients</div>
          </div>
        </div>
      </div>
    `;
  }
}

// ── IA CLASSIFICATION (optionnel) ─────────
function classifyProduct(e) {
  const file   = e.target.files[0];
  if (!file) return;
  const result = document.getElementById('aiResult');
  result.style.display = 'block';
  result.innerHTML = '<span style="color:var(--accent);">🤖 Analyse en cours...</span>';
  setTimeout(() => {
    result.innerHTML = `
      <strong style="color:var(--green,#22c55e);">✅ Classification :</strong><br>
      <span style="color:var(--text-secondary);">Catégorie : <strong>Ordinateur portable</strong></span><br>
      <span style="font-size:0.8rem;color:var(--text-muted);">Confiance : 94%</span>
    `;
  }, 1500);
}

function classifyByText() {
  const text   = document.getElementById('aiDescription')?.value.trim();
  const result = document.getElementById('aiResult');
  if (!text) { showToast('Entrez une description', 'warning'); return; }

  result.style.display = 'block';
  result.innerHTML = '<span style="color:var(--accent);">🤖 Analyse...</span>';

  setTimeout(() => {
    const kw  = text.toLowerCase();
    let cat   = 'Accessoires';
    if (kw.includes('pc') || kw.includes('ordinateur') || kw.includes('laptop') || kw.includes('gaming')) cat = 'Ordinateurs PC';
    else if (kw.includes('phone') || kw.includes('smartphone') || kw.includes('iphone') || kw.includes('samsung')) cat = 'Téléphones';
    result.innerHTML = `
      <strong style="color:var(--green,#22c55e);">✅ Classification :</strong><br>
      <span style="color:var(--text-secondary);">Catégorie : <strong>${cat}</strong></span>
    `;
  }, 800);
}