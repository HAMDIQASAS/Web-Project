/* ========================================
   SWEET HAVEN - MAIN APPLICATION
   ======================================== */

// ========================================
// AUTHENTICATION CHECK
// ========================================
const AUTH = {
    // Check if user is logged in (session-based via PHP)
    isLoggedIn: function() {
        // Check for session cookie indicator
        return document.cookie.includes('logged_in=true');
    },

    // Protect page - redirect to auth if not logged in
    requireAuth: function() {
        // Allow access to auth page without login
        const currentPage = window.location.pathname.split('/').pop() || 'home.html';
        if (currentPage === 'auth.html') return;

        if (!this.isLoggedIn()) {
            window.location.href = 'auth.html';
        }
    },

    // Logout user
    logout: async function() {
        try {
            const response = await fetch('php/auth.php?action=logout', {
                method: 'POST',
                credentials: 'include'
            });
            const data = await response.json();
            if (data.success) {
                // Clear client-side cookie indicator
                document.cookie = 'logged_in=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                window.location.href = 'auth.html';
            }
        } catch (error) {
            console.error('Logout error:', error);
            // Force redirect anyway
            document.cookie = 'logged_in=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            window.location.href = 'auth.html';
        }
    },

    // Get current user info
    getCurrentUser: async function() {
        try {
            const response = await fetch('php/auth.php?action=user', {
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Get user error:', error);
            return null;
        }
    }
};

// ========================================
// CART MANAGEMENT
// ========================================
const CART = {
    items: [],

    // Initialize cart from server
    init: async function() {
        try {
            const response = await fetch('php/cart.php?action=get', {
                credentials: 'include'
            });
            const data = await response.json();
            if (data.success) {
                this.items = data.items || [];
                this.updateBadge();
            }
        } catch (error) {
            console.error('Cart init error:', error);
            this.items = [];
        }
    },

    // Add item to cart
    add: async function(productId, quantity = 1) {
        try {
            const response = await fetch('php/cart.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ product_id: productId, quantity: quantity })
            });
            const data = await response.json();
            if (data.success) {
                await this.init(); // Refresh cart
                showToast('Item added to cart!', 'success');
            } else {
                showToast(data.error || 'Failed to add item', 'error');
            }
        } catch (error) {
            console.error('Add to cart error:', error);
            showToast('Failed to add item to cart', 'error');
        }
    },

    // Update item quantity
    update: async function(productId, quantity) {
        try {
            const response = await fetch('php/cart.php?action=update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ product_id: productId, quantity: quantity })
            });
            const data = await response.json();
            if (data.success) {
                await this.init();
            }
        } catch (error) {
            console.error('Update cart error:', error);
        }
    },

    // Remove item from cart
    remove: async function(productId) {
        try {
            const response = await fetch('php/cart.php?action=remove', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ product_id: productId })
            });
            const data = await response.json();
            if (data.success) {
                await this.init();
                showToast('Item removed from cart', 'success');
            }
        } catch (error) {
            console.error('Remove from cart error:', error);
        }
    },

    // Clear entire cart
    clear: async function() {
        try {
            const response = await fetch('php/cart.php?action=clear', {
                method: 'POST',
                credentials: 'include'
            });
            const data = await response.json();
            if (data.success) {
                this.items = [];
                this.updateBadge();
            }
        } catch (error) {
            console.error('Clear cart error:', error);
        }
    },

    // Get cart total
    getTotal: function() {
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    },

    // Get item count
    getCount: function() {
        return this.items.reduce((count, item) => count + item.quantity, 0);
    },

    // Update cart badge
    updateBadge: function() {
        const badges = document.querySelectorAll('.cart-badge');
        const count = this.getCount();
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    }
};

