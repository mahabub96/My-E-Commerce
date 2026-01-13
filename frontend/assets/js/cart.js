document.addEventListener('DOMContentLoaded', () => {
  const drawer = document.querySelector('[data-cart-drawer]');
  if (!drawer) return;

  const panel = drawer.querySelector('[data-cart-panel]');
  const overlay = drawer.querySelector('[data-cart-overlay]');
  const openers = document.querySelectorAll('[data-cart-open]');
  const adders = document.querySelectorAll('[data-add-to-cart]');
  const closeButtons = drawer.querySelectorAll('[data-cart-close]');
  const itemsContainer = drawer.querySelector('[data-cart-items]');
  const emptyState = drawer.querySelector('[data-cart-empty]');
  const totalEls = document.querySelectorAll('[data-cart-total]');
  const countEls = document.querySelectorAll('[data-cart-count]');
  const summaryContainer = document.querySelector('[data-checkout-summary]');

  const CART_KEY = 'ff-cart';
  const focusableSelectors = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';
  let cart = readCart();
  let lastFocused = null;

  function readCart() {
    try {
      const stored = localStorage.getItem(CART_KEY);
      return stored ? JSON.parse(stored) : [];
    } catch (error) {
      return [];
    }
  }

  function saveCart() {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
  }

  function formatMoney(value) {
    return `$${Number(value).toLocaleString()}`;
  }

  function updateCountBadge() {
    const count = cart.reduce((sum, item) => sum + item.qty, 0);
    countEls.forEach(el => {
      if (!el) return;
      el.textContent = count;
      el.hidden = count === 0;
    });
  }

  function renderCart() {
    if (!itemsContainer) return;

    itemsContainer.innerHTML = '';
    const isEmpty = !cart.length;

    if (emptyState) emptyState.hidden = !isEmpty;
    totalEls.forEach(el => {
      el.textContent = isEmpty ? '$0' : formatMoney(getSubtotal());
    });

    if (isEmpty) {
      updateCountBadge();
      renderCheckoutSummary();
      return;
    }

    cart.forEach(item => {
      const article = document.createElement('article');
      article.className = 'cart-item';
      article.dataset.cartItem = item.id;
      article.innerHTML = `
        <div class="cart-item__thumb">
          <img src="${item.image}" alt="${item.name}">
        </div>
        <div class="cart-item__info">
          <p class="cart-item__title">${item.name}</p>
          <div class="cart-item__meta">
            <span>${formatMoney(item.price)}</span>
            <div class="cart-qty" data-cart-qty>
              <button type="button" data-action="dec" aria-label="Decrease quantity">-</button>
              <input type="text" value="${item.qty}" inputmode="numeric" aria-label="Quantity">
              <button type="button" data-action="inc" aria-label="Increase quantity">+</button>
            </div>
          </div>
        </div>
        <button class="cart-remove" type="button" data-cart-remove aria-label="Remove item"><i class="bi bi-trash"></i></button>
      `;

      bindCartItem(article, item.id);
      itemsContainer.appendChild(article);
    });

    renderCheckoutSummary();
    updateCountBadge();
  }

  function renderCheckoutSummary() {
    if (!summaryContainer) return;
    summaryContainer.innerHTML = '';

    if (!cart.length) {
      summaryContainer.innerHTML = '<p class="text-muted mb-0">Your cart is empty.</p>';
      return;
    }

    cart.forEach(item => {
      const row = document.createElement('div');
      row.className = 'd-flex justify-content-between align-items-center mb-2';
      row.innerHTML = `
        <div class="d-flex align-items-center gap-3">
          <img src="${item.image}" alt="${item.name}" width="64">
          <div>
            <p class="mb-1 fw-semibold">${item.name}</p>
            <small class="text-muted">Qty: ${item.qty}</small>
          </div>
        </div>
        <span class="fw-bold">${formatMoney(item.price * item.qty)}</span>
      `;
      summaryContainer.appendChild(row);
    });

    const totals = document.createElement('div');
    totals.className = 'd-flex justify-content-between align-items-center mt-2';
    totals.innerHTML = `<strong>Total</strong><strong>${formatMoney(getSubtotal())}</strong>`;
    summaryContainer.appendChild(totals);
  }

  function bindCartItem(node, id) {
    const qtyWrapper = node.querySelector('[data-cart-qty]');
    const input = qtyWrapper?.querySelector('input');

    qtyWrapper?.querySelectorAll('button').forEach(button => {
      button.addEventListener('click', () => {
        const dir = button.dataset.action === 'inc' ? 1 : -1;
        const next = Math.max(1, (parseInt(input.value, 10) || 1) + dir);
        input.value = next;
        updateItemQty(id, next);
      });
    });

    input?.addEventListener('change', () => {
      const next = Math.max(1, parseInt(input.value, 10) || 1);
      input.value = next;
      updateItemQty(id, next);
    });

    const removeBtn = node.querySelector('[data-cart-remove]');
    removeBtn?.addEventListener('click', () => removeItem(id));
  }

  function getSubtotal() {
    return cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  }

  function updateItemQty(id, qty) {
    cart = cart.map(item => item.id === id ? { ...item, qty } : item);
    saveCart();
    renderCart();
  }

  function removeItem(id) {
    cart = cart.filter(item => item.id !== id);
    saveCart();
    renderCart();
  }

  function getQtyFromContext(button) {
    const scopedInput = button.closest('[data-qty-wrapper]')?.querySelector('[data-qty-input]')
      || document.querySelector('[data-qty-input]');
    if (!scopedInput) return 1;
    return Math.max(1, parseInt(scopedInput.value, 10) || 1);
  }

  function addToCart(button) {
    const { productId, productName, productPrice, productImage } = button.dataset;
    if (!productId || !productName || !productPrice || !productImage) return;

    const qty = getQtyFromContext(button);
    const existing = cart.find(item => item.id === productId);
    const price = Number(productPrice);

    if (existing) {
      existing.qty += qty;
    } else {
      cart.push({ id: productId, name: productName, price, image: productImage, qty });
    }

    saveCart();
    renderCart();
    openCart();
  }

  function trapFocus(event) {
    if (!panel || !drawer.classList.contains('is-open')) return;

    const focusable = Array.from(panel.querySelectorAll(focusableSelectors)).filter(el => !el.hasAttribute('disabled'));
    if (!focusable.length) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.key === 'Tab') {
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }
  }

  function handleKeydown(event) {
    if (event.key === 'Escape') closeCart();
    trapFocus(event);
  }

  function openCart() {
    drawer.classList.add('is-open');
    document.body.classList.add('cart-open');
    lastFocused = document.activeElement;
    panel?.setAttribute('aria-hidden', 'false');
    panel?.focus();
    document.addEventListener('keydown', handleKeydown);
  }

  function closeCart() {
    drawer.classList.remove('is-open');
    document.body.classList.remove('cart-open');
    panel?.setAttribute('aria-hidden', 'true');
    document.removeEventListener('keydown', handleKeydown);
    if (lastFocused) lastFocused.focus();
  }

  overlay?.addEventListener('click', closeCart);
  closeButtons.forEach(btn => btn.addEventListener('click', closeCart));

  drawer.addEventListener('click', event => {
    if (event.target === drawer) closeCart();
  });

  openers.forEach(trigger => trigger.addEventListener('click', event => {
    event.preventDefault();
    openCart();
  }));

  adders.forEach(button => button.addEventListener('click', event => {
    event.preventDefault();
    addToCart(button);
  }));

  renderCart();
});
