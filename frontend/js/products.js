/* ========================================
   AYKON TECH STORE — products.js
   Catalogue, filtres, recherche, pagination
   Connecté à l'API PHP /api/products
   ======================================== */

const API_PRODUCTS = '../backend/index.php/api/products';

let allProducts     = [];
let filteredProducts = [];
let currentPage     = 1;
const PER_PAGE      = 8;

// ── FETCH PRODUITS DEPUIS L'API ───────────
async function fetchProducts(params = {}) {
  const qs = new URLSearchParams();
  if (params.cat)    qs.set('cat',    params.cat);
  if (params.q)      qs.set('q',      params.q);
  if (params.marque) qs.set('marque', params.marque);
  if (params.sort)   qs.set('sort',   params.sort);

  const url = `${API_PRODUCTS}${qs.toString() ? '?' + qs : ''}`;
  try {
    const res = await fetch(url, { credentials: 'include' });
    const data = await res.json();
    return data.success ? data.data : [];
  } catch {
    console.error('Erreur fetch produits');
    return [];
  }
}

// ── RENDER PRODUCT CARD ──────────────────
function renderProductCard(p) {
  // Normaliser les champs (API PHP vs mock)
  const id       = p.id;
  const name     = p.nom   || p.name;
  const brand    = p.marque || p.brand || '';
  const price    = parseFloat(p.prix  || p.price);
  const oldPrice = p.prix_ancien ? parseFloat(p.prix_ancien) : (p.oldPrice || null);
  const emoji    = p.emoji || '📦';
  const badge    = p.badge || null;
  const stock    = p.stock ?? 99;
  const rating   = p.rating || null;
  const reviews  = p.reviews || null;

  const discount = oldPrice ? Math.round((1 - price / oldPrice) * 100) : null;
  const badgeLabel = badge === 'new' ? 'Nouveau' : badge === 'sale' ? `−${discount}%` : badge === 'hot' ? 'Hot' : '';

  return `
    <div class="product-card" onclick="window.location.href='product-detail.html?id=${id}'">
      <div class="product-img">
        <div class="product-img-placeholder">${emoji}</div>
        ${badge ? `<div class="product-badge badge-${badge}">${badgeLabel}</div>` : ''}
        <div class="product-wishlist" onclick="event.stopPropagation();addWishlist(${id})">♡</div>
      </div>
      <div class="product-info">
        <div class="product-brand">${brand}</div>
        <div class="product-name">${name}</div>
        ${rating ? `
        <div class="product-rating">
          <span class="stars">${renderStars(rating)}</span>
          ${reviews ? `<span class="rating-count">(${reviews})</span>` : ''}
        </div>` : ''}
        <div class="product-footer">
          <div class="product-price">
            <span class="price-current">${formatPrice(price)}</span>
            ${oldPrice ? `<span class="price-old">${formatPrice(oldPrice)}</span>` : ''}
            ${discount ? `<div class="price-discount">Économie: ${formatPrice(oldPrice - price)}</div>` : ''}
          </div>
          <button class="add-cart-btn" onclick="event.stopPropagation();addToCart(${JSON.stringify({id,nom:name,prix:price,emoji,marque:brand,stock}).replace(/"/g,'&quot;')})" title="Ajouter au panier">+</button>
        </div>
      </div>
    </div>
  `;
}

// ── APPLY FILTERS (client-side sur données chargées) ──
function applyFilters() {
  const cat       = document.querySelector('input[name="cat"]:checked')?.value || '';
  const brands    = [...document.querySelectorAll('#brandFilters input:checked')].map(i => i.value);
  const maxPrice  = parseInt(document.getElementById('priceRange')?.value || '999999');
  const inStock   = document.getElementById('in-stock')?.checked;
  const sort      = document.getElementById('sortSelect')?.value || 'popular';
  const q         = getParam('q') || document.getElementById('searchInput')?.value?.toLowerCase() || '';

  let results = allProducts.filter(p => {
    const name  = (p.nom || p.name || '').toLowerCase();
    const brand = (p.marque || p.brand || '').toLowerCase();
    const pCat  = p.categorie_slug || p.category || '';
    const price = parseFloat(p.prix || p.price);

    if (cat && pCat !== cat) return false;
    if (brands.length && !brands.includes(p.marque || p.brand)) return false;
    if (price > maxPrice) return false;
    if (inStock && (p.stock ?? 1) < 1) return false;
    if (q && !name.includes(q) && !brand.includes(q)) return false;
    return true;
  });

  if (sort === 'price-asc')  results.sort((a,b) => parseFloat(a.prix||a.price) - parseFloat(b.prix||b.price));
  else if (sort === 'price-desc') results.sort((a,b) => parseFloat(b.prix||b.price) - parseFloat(a.prix||a.price));
  else if (sort === 'newest')     results.sort((a,b) => b.id - a.id);

  filteredProducts = results;
  currentPage = 1;
  renderProductsPage();
}

