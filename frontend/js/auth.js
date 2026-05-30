/* ========================================
   AYKON TECH STORE — auth.js
   Connexion réelle avec l'API PHP
   ======================================== */

const API = '../backend/index.php';

// ── HELPERS SESSION ──────────────────────
function getUser() {
  try { return JSON.parse(localStorage.getItem('user') || 'null'); } catch { return null; }
}
function setUser(user) {
  localStorage.setItem('user', JSON.stringify(user));
}
function isLoggedIn() { return !!getUser(); }
function isAdmin()    { return getUser()?.role === 'admin'; }

// ── LOGIN ────────────────────────────────
async function handleLogin(e) {
  e.preventDefault();
  const btn      = document.getElementById('loginBtn');
  const errorEl  = document.getElementById('loginError');
  const email    = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;

  btn.textContent = 'Connexion…'; btn.disabled = true;
  errorEl.style.display = 'none';

  const res = await apiCall(`${API}/api/auth/login`, 'POST', { email, mot_de_passe: password });

  if (res.success) {
    setUser(res.user);
    showToast('Connexion réussie !', 'success');
    setTimeout(() => {
      window.location.href = res.user.role === 'admin'
        ? 'pages/admin/dashboard.html'
        : 'index.html';
    }, 700);
  } else {
    errorEl.textContent = res.error || 'Email ou mot de passe incorrect';
    errorEl.style.display = 'block';
    btn.textContent = 'Se connecter'; btn.disabled = false;
  }
}

// ── REGISTER ─────────────────────────────
async function handleRegister(e) {
  e.preventDefault();
  const errorEl  = document.getElementById('registerError');
  const btn      = document.querySelector('#registerForm button[type="submit"]');
  const password = document.getElementById('password').value;
  const confirm  = document.getElementById('confirmPassword').value;

  errorEl.style.display = 'none';

  if (password !== confirm) {
    errorEl.textContent = 'Les mots de passe ne correspondent pas';
    errorEl.style.display = 'block';
    return;
  }

  btn.textContent = 'Création…'; btn.disabled = true;

  const res = await apiCall(`${API}/api/auth/register`, 'POST', {
    nom:          document.getElementById('lastName').value.trim(),
    prenom:       document.getElementById('firstName').value.trim(),
    email:        document.getElementById('email').value.trim(),
    telephone:    document.getElementById('phone')?.value.trim() || '',
    mot_de_passe: password,
  });

  if (res.success) {
    showToast('Compte créé avec succès !', 'success');
    setTimeout(() => { window.location.href = 'login.html'; }, 900);
  } else {
    errorEl.textContent = res.error || 'Erreur lors de la création du compte';
    errorEl.style.display = 'block';
    btn.textContent = "S'inscrire"; btn.disabled = false;
  }
}

// ── LOGOUT ───────────────────────────────
async function logout() {
  await apiCall(`${API}/api/auth/logout`, 'POST');
  localStorage.removeItem('user');
  window.location.href = 'login.html';
}
async function logout1() {
  await apiCall(`${API}/api/auth/logout`, 'POST');
  localStorage.removeItem('user');
  window.location.href = '../../login.html';
}

// ── NAVBAR : afficher user connecté ──────
function updateNavAuth() {
  const user    = getUser();
  const actions = document.querySelector('.nav-actions');
  if (!actions) return;

  // Remplacer les boutons Connexion/Inscription
  const loginBtn    = actions.querySelector('a[href="login.html"]');
  const registerBtn = actions.querySelector('a[href="register.html"]');

  if (user) {
    if (loginBtn)    loginBtn.remove();
    if (registerBtn) registerBtn.remove();

    // Ajouter dropdown user si pas déjà présent
    if (!document.getElementById('navUserMenu')) {
      const div = document.createElement('div');
      div.id        = 'navUserMenu';
      div.className = 'nav-user-menu';
      div.innerHTML = `
        <button class="btn-nav-auth btn-outline" onclick="toggleUserMenu()" style="display:flex;align-items:center;gap:6px;">
          👤 ${user.prenom || user.nom}
          <span style="font-size:0.7rem;opacity:0.6;">▾</span>
        </button>
        <div id="userDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);min-width:160px;padding:8px 0;z-index:999;box-shadow:0 8px 24px rgba(0,0,0,0.15);">
          <a href="profile.html"   style="display:block;padding:10px 16px;font-size:0.875rem;color:var(--text-secondary);text-decoration:none;transition:background .15s;" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">Mon profil</a>
          <a href="orders.html"    style="display:block;padding:10px 16px;font-size:0.875rem;color:var(--text-secondary);text-decoration:none;" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">Mes commandes</a>
          ${user.role === 'admin' ? `<a href="pages/admin/dashboard.html" style="display:block;padding:10px 16px;font-size:0.875rem;color:var(--accent);text-decoration:none;" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">Dashboard admin</a>` : ''}
          <hr style="border:none;border-top:1px solid var(--border);margin:4px 0;">
          <button onclick="logout()" style="width:100%;text-align:left;padding:10px 16px;font-size:0.875rem;color:var(--red, #e55);background:none;border:none;cursor:pointer;" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">Déconnexion</button>
        </div>
      `;
      div.style.position = 'relative';
      // Insérer avant le hamburger
      const ham = actions.querySelector('.hamburger');
      actions.insertBefore(div, ham || null);
    }
  }
}

function toggleUserMenu() {
  const dd = document.getElementById('userDropdown');
  if (!dd) return;
  dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}

// Fermer dropdown en cliquant ailleurs
document.addEventListener('click', (e) => {
  const menu = document.getElementById('navUserMenu');
  if (menu && !menu.contains(e.target)) {
    const dd = document.getElementById('userDropdown');
    if (dd) dd.style.display = 'none';
  }
});

// ── PROTECTION ROUTES ────────────────────
function requireAuth(redirectTo = 'login.html') {
  if (!isLoggedIn()) {
    window.location.href = redirectTo;
    return false;
  }
  return true;
}
function requireAdmin(redirectTo = 'index.html') {
  if (!isAdmin()) {
    window.location.href = redirectTo;
    return false;
  }
  return true;
}

// ── INIT ─────────────────────────────────
document.addEventListener('DOMContentLoaded', updateNavAuth);