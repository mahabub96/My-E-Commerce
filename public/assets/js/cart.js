document.addEventListener('DOMContentLoaded', () => {
  const drawer = document.querySelector('[data-cart-drawer]');
  if (!drawer) return;

  const panel = drawer.querySelector('[data-cart-panel]');
  const overlay = drawer.querySelector('[data-cart-overlay]');
  const openers = document.querySelectorAll('[data-cart-open]');
  const adders = [];
  const closeButtons = drawer.querySelectorAll('[data-cart-close]');
  const itemsContainer = drawer.querySelector('[data-cart-items]');
  const emptyState = drawer.querySelector('[data-cart-empty]');
  const totalEls = document.querySelectorAll('[data-cart-total]');
  const countEls = document.querySelectorAll('[data-cart-count]');
  const summaryContainer = document.querySelector('[data-checkout-summary]');

  const focusableSelectors = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';
  let cart = [];
  let lastFocused = null;

  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function formatMoney(value) {
    return `$${Number(value).toLocaleString()}`;
  }

  function getSubtotal() {
    return cart.reduce((sum, item) => {
      const price = Number(item.price) || 0;
      const qty = Number(item.qty) || 0;
      return sum + (price * qty);
    }, 0);
  }

  async function fetchCart() {
    try {
      const res = await fetch('/cart/items', {
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();
      if (json && json.success) {
        cart = json.cart.map(item => ({ id: Number(item.id || item.product_id), name: item.name, price: Number(item.price), qty: Number(item.quantity || item.qty || 1), image: item.image || '' }));
        renderCart();
        updateCountBadge(json.count ?? cart.reduce((s,i) => s + i.qty, 0));
      }
    } catch (err) {
      console.error('Failed to fetch cart', err);
    }
  }

  function updateCountBadge(count) {
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

  async function updateItemQty(id, qty) {
    try {
      const res = await fetch('/cart/update', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ product_id: id, quantity: qty })
      });
      const json = await res.json();
      if (json && json.success) {
        await fetchCart();
      } else {
        alert(json.message || 'Failed to update cart');
      }
    } catch (err) {
      console.error(err);
      alert('Failed to update cart. Please try again.');
    }
  }

  async function removeItem(id) {
    try {
      const res = await fetch('/cart/remove', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ product_id: id })
      });
      const json = await res.json();
      if (json && json.success) {
        await fetchCart();
      } else {
        alert(json.message || 'Failed to remove item');
      }
    } catch (err) {
      console.error(err);
      alert('Failed to remove item. Please try again.');
    }
  }

  function getQtyFromContext(button) {
    const scopedInput = button.closest('[data-qty-wrapper]')?.querySelector('[data-qty-input]')
      || document.querySelector('[data-qty-input]');
    if (!scopedInput) return 1;
    return Math.max(1, parseInt(scopedInput.value, 10) || 1);
  }

  async function addToCart(button) {
    const productId = Number(button.dataset.productId || 0);
    if (!productId) return;
    const qty = getQtyFromContext(button);
    try {
      const res = await fetch('/cart/add', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ product_id: productId, quantity: qty })
      });
      const json = await res.json();
      if (json && json.success) {
        await fetchCart();
        openCart();
      } else {
        alert(json.message || 'Failed to add to cart');
      }
    } catch (err) {
      console.error(err);
      alert('Failed to add to cart. Please try again.');
    }
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

  openers.forEach(trigger => trigger.addEventListener('click', async event => {
    event.preventDefault();
    await fetchCart();
    openCart();
  }));

  document.addEventListener('click', event => {
    const btn = event.target.closest('[data-add-to-cart]');
    if (!btn) return;
    event.preventDefault();
    addToCart(btn);
  });

  // initial load
  fetchCart();
});