// ========================================
// PRODUCTS
// ========================================
const PRODUCTS = {
    items: [],

    // Fetch all products
    getAll: async function(category = '', search = '') {
        try {
            let url = 'php/products.php?action=list';
            if (category) url += '&category=' + encodeURIComponent(category);
            if (search) url += '&search=' + encodeURIComponent(search);

            const response = await fetch(url);
            const data = await response.json();
            if (data.success) {
                this.items = data.products || [];
                return this.items;
            }
            return [];
        } catch (error) {
            console.error('Get products error:', error);
            return [];
        }
    },

    // Get single product
    getById: async function(id) {
        try {
            const response = await fetch('php/products.php?action=get&id=' + encodeURIComponent(id));
            const data = await response.json();
            if (data.success) {
                return data.product;
            }
            return null;
        } catch (error) {
            console.error('Get product error:', error);
            return null;
        }
    },

    // Get categories
    getCategories: async function() {
        try {
            const response = await fetch('php/products.php?action=categories');
            const data = await response.json();
            if (data.success) {
                return data.categories || [];
            }
            return [];
        } catch (error) {
            console.error('Get categories error:', error);
            return [];
        }
    }
};

// ========================================
// UI HELPERS
// ========================================

// Toast notifications
function showToast(message, type = 'info') {
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
    <span>${message}</span>
    <button onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
  `;
    document.body.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Mobile navigation
function toggleMobileNav() {
    const nav = document.getElementById('mobile-nav');
    const btn = document.querySelector('.mobile-menu-btn');
    if (nav && btn) {
        nav.classList.toggle('open');
        btn.setAttribute('aria-expanded', nav.classList.contains('open'));
    }
}

function closeMobileNav() {
    const nav = document.getElementById('mobile-nav');
    const btn = document.querySelector('.mobile-menu-btn');
    if (nav && btn) {
        nav.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
    }
}

// Format currency
function formatPrice(price) {
    return '$' + parseFloat(price).toFixed(2);
}

function isLikelyImageUrl(url) {
    if (!url) return false;
    const u = String(url).trim();
    return /^https?:\/\//i.test(u) || u.startsWith('/') || u.startsWith('uploads/') || u.startsWith('./') || u.startsWith('../');
}

function escapeXml(str) {
    return String(str ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[c]));
}

function placeholderDataUri(label, size) {
    const s = Number(size) || 300;
    const name = escapeXml(label || 'Product');
    const fontSize = Math.max(12, Math.floor(s / 14));
    const brandSize = Math.max(10, Math.floor(s / 22));

    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${s}" height="${s}" viewBox="0 0 ${s} ${s}">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="hsl(240 14% 6%)"/>
      <stop offset="100%" stop-color="hsl(222 20% 14%)"/>
    </linearGradient>
  </defs>
  <rect width="100%" height="100%" rx="16" fill="url(#g)"/>
  <rect x="18" y="18" width="${s - 36}" height="${s - 36}" rx="14" fill="none" stroke="hsl(326 100% 50%)" stroke-opacity="0.55" stroke-width="2"/>
  <text x="50%" y="52%" dominant-baseline="middle" text-anchor="middle" fill="hsl(0 0% 100%)" font-family="system-ui, -apple-system, Segoe UI, Roboto" font-size="${fontSize}" opacity="0.9">${name}</text>
  <text x="50%" y="68%" dominant-baseline="middle" text-anchor="middle" fill="hsl(169 100% 50%)" font-family="system-ui, -apple-system, Segoe UI, Roboto" font-size="${brandSize}" opacity="0.85">KOOKIE</text>
</svg>`;

    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
}

function getImageUrl(url, label, size) {
    return isLikelyImageUrl(url) ? url : placeholderDataUri(label, size);
}

