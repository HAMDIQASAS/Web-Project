/*
// Product Catalog
const products = [
    {
        id: 1,
        name: "Dark Belgian Truffles",
        category: "Chocolates",
        price: 24.99,
        description: "Handcrafted dark chocolate truffles made with premium Belgian cocoa. Each piece is a perfect blend of rich, velvety chocolate with a smooth ganache center that melts in your mouth.",
        tags: ["Premium", "Handmade", "Belgian"],
        images: ["chocolate-1.jpg", "chocolate-1-alt.jpg"],
        popular: true,
        sugarFree: false
    },
    {
        id: 2,
        name: "Rainbow Gummy Bears",
        category: "Gummies",
        price: 8.99,
        description: "A colorful assortment of fruity gummy bears in six delicious flavors. Made with real fruit juice for an authentic taste experience.",
        tags: ["Fruity", "Colorful", "Classic"],
        images: ["gummy-1.jpg"],
        popular: true,
        sugarFree: false
    },
    {
        id: 3,
        name: "Sugar-Free Mint Chocolates",
        category: "Sugar-Free",
        price: 18.99,
        description: "Refreshing mint chocolates without the guilt. Made with natural stevia and premium dark chocolate for a satisfying treat.",
        tags: ["Sugar-Free", "Mint", "Dark Chocolate"],
        images: ["sugarfree-1.jpg"],
        popular: false,
        sugarFree: true
    },
    {
        id: 4,
        name: "Caramel Nougat Bar",
        category: "Candy Bars",
        price: 4.99,
        description: "Chewy nougat layered with smooth caramel and coated in milk chocolate. A perfect balance of textures and flavors.",
        tags: ["Caramel", "Nougat", "Milk Chocolate"],
        images: ["bar-1.jpg"],
        popular: true,
        sugarFree: false
    },
    {
        id: 5,
        name: "Sour Worm Extravaganza",
        category: "Gummies",
        price: 9.99,
        description: "Extra sour, extra long gummy worms dusted with tangy sugar crystals. For those who love an extreme sour kick!",
        tags: ["Sour", "Fun", "Tangy"],
        images: ["gummy-2.jpg"],
        popular: false,
        sugarFree: false
    },
    {
        id: 6,
        name: "Hazelnut Praline Collection",
        category: "Chocolates",
        price: 32.99,
        description: "An exquisite collection of hazelnut pralines crafted by master chocolatiers. Each piece features roasted hazelnuts wrapped in layers of chocolate.",
        tags: ["Premium", "Hazelnut", "Gift Box"],
        images: ["chocolate-2.jpg"],
        popular: true,
        sugarFree: false
    },
    {
        id: 7,
        name: "Keto Chocolate Squares",
        category: "Sugar-Free",
        price: 15.99,
        description: "Keto-friendly dark chocolate squares sweetened with erythritol. Rich chocolate flavor with only 1g net carbs per serving.",
        tags: ["Keto", "Sugar-Free", "Low Carb"],
        images: ["sugarfree-2.jpg"],
        popular: false,
        sugarFree: true
    },
    {
        id: 8,
        name: "Peanut Butter Crunch Bar",
        category: "Candy Bars",
        price: 5.49,
        description: "Crispy peanut butter center covered in rich milk chocolate. The ultimate combination for peanut butter lovers.",
        tags: ["Peanut Butter", "Crunchy", "Classic"],
        images: ["bar-2.jpg"],
        popular: true,
        sugarFree: false
    },
    {
        id: 9,
        name: "Tropical Fruit Gummies",
        category: "Gummies",
        price: 10.99,
        description: "Exotic fruit flavors including mango, passion fruit, pineapple, and papaya. A tropical vacation in every bite.",
        tags: ["Tropical", "Fruity", "Exotic"],
        images: ["gummy-3.jpg"],
        popular: false,
        sugarFree: false
    },
    {
        id: 10,
        name: "Salted Caramel Bonbons",
        category: "Chocolates",
        price: 28.99,
        description: "Luxurious bonbons filled with salted caramel and enrobed in dark chocolate. The perfect sweet-salty balance.",
        tags: ["Salted Caramel", "Premium", "Dark Chocolate"],
        images: ["chocolate-3.jpg"],
        popular: true,
        sugarFree: false
    },
    {
        id: 11,
        name: "Sugar-Free Fruit Drops",
        category: "Sugar-Free",
        price: 12.99,
        description: "Classic hard candy fruit drops made without sugar. Enjoy classic flavors like cherry, lemon, and orange guilt-free.",
        tags: ["Sugar-Free", "Hard Candy", "Fruity"],
        images: ["sugarfree-3.jpg"],
        popular: false,
        sugarFree: true
    },
    {
        id: 12,
        name: "Triple Layer Chocolate Bar",
        category: "Candy Bars",
        price: 6.99,
        description: "Three layers of chocolate heaven: dark, milk, and white chocolate stacked together for the ultimate chocolate experience.",
        tags: ["Triple Chocolate", "Layered", "Premium"],
        images: ["bar-3.jpg"],
        popular: false,
        sugarFree: false
    }
];

// Cart Management
const cart = {
    items: [],

    init() {
        const stored = localStorage.getItem('sweetstore_cart');
        if (stored) {
            this.items = JSON.parse(stored);
        }
        this.updateBadge();
    },

    save() {
        localStorage.setItem('sweetstore_cart', JSON.stringify(this.items));
        this.updateBadge();
    },

    add(productId, quantity = 1) {
        const product = products.find(p => p.id === productId);
        if (!product) return;

        const existing = this.items.find(item => item.id === productId);
        if (existing) {
            existing.quantity += quantity;
        } else {
            this.items.push({
                id: product.id,
                name: product.name,
                price: product.price,
                category: product.category,
                quantity: quantity
            });
        }

        this.save();
        this.showNotification(`${product.name} added to cart!`);
    },

    remove(productId) {
        this.items = this.items.filter(item => item.id !== productId);
        this.save();
    },

    updateQuantity(productId, quantity) {
        const item = this.items.find(item => item.id === productId);
        if (item) {
            item.quantity = Math.max(1, quantity);
            this.save();
        }
    },

    getTotal() {
        return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    },

    getCount() {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    },

    clear() {
        this.items = [];
        this.save();
    },

    updateBadge() {
        const badges = document.querySelectorAll('.cart-badge');
        const count = this.getCount();
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    },

    showNotification(message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'cart-notification';
        notification.innerHTML = `
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
        <polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
      <span>${message}</span>
    `;

        // Add styles
        notification.style.cssText = `
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      background: #111;
      border: 1px solid #00ffd6;
      color: #00ffd6;
      padding: 1rem 1.5rem;
      border-radius: 4px;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      z-index: 9999;
      animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s;
      box-shadow: 0 0 20px rgba(0, 255, 214, 0.3);
    `;

        document.body.appendChild(notification);

        setTimeout(() => notification.remove(), 3000);
    }
};

// Contact Messages Storage
const contactStorage = {
    save(message) {
        const messages = this.getAll();
        messages.push({
            ...message,
            id: Date.now(),
            date: new Date().toISOString()
        });
        localStorage.setItem('sweetstore_messages', JSON.stringify(messages));
    },

    getAll() {
        const stored = localStorage.getItem('sweetstore_messages');
        return stored ? JSON.parse(stored) : [];
    }
};

// Shop Page Functions
function renderProducts(productsToRender) {
    const grid = document.getElementById('product-grid');
    if (!grid) return;

    if (productsToRender.length === 0) {
        grid.innerHTML = `
      <div class="no-products" style="grid-column: 1 / -1; text-align: center; padding: 4rem;">
        <p style="color: var(--text-muted);">No products found matching your criteria.</p>
      </div>
    `;
        return;
    }

    grid.innerHTML = productsToRender.map(product => `
    <article class="product-card" data-id="${product.id}">
      <a href="product.html?id=${product.id}" class="product-image" aria-label="View ${product.name}">
        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, ${getGradientColors(product.category)}); display: flex; align-items: center; justify-content: center;">
          <span style="font-size: 4rem;">${getCategoryEmoji(product.category)}</span>
        </div>
        ${product.sugarFree ? '<span class="product-tag sugar-free">Sugar Free</span>' : ''}
        ${product.popular ? '<span class="product-tag">Popular</span>' : ''}
      </a>
      <div class="product-info">
        <span class="product-category">${product.category}</span>
        <h3 class="product-name">${product.name}</h3>
        <span class="product-price">$${product.price.toFixed(2)}</span>
        <div class="product-actions">
          <a href="product.html?id=${product.id}" class="btn btn-outline btn-small">View</a>
          <button class="btn btn-primary btn-small" onclick="cart.add(${product.id}); event.stopPropagation();">
            Add to Cart
          </button>
        </div>
      </div>
    </article>
  `).join('');
}

function getCategoryEmoji(category) {
    const emojis = {
        'Chocolates': '🍫',
        'Gummies': '🍬',
        'Candy Bars': '🍪',
        'Sugar-Free': '🌿'
    };
    return emojis[category] || '🍭';
}

function getGradientColors(category) {
    const colors = {
        'Chocolates': 'rgba(139, 69, 19, 0.3), rgba(78, 42, 16, 0.3)',
        'Gummies': 'rgba(255, 0, 153, 0.2), rgba(0, 255, 214, 0.2)',
        'Candy Bars': 'rgba(255, 213, 0, 0.2), rgba(255, 140, 0, 0.2)',
        'Sugar-Free': 'rgba(0, 255, 214, 0.2), rgba(0, 200, 150, 0.2)'
    };
    return colors[category] || 'rgba(100, 100, 100, 0.3), rgba(50, 50, 50, 0.3)';
}

function filterProducts() {
    const searchInput = document.getElementById('search-input');
    const sortSelect = document.getElementById('sort-select');
    const activeFilter = document.querySelector('.filter-btn.active');

    let filtered = [...products];

    // Search filter
    if (searchInput && searchInput.value) {
        const search = searchInput.value.toLowerCase();
        filtered = filtered.filter(p =>
            p.name.toLowerCase().includes(search) ||
            p.category.toLowerCase().includes(search) ||
            p.tags.some(tag => tag.toLowerCase().includes(search))
        );
    }

    // Category filter
    if (activeFilter && activeFilter.dataset.category !== 'all') {
        const category = activeFilter.dataset.category;
        filtered = filtered.filter(p => p.category === category);
    }

    // Sorting
    if (sortSelect) {
        switch (sortSelect.value) {
            case 'price-asc':
                filtered.sort((a, b) => a.price - b.price);
                break;
            case 'price-desc':
                filtered.sort((a, b) => b.price - a.price);
                break;
            case 'popular':
                filtered.sort((a, b) => b.popular - a.popular);
                break;
        }
    }

    renderProducts(filtered);
}

// Product Detail Page
function loadProductDetail() {
    const urlParams = new URLSearchParams(window.location.search);
    const productId = parseInt(urlParams.get('id'));
    const product = products.find(p => p.id === productId);

    if (!product) {
        document.querySelector('.product-detail').innerHTML = `
      <div class="container" style="text-align: center; padding: 4rem;">
        <h1>Product Not Found</h1>
        <p style="color: var(--text-muted); margin: 1rem 0 2rem;">The product you're looking for doesn't exist.</p>
        <a href="shop.html" class="btn btn-primary">Back to Shop</a>
      </div>
    `;
        return;
    }

    // Update page title
    document.title = `${product.name} - KOOKIE`;

    // Render product detail
    const detailContainer = document.getElementById('product-detail-content');
    if (detailContainer) {
        detailContainer.innerHTML = `
      <div class="product-gallery">
        <div class="gallery-main">
          <div style="width: 100%; height: 100%; background: linear-gradient(135deg, ${getGradientColors(product.category)}); display: flex; align-items: center; justify-content: center;">
            <span style="font-size: 8rem;">${getCategoryEmoji(product.category)}</span>
          </div>
        </div>
        <div class="gallery-thumbs">
          ${[1, 2, 3, 4].map((_, i) => `
            <button class="gallery-thumb ${i === 0 ? 'active' : ''}" aria-label="View image ${i + 1}">
              <div style="width: 100%; height: 100%; background: linear-gradient(135deg, ${getGradientColors(product.category)}); display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 1.5rem;">${getCategoryEmoji(product.category)}</span>
              </div>
            </button>
          `).join('')}
        </div>
      </div>
      <div class="product-detail-info">
        <span class="product-detail-category">${product.category}</span>
        <h1>${product.name}</h1>
        <div class="product-detail-price">$${product.price.toFixed(2)}</div>
        <p class="product-detail-description">${product.description}</p>
        <div class="product-tags">
          ${product.tags.map(tag => `<span>${tag}</span>`).join('')}
        </div>
        <div class="quantity-selector">
          <label>Quantity:</label>
          <div class="quantity-controls">
            <button type="button" onclick="updateDetailQuantity(-1)" aria-label="Decrease quantity">−</button>
            <input type="number" id="detail-quantity" value="1" min="1" max="99" aria-label="Quantity">
            <button type="button" onclick="updateDetailQuantity(1)" aria-label="Increase quantity">+</button>
          </div>
        </div>
        <div class="product-detail-actions">
          <button class="btn btn-primary" onclick="addDetailToCart(${product.id})">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
              <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            Add to Cart
          </button>
          <a href="shop.html" class="btn btn-secondary">Continue Shopping</a>
        </div>
      </div>
    `;
    }
}

function updateDetailQuantity(change) {
    const input = document.getElementById('detail-quantity');
    if (input) {
        const newValue = Math.max(1, Math.min(99, parseInt(input.value) + change));
        input.value = newValue;
    }
}

function addDetailToCart(productId) {
    const input = document.getElementById('detail-quantity');
    const quantity = input ? parseInt(input.value) : 1;
    cart.add(productId, quantity);
}

// Cart Page
function renderCart() {
    const cartContainer = document.getElementById('cart-items');
    const summaryContainer = document.getElementById('cart-summary');

    if (!cartContainer) return;

    if (cart.items.length === 0) {
        cartContainer.innerHTML = `
      <div class="cart-empty">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin: 0 auto 1rem; color: var(--text-muted);">
          <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <p>Your cart is empty</p>
        <a href="shop.html" class="btn btn-primary">Start Shopping</a>
      </div>
    `;
        if (summaryContainer) {
            summaryContainer.style.display = 'none';
        }
        return;
    }

    cartContainer.innerHTML = cart.items.map(item => {
        const product = products.find(p => p.id === item.id);
        return `
      <div class="cart-item" data-id="${item.id}">
        <div class="cart-item-image">
          <div style="width: 100%; height: 100%; background: linear-gradient(135deg, ${getGradientColors(item.category)}); display: flex; align-items: center; justify-content: center;">
            <span style="font-size: 2rem;">${getCategoryEmoji(item.category)}</span>
          </div>
        </div>
        <div class="cart-item-details">
          <h3 class="cart-item-name">${item.name}</h3>
          <span class="cart-item-category">${item.category}</span>
          <div class="cart-item-bottom">
            <span class="cart-item-price">$${(item.price * item.quantity).toFixed(2)}</span>
            <div class="cart-item-actions">
              <div class="quantity-controls">
                <button type="button" onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})" aria-label="Decrease quantity">−</button>
                <input type="number" value="${item.quantity}" min="1" max="99" onchange="updateCartQuantity(${item.id}, this.value)" aria-label="Quantity">
                <button type="button" onclick="updateCartQuantity(${item.id}, ${item.quantity + 1})" aria-label="Increase quantity">+</button>
              </div>
              <button class="remove-btn" onclick="removeFromCart(${item.id})">Remove</button>
            </div>
          </div>
        </div>
      </div>
    `;
    }).join('');

    // Update summary
    if (summaryContainer) {
        summaryContainer.style.display = 'block';
        const subtotal = cart.getTotal();
        const shipping = subtotal > 50 ? 0 : 5.99;
        const total = subtotal + shipping;

        document.getElementById('cart-subtotal').textContent = `$${subtotal.toFixed(2)}`;
        document.getElementById('cart-shipping').textContent = shipping === 0 ? 'FREE' : `$${shipping.toFixed(2)}`;
        document.getElementById('cart-total').textContent = `$${total.toFixed(2)}`;
    }
}

function updateCartQuantity(productId, quantity) {
    quantity = parseInt(quantity);
    if (quantity < 1) {
        removeFromCart(productId);
    } else {
        cart.updateQuantity(productId, quantity);
        renderCart();
    }
}

function removeFromCart(productId) {
    cart.remove(productId);
    renderCart();
}

// Checkout Page
function renderCheckout() {
    const itemsContainer = document.getElementById('checkout-items');
    const summaryContainer = document.getElementById('checkout-summary');

    if (!itemsContainer) return;

    if (cart.items.length === 0) {
        window.location.href = 'cart.html';
        return;
    }

    itemsContainer.innerHTML = cart.items.map(item => `
    <div class="checkout-item">
      <div class="checkout-item-image">
        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, ${getGradientColors(item.category)}); display: flex; align-items: center; justify-content: center;">
          <span style="font-size: 1.5rem;">${getCategoryEmoji(item.category)}</span>
        </div>
      </div>
      <div class="checkout-item-info">
        <div class="checkout-item-name">${item.name}</div>
        <div class="checkout-item-qty">Qty: ${item.quantity}</div>
      </div>
      <div class="checkout-item-price">$${(item.price * item.quantity).toFixed(2)}</div>
    </div>
  `).join('');

    // Update summary
    const subtotal = cart.getTotal();
    const shipping = subtotal > 50 ? 0 : 5.99;
    const tax = subtotal * 0.08;
    const total = subtotal + shipping + tax;

    document.getElementById('checkout-subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('checkout-shipping').textContent = shipping === 0 ? 'FREE' : `$${shipping.toFixed(2)}`;
    document.getElementById('checkout-tax').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('checkout-total').textContent = `$${total.toFixed(2)}`;
}

function placeOrder() {
    const modal = document.getElementById('success-modal');
    if (modal) {
        modal.classList.add('show');
        cart.clear();
    }
}

function closeSuccessModal() {
    const modal = document.getElementById('success-modal');
    if (modal) {
        modal.classList.remove('show');
        window.location.href = 'home.html';
    }
}

// Contact Form
function handleContactSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);

    const message = {
        name: formData.get('name'),
        email: formData.get('email'),
        message: formData.get('message')
    };

    contactStorage.save(message);

    form.reset();
    const successMsg = document.getElementById('form-success');
    if (successMsg) {
        successMsg.classList.add('show');
        setTimeout(() => successMsg.classList.remove('show'), 5000);
    }
}

// Mobile Navigation
function toggleMobileNav() {
    const mobileNav = document.getElementById('mobile-nav');
    if (mobileNav) {
        mobileNav.classList.toggle('show');
        document.body.style.overflow = mobileNav.classList.contains('show') ? 'hidden' : '';
    }
}

function closeMobileNav() {
    const mobileNav = document.getElementById('mobile-nav');
    if (mobileNav) {
        mobileNav.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Initialize cart
    cart.init();

    // Page-specific initialization
    const currentPage = window.location.pathname.split('/').pop() || 'home.html';

    switch (currentPage) {
        case 'shop.html':
            renderProducts(products);

            // Set up filters
            const filterBtns = document.querySelectorAll('.filter-btn');
            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    filterProducts();
                });
            });

            // Set up search
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.addEventListener('input', filterProducts);
            }

            // Set up sort
            const sortSelect = document.getElementById('sort-select');
            if (sortSelect) {
                sortSelect.addEventListener('change', filterProducts);
            }
            break;

        case 'product.html':
            loadProductDetail();
            break;

        case 'cart.html':
            renderCart();
            break;

        case 'checkout.html':
            renderCheckout();
            break;

        case 'contact.html':
            const contactForm = document.getElementById('contact-form');
            if (contactForm) {
                contactForm.addEventListener('submit', handleContactSubmit);
            }
            break;
    }

    // Set active nav link
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === 'home.html' && href === 'home.html')) {
            link.classList.add('active');
        }
    });

    // Add notification animation styles
    const style = document.createElement('style');
    style.textContent = `
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes fadeOut {
      from { opacity: 1; }
      to { opacity: 0; }
    }
  `;
    document.head.appendChild(style);
});

// Keyboard navigation for gallery
document.addEventListener('keydown', (e) => {
    const thumbs = document.querySelectorAll('.gallery-thumb');
    if (thumbs.length === 0) return;

    const active = document.querySelector('.gallery-thumb.active');
    const activeIndex = Array.from(thumbs).indexOf(active);

    if (e.key === 'ArrowLeft' && activeIndex > 0) {
        thumbs[activeIndex].classList.remove('active');
        thumbs[activeIndex - 1].classList.add('active');
    } else if (e.key === 'ArrowRight' && activeIndex < thumbs.length - 1) {
        thumbs[activeIndex].classList.remove('active');
        thumbs[activeIndex + 1].classList.add('active');
    }
});
*/
