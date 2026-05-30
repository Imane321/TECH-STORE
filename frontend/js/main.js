/* ========================================
   AYKON TECH STORE — main.js
   Navigation, Toast, Utils globaux
   ======================================== */

// ── BASE URL API ──────────────────────────
// Adapter selon l'emplacement du serveur PHP
// Si frontend/ et backend/ sont dans le même serveur :
const BASE_API = '../backend/index.php';

// ── TOGGLE MENU MOBILE ────────────────────
function toggleMenu() {
  const nav = document.getElementById('navLinks');
  if (nav) nav.classList.toggle('open');
}

// ── TOAST NOTIFICATIONS ───────────────────
function showToast(message, type = 'success', duration = 3000) {
  const container = document.getElementById('toastContainer');
  if (!container) return;
  const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<span>${icons[type] || icons.info}</span><span>${message}</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(8px)';
    toast.style.transition = '0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// ── PASSWORD TOGGLE ───────────────────────
function togglePassword() {
  const input = document.getElementById('password');
  if (!input) return;
  input.type = input.type === 'password' ? 'text' : 'password';
}

// ── URL PARAMS UTIL ───────────────────────
function getParam(key) {
  return new URLSearchParams(window.location.search).get(key);
}

// ── FORMAT PRIX ───────────────────────────
function formatPrice(price) {
  return new Intl.NumberFormat('fr-MA', { style: 'decimal' }).format(price) + ' MAD';
}

// ── STARS RENDER ──────────────────────────
function renderStars(rating) {
  const full  = Math.floor(rating);
  const half  = rating % 1 >= 0.5 ? 1 : 0;
  const empty = 5 - full - half;
  return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty);
}

// ── FETCH WRAPPER (apiCall) ───────────────
async function apiCall(url, method = 'GET', data = null) {
  const options = {
    method,
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',   // envoyer les cookies de session PHP
  };
  if (data && method !== 'GET') options.body = JSON.stringify(data);
  try {
    const res = await fetch(url, options);
    return await res.json();
  } catch (err) {
    console.error('API Error:', err);
    return { success: false, error: 'Erreur réseau' };
  }
}

// ── DEBOUNCE ──────────────────────────────
function debounce(fn, delay = 300) {
  let timer;
  return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
}

// ── LIVE SEARCH ───────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        const q = searchInput.value.trim();
        if (q) window.location.href = `products.html?q=${encodeURIComponent(q)}`;
      }
    });
  }
});