// Create product card HTML
function createProductCard(product) {
    const imageUrl = getImageUrl(product.image_url, product.name, 300);
    const stock = parseInt(product.stock) || 0;
    return `
    <article class="product-card" data-category="${product.category}">
      <a href="product.html?id=${product.id}" class="product-image">
        <img src="${imageUrl}" alt="${product.name}" loading="lazy">
        ${stock < 10 && stock > 0 ? '<span class="product-badge">Low Stock</span>' : ''}
        ${stock === 0 ? '<span class="product-badge sold-out">Sold Out</span>' : ''}
      </a>
      <div class="product-info">
        <span class="product-category">${product.category}</span>
        <h3><a href="product.html?id=${product.id}">${product.name}</a></h3>
        <p class="product-desc">${product.description || ''}</p>
        <div class="product-footer">
          <span class="product-price">${formatPrice(product.price)}</span>
          <button class="btn btn-primary btn-small" onclick="CART.add(${product.id})" ${stock === 0 ? 'disabled' : ''}>
            ${stock === 0 ? 'Sold Out' : 'Add to Cart'}
          </button>
        </div>
      </div>
    </article>
  `;
}

// ========================================
// PAGE-SPECIFIC INITIALIZATION
// ========================================

// Initialize shop page
async function initShopPage() {
    const grid = document.getElementById('products-grid');
    const searchInput = document.getElementById('search-input');
    const filterBtns = document.querySelectorAll('.filter-btn');

    if (!grid) return;

    let currentCategory = '';
    let searchTerm = '';

    async function loadProducts() {
        grid.innerHTML = '<div class="loading">Loading products...</div>';
        const products = await PRODUCTS.getAll(currentCategory, searchTerm);

        if (products.length === 0) {
            grid.innerHTML = '<div class="no-results">No products found</div>';
            return;
        }

        grid.innerHTML = products.map(createProductCard).join('');
    }

    // Search functionality
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchTerm = e.target.value;
                loadProducts();
            }, 300);
        });
    }

    // Category filter
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentCategory = btn.dataset.category || '';
            loadProducts();
        });
    });

    loadProducts();
}

// Initialize product detail page
async function initProductPage() {
    const container = document.getElementById('product-detail');
    if (!container) return;

    const params = new URLSearchParams(window.location.search);
    const productId = params.get('id');

    if (!productId) {
        container.innerHTML = '<div class="error">Product not found</div>';
        return;
    }

    container.innerHTML = '<div class="loading">Loading product...</div>';
    const product = await PRODUCTS.getById(productId);

    if (!product) {
        container.innerHTML = '<div class="error">Product not found</div>';
        return;
    }

    const imageUrl = getImageUrl(product.image_url, product.name, 500);
    const stock = parseInt(product.stock) || 0;

    container.innerHTML = `
    <div class="product-gallery">
      <img src="${imageUrl}" alt="${product.name}" class="product-main-image">
    </div>
    <div class="product-details">
      <span class="product-category">${product.category}</span>
      <h1>${product.name}</h1>
      <p class="product-price-large">${formatPrice(product.price)}</p>
      <p class="product-description">${product.description || 'No description available.'}</p>
      <div class="product-stock ${stock < 10 ? 'low' : ''}">
        ${stock > 0 ? `${stock} in stock` : 'Out of stock'}
      </div>
      <div class="product-actions">
        <div class="quantity-selector">
          <button type="button" onclick="decrementQty()" aria-label="Decrease quantity">-</button>
          <input type="number" id="product-qty" value="1" min="1" max="${stock}" onchange="validateQty(${stock})">
          <button type="button" onclick="incrementQty(${stock})" aria-label="Increase quantity">+</button>
        </div>
        <button class="btn btn-primary" onclick="addToCartFromDetail(${product.id})" ${stock === 0 ? 'disabled' : ''}>
          ${stock === 0 ? 'Sold Out' : 'Add to Cart'}
        </button>
      </div>
    </div>
  `;

    // Update page title
    document.title = `${product.name} - Sweet Haven`;
}

function incrementQty(max) {
    const input = document.getElementById('product-qty');
    if (input && parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
    }
}

