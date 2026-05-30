/* ========================================
   AYKON TECH STORE — cart.js
   Panier : localStorage (sync) + API PHP (session)
   Stratégie : localStorage comme cache local,
   sync avec /api/cart à chaque action
   ======================================== */

const CART_KEY = 'aykon_cart';
const API_CART = '../backend/index.php/api/cart';

// ── LOCALSTORAGE (UI instantanée) ─────────
function getCart() {
  try { return JSON.parse(localStorage.getItem(CART_KEY) || '[]'); } catch { return []; }
}
function saveCartLocal(cart) {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  updateCartUI();
}

// ── API CALLS ─────────────────────────────

async function addToCart(product) {
  // Mise à jour locale immédiate pour UI réactive
  const cart     = getCart();
  const existing = cart.find(i => i.id === product.id);
  if (existing) {
    existing.qty = Math.min(existing.qty + 1, product.stock || 99);
    showToast(`Quantité mise à jour`, 'success');
  } else {
    cart.push({
      id:    product.id,
      name:  product.nom   || product.name,
      price: product.prix  || product.price,
      emoji: product.emoji || '📦',
      brand: product.marque || product.brand || '',
      stock: product.stock || 99,
      qty:   1,
    });
    showToast(`${product.nom || product.name} ajouté au panier`, 'success');
  }
  saveCartLocal(cart);

  // Sync avec la session PHP (best-effort)
  apiCall(API_CART, 'POST', {
    product_id: product.id,
    quantite:   1,
  }).catch(() => {});
}

async function removeFromCart(productId) {
  const cart = getCart().filter(i => i.id !== productId);
  saveCartLocal(cart);
  apiCall(`${API_CART}/${productId}`, 'DELETE').catch(() => {});
}

async function updateQty(productId, delta) {
  const cart = getCart();
  const item  = cart.find(i => i.id === productId);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) return removeFromCart(productId);
  saveCartLocal(cart);
  apiCall(`${API_CART}/${productId}`, 'PUT', { quantite: item.qty }).catch(() => {});
}

function clearCart() {
  localStorage.removeItem(CART_KEY);
  updateCartUI();
  apiCall(API_CART, 'DELETE').catch(() => {});
}

function getCartTotal() {
  return getCart().reduce((sum, i) => sum + i.price * i.qty, 0);
}
function getCartCount() {
  return getCart().reduce((sum, i) => sum + i.qty, 0);
}

// ── RENDER UI ─────────────────────────────
function updateCartUI() {
  const countEl = document.getElementById('cartCount');
  if (countEl) countEl.textContent = getCartCount();

  const cartItemsEl = document.getElementById('cartItems');
  if (!cartItemsEl) return;

  const cart = getCart();
  if (cart.length === 0) {
    cartItemsEl.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:40px 0;">Votre panier est vide</p>';
  } else {
    cartItemsEl.innerHTML = cart.map(item => `
      <div class="cart-item">
        <div style="width:64px;height:64px;background:var(--bg-card);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:1.8rem;flex-shrink:0;">
          ${item.emoji || '📦'}
        </div>
        <div class="cart-item-info">
          <div class="cart-item-brand">${item.brand || ''}</div>
          <div class="cart-item-name">${item.name}</div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;">
            <div class="cart-qty">
              <button class="qty-btn" onclick="updateQty(${item.id}, -1)">−</button>
              <span style="font-size:0.875rem;font-weight:600;">${item.qty}</span>
              <button class="qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
            </div>
            <div class="cart-item-price">${formatPrice(item.price * item.qty)}</div>
          </div>
        </div>
        <button onclick="removeFromCart(${item.id})" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1rem;padding:4px;align-self:flex-start;" title="Supprimer">✕</button>
      </div>
    `).join('');
  }

  const totalEl = document.getElementById('cartTotal');
  if (totalEl) totalEl.textContent = formatPrice(getCartTotal());
}

// ── SIDEBAR OPEN/CLOSE ────────────────────
function openCart() {
  document.getElementById('cartSidebar')?.classList.add('open');
  document.getElementById('overlay')?.classList.add('show');
}
function closeCart() {
  document.getElementById('cartSidebar')?.classList.remove('open');
  document.getElementById('overlay')?.classList.remove('show');
}

// ── INIT ──────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  updateCartUI();
  document.getElementById('cartToggle')?.addEventListener('click', openCart);
});