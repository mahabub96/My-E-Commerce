document.addEventListener('DOMContentLoaded', () => {
  const panel = document.querySelector('[data-filter-panel]');
  if (!panel) return;

  const toggles = document.querySelectorAll('[data-filter-toggle]');
  const closers = document.querySelectorAll('[data-filter-close]');
  const overlay = document.querySelector('[data-filter-overlay]');
  const sortSelect = document.querySelector('[data-sort-select]');
  const mql = window.matchMedia('(min-width: 992px)');
  const isDesktop = () => mql.matches;
  const focusableSelectors = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';
  let lastFocus = null;

  panel.setAttribute('tabindex', '-1');

  const setAria = (isOpen) => {
    panel.setAttribute('aria-hidden', (!isOpen).toString());
    toggles.forEach(btn => btn.setAttribute('aria-expanded', isOpen.toString()));
  };

  const trapFocus = (event) => {
    if (!panel.classList.contains('is-open') || event.key !== 'Tab') return;

    const focusable = Array.from(panel.querySelectorAll(focusableSelectors)).filter(el => !el.hasAttribute('disabled'));
    if (!focusable.length) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  };

  const closeFilters = () => {
    panel.classList.remove('is-open');
    overlay?.classList.remove('is-active');
    document.body.classList.remove('filter-open');
    setAria(false);
    document.removeEventListener('keydown', handleKeydown);
    if (lastFocus) lastFocus.focus();
  };

  const openFilters = (shouldFocus = false) => {
    panel.classList.add('is-open');
    if (isDesktop()) {
      overlay?.classList.remove('is-active');
      document.body.classList.remove('filter-open');
    } else {
      overlay?.classList.add('is-active');
      document.body.classList.add('filter-open');
    }
    setAria(true);
    lastFocus = document.activeElement;
    // Only move focus and trap keyboard on mobile (overlay) where modal behavior is expected
    if (shouldFocus && !isDesktop()) panel.focus();
    if (!isDesktop()) document.addEventListener('keydown', handleKeydown);
  };

  const handleKeydown = (event) => {
    if (event.key === 'Escape') closeFilters();
    trapFocus(event);
  };

  toggles.forEach(btn => btn.addEventListener('click', () => {
    if (panel.classList.contains('is-open')) closeFilters();
    else openFilters(true);
  }));

  closers.forEach(btn => btn.addEventListener('click', closeFilters));
  overlay?.addEventListener('click', closeFilters);

  const sortProducts = (order) => {
    const grid = document.querySelector('.shop-grid');
    if (!grid) return;

    const cards = Array.from(grid.children);
    // Preserve original order using dataset index
    cards.forEach((card, idx) => {
      if (!card.dataset.originalIndex) {
        card.dataset.originalIndex = idx.toString();
      }
    });

    const parsePrice = (card) => {
      const priceEl = card.querySelector('.product-card__price');
      if (!priceEl) return 0;
      return parseFloat(priceEl.textContent.replace(/[^0-9.]/g, '')) || 0;
    };

    let sorted = [...cards];
    if (order === 'price-asc') {
      sorted.sort((a, b) => parsePrice(a) - parsePrice(b));
    } else if (order === 'price-desc') {
      sorted.sort((a, b) => parsePrice(b) - parsePrice(a));
    } else {
      // Featured/Newest fallback: original order
      sorted.sort((a, b) => Number(a.dataset.originalIndex) - Number(b.dataset.originalIndex));
    }

    // Re-append in new order
    sorted.forEach(card => grid.appendChild(card));
  };

  sortSelect?.addEventListener('change', (e) => {
    const value = e.target.value;
    if (value === 'price-asc') sortProducts('price-asc');
    else if (value === 'price-desc') sortProducts('price-desc');
    else sortProducts('featured');
  });

  mql.addEventListener('change', closeFilters);
  closeFilters();

  // Initialize with featured order
  sortProducts('featured');
});