function decrementQty() {
    const input = document.getElementById('product-qty');
    if (input && parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function validateQty(max) {
    const input = document.getElementById('product-qty');
    if (!input) return;
    let val = parseInt(input.value) || 1;
    if (val < 1) val = 1;
    if (val > max) val = max;
    input.value = val;
}

function addToCartFromDetail(productId) {
    const qty = parseInt(document.getElementById('product-qty').value) || 1;
    CART.add(productId, qty);
}

// Initialize cart page
async function initCartPage() {
    const container = document.getElementById('cart-items');
    const subtotalEl = document.getElementById('cart-subtotal');
    const shippingEl = document.getElementById('cart-shipping');
    const taxEl = document.getElementById('cart-tax');
    const totalEl = document.getElementById('cart-total');
    const checkoutBtn = document.getElementById('checkout-btn');

    if (!container) return;

    function renderCart() {
        const subtotal = CART.getTotal();
        const shipping = subtotal > 50 ? 0 : 5.99;
        const tax = subtotal * 0.08;
        const grandTotal = subtotal + shipping + tax;

        if (CART.items.length === 0) {
            container.innerHTML = `
        <div class="empty-cart">
          <p>Your cart is empty</p>
          <a href="shop.html" class="btn btn-primary">Continue Shopping</a>
        </div>
      `;
            if (subtotalEl) subtotalEl.textContent = formatPrice(0);
            if (shippingEl) shippingEl.textContent = formatPrice(5.99);
            if (taxEl) taxEl.textContent = formatPrice(0);
            if (totalEl) totalEl.textContent = formatPrice(0);
            if (checkoutBtn) checkoutBtn.disabled = true;
            return;
        }

        container.innerHTML = CART.items.map(item => `
      <div class="cart-item" data-id="${item.product_id}">
        <img src="${getImageUrl(item.image_url, item.name, 80)}" alt="${item.name}">
        <div class="cart-item-info">
          <h3>${item.name}</h3>
          <p class="cart-item-price">${formatPrice(item.price)}</p>
        </div>
        <div class="cart-item-qty">
          <button onclick="updateCartQty(${item.product_id}, ${item.quantity - 1})">-</button>
          <span>${item.quantity}</span>
          <button onclick="updateCartQty(${item.product_id}, ${item.quantity + 1})">+</button>
        </div>
        <p class="cart-item-total">${formatPrice(item.price * item.quantity)}</p>
        <button class="cart-remove" onclick="removeCartItem(${item.product_id})" aria-label="Remove item">&times;</button>
      </div>
    `).join('');

        if (subtotalEl) subtotalEl.textContent = formatPrice(subtotal);
        if (shippingEl) shippingEl.textContent = shipping === 0 ? 'FREE' : formatPrice(shipping);
        if (taxEl) taxEl.textContent = formatPrice(tax);
        if (totalEl) totalEl.textContent = formatPrice(grandTotal);
        if (checkoutBtn) checkoutBtn.disabled = false;
    }

    // Store render function for updates
    window.renderCart = renderCart;
    renderCart();
}

async function updateCartQty(productId, newQty) {
    if (newQty < 1) {
        await CART.remove(productId);
    } else {
        await CART.update(productId, newQty);
    }
    if (window.renderCart) window.renderCart();
}

async function removeCartItem(productId) {
    await CART.remove(productId);
    if (window.renderCart) window.renderCart();
}

// Initialize checkout page
async function initCheckoutPage() {
    const form = document.getElementById('checkout-form');
    const summaryContainer = document.getElementById('order-summary');
    const orderTotalEl = document.getElementById('order-total');

    if (!form) return;

    // Render order summary
    function renderSummary() {
        if (!summaryContainer) return;

        const subtotal = CART.getTotal();
        const shipping = subtotal > 50 ? 0 : 5.99;
        const tax = subtotal * 0.08;
        const grandTotal = subtotal + shipping + tax;

        summaryContainer.innerHTML = `
      ${CART.items.map(item => `
        <div class="summary-item">
          <span>${item.name} x ${item.quantity}</span>
          <span>${formatPrice(item.price * item.quantity)}</span>
        </div>
      `).join('')}
      <div class="summary-divider"></div>
      <div class="summary-item">
        <span>Subtotal</span>
        <span>${formatPrice(subtotal)}</span>
      </div>
      <div class="summary-item">
        <span>Shipping</span>
        <span>${shipping === 0 ? 'FREE' : formatPrice(shipping)}</span>
      </div>
      <div class="summary-item">
        <span>Tax (8%)</span>
        <span>${formatPrice(tax)}</span>
      </div>
    `;

        if (orderTotalEl) orderTotalEl.textContent = formatPrice(grandTotal);
    }

    renderSummary();

    // Handle form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const orderData = {
            shipping_address: formData.get('address'),
            shipping_city: formData.get('city'),
            shipping_zip: formData.get('zip'),
            shipping_country: formData.get('country')
        };

        try {
            const response = await fetch('php/orders.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(orderData)
            });

            const data = await response.json();
            if (data.success) {
                await CART.clear();
                showToast('Order placed successfully!', 'success');
                setTimeout(() => {
                    window.location.href = 'home.html';
                }, 2000);
            } else {
                showToast(data.error || 'Failed to place order', 'error');
            }
        } catch (error) {
            console.error('Checkout error:', error);
            showToast('Failed to place order', 'error');
        }
    });
}