// ── RENDER PAGE ──────────────────────────
function renderProductsPage() {
  const grid    = document.getElementById('productsGrid');
  const empty   = document.getElementById('emptyState');
  const countEl = document.getElementById('resultsCount');

  if (!grid) return;

  if (countEl) countEl.textContent =
    `${filteredProducts.length} produit${filteredProducts.length > 1 ? 's' : ''} trouvé${filteredProducts.length > 1 ? 's' : ''}`;

  if (filteredProducts.length === 0) {
    grid.innerHTML = '';
    if (empty) empty.style.display = 'block';
    renderPagination();
    return;
  }
  if (empty) empty.style.display = 'none';

  const start     = (currentPage - 1) * PER_PAGE;
  const paginated = filteredProducts.slice(start, start + PER_PAGE);
  grid.innerHTML  = paginated.map(renderProductCard).join('');
  renderPagination();
}

// ── PAGINATION ───────────────────────────
function renderPagination() {
  const el = document.getElementById('pagination');
  if (!el) return;
  const total = Math.ceil(filteredProducts.length / PER_PAGE);
  if (total <= 1) { el.innerHTML = ''; return; }

  el.innerHTML = Array.from({ length: total }, (_, i) => i + 1).map(i => {
    const active = i === currentPage;
    return `<button onclick="goToPage(${i})" style="
      width:36px;height:36px;border-radius:var(--radius-sm);
      border:1px solid ${active ? 'var(--accent)' : 'var(--border)'};
      background:${active ? 'var(--accent)' : 'var(--bg-card)'};
      color:${active ? 'var(--bg-primary)' : 'var(--text-secondary)'};
      cursor:pointer;font-family:var(--font-body);font-size:0.875rem;">${i}</button>`;
  }).join('');
}

function goToPage(n) {
  currentPage = n;
  renderProductsPage();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── PRICE FILTER ─────────────────────────
function updatePrice(value) {
  const label = document.getElementById('priceLabel');
  if (label) label.textContent = formatPrice(parseInt(value));
  applyFilters();
}

function resetFilters() {
  const firstCat = document.querySelectorAll('input[name="cat"]')[0];
  if (firstCat) firstCat.checked = true;
  document.querySelectorAll('#brandFilters input').forEach(i => i.checked = false);
  const range = document.getElementById('priceRange');
  if (range) { range.value = range.max; updatePrice(range.max); }
  const stock = document.getElementById('in-stock');
  if (stock) stock.checked = false;
  applyFilters();
}

function addWishlist(id) { showToast('Produit ajouté aux favoris', 'info'); }

// ── LOADING STATE ─────────────────────────
function showGridLoader(gridId) {
  const grid = document.getElementById(gridId);
  if (!grid) return;
  grid.innerHTML = Array(4).fill(`
    <div class="product-card" style="opacity:0.4;pointer-events:none;">
      <div class="product-img"><div class="product-img-placeholder" style="background:var(--bg-hover);">⏳</div></div>
      <div class="product-info">
        <div style="height:12px;background:var(--bg-hover);border-radius:4px;margin-bottom:8px;"></div>
        <div style="height:16px;background:var(--bg-hover);border-radius:4px;width:60%;"></div>
      </div>
    </div>
  `).join('');
}

// ── INIT ─────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  const cat = getParam('cat');
  const q   = getParam('q');

  // Mettre à jour le titre de la page catalogue
  if (cat) {
    const catLabels = { pc: 'Ordinateurs PC', phones: 'Téléphones', accessories: 'Accessoires' };
    const radio = document.querySelector(`input[name="cat"][value="${cat}"]`);
    if (radio) radio.checked = true;
    const titleEl = document.getElementById('pageTitle');
    const tagEl   = document.getElementById('pageCatTag');
    if (titleEl && catLabels[cat]) titleEl.textContent = catLabels[cat];
    if (tagEl   && catLabels[cat]) tagEl.textContent   = catLabels[cat];
  }
  if (q) {
    const si = document.getElementById('searchInput');
    if (si) si.value = q;
  }

  // Page d'accueil : produits en vedette
  const featuredEl = document.getElementById('featuredProducts');
  if (featuredEl) {
    showGridLoader('featuredProducts');
    const params = {};
    const featured = await fetchProducts(params);
    allProducts = featured;
    const withBadge = featured.filter(p => p.badge).slice(0, 8);
    featuredEl.innerHTML = (withBadge.length ? withBadge : featured.slice(0,8)).map(renderProductCard).join('');
  }

  // Page catalogue
  if (document.getElementById('productsGrid')) {
    showGridLoader('productsGrid');
    allProducts = await fetchProducts({ cat, q });
    filteredProducts = [...allProducts];
    applyFilters();
  }
});