// Initialize contact page
function initContactPage() {
    const form = document.getElementById('contact-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const response = await fetch('php/contact.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                showToast(data.message || 'Message sent successfully!', 'success');
                form.reset();
            } else {
                showToast(data.message || data.error || 'Failed to send message', 'error');
            }
        } catch (error) {
            console.error('Contact form error:', error);
            showToast('Failed to send message', 'error');
        }
    });
}

// Update user nav display
async function updateUserNav() {
    const userNav = document.getElementById('user-nav');
    if (!userNav) return;

    if (AUTH.isLoggedIn()) {
        const userData = await AUTH.getCurrentUser();
        let adminLink = '';

        // Check if user is admin
        try {
            const adminCheck = await fetch('php/admin.php?action=check_admin', {
                credentials: 'include'
            });
            const adminData = await adminCheck.json();
            if (adminData.success && adminData.is_admin) {
                adminLink = '<a href="admin.html" class="btn btn-outline btn-small" style="border-color: var(--magenta); color: var(--magenta);">Admin</a>';
            }
        } catch (e) {
            // Not admin or error, ignore
        }

        if (userData && userData.success && userData.user) {
            userNav.innerHTML = `
        <span class="user-greeting">Hi, ${userData.user.name || userData.user.email}</span>
        ${adminLink}
        <button class="btn btn-outline btn-small" onclick="AUTH.logout()">Logout</button>
      `;
        } else {
            userNav.innerHTML = `
        ${adminLink}
        <button class="btn btn-outline btn-small" onclick="AUTH.logout()">Logout</button>
      `;
        }
    } else {
        userNav.innerHTML = `
      <a href="auth.html" class="btn btn-outline btn-small">Sign In</a>
    `;
    }
}

// ========================================
// MAIN INITIALIZATION
// ========================================
document.addEventListener('DOMContentLoaded', async () => {
    // Check authentication for protected pages
    AUTH.requireAuth();

    // Initialize cart
    await CART.init();

    // Update user navigation
    await updateUserNav();

    // Page-specific initialization
    const page = window.location.pathname.split('/').pop() || 'home.html';

    switch(page) {
        case 'shop.html':
            await initShopPage();
            break;
        case 'product.html':
            await initProductPage();
            break;
        case 'cart.html':
            await initCartPage();
            break;
        case 'checkout.html':
            await initCheckoutPage();
            break;
        case 'contact.html':
            initContactPage();
            break;
    }
});
