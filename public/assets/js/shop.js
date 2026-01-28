document.addEventListener('DOMContentLoaded', () => {
  const panel = document.querySelector('[data-filter-panel]');
  if (!panel) return;

  const toggles = document.querySelectorAll('[data-filter-toggle]');
  const closers = document.querySelectorAll('[data-filter-close]');
  const overlay = document.querySelector('[data-filter-overlay]');
  const sortSelect = document.querySelector('[data-sort-select]');
  const searchInput = document.querySelector('#shop-search');
  const searchForm = searchInput?.closest('form');
  const searchClearBtn = searchInput?.parentElement?.querySelector('.btn-clear-search');
  const grid = document.querySelector('.shop-grid');
  const resultsText = document.querySelector('.shop-toolbar .text-muted');
  const sectionTitle = document.querySelector('.shop-toolbar .section-title');
  const pagination = document.querySelector('nav[aria-label="Shop pagination"]');
  const mql = window.matchMedia('(min-width: 992px)');
  const isDesktop = () => mql.matches;
  const focusableSelectors = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';
  let lastFocus = null;
  let searchDebounceTimer = null;
  let activeSearchRequest = null;
  let originalProducts = [];
  let originalPagination = {};
  let originalFilters = {
    categories: new Set(),
    prices: new Set()
  };

  const priceMap = ['under-100', '100-500', '500-1000', '1000-plus'];

  // Suggestion box / debounce handles
  let suggestionBox = null;
  let suggestionDebounceTimer = null;

  const state = {
    search: searchInput?.value || '',
    sort: sortSelect?.value || 'featured',
    categories: new Set(),
    prices: new Set(),
    page: 1,
    isSearchActive: false
  };

  // Save original UI state holders
  let originalSort = state.sort;
  let originalSearch = state.search;

  // Convert price radios to checkbox-like behavior for multi-select
  const priceInputs = Array.from(document.querySelectorAll('input[name="price"]'));
  priceInputs.forEach((input, idx) => {
    input.type = 'checkbox';
    input.dataset.priceRange = priceMap[idx] || '';
  });

  const initStateFromInputs = () => {
    state.categories = new Set(Array.from(document.querySelectorAll('input[name="category"]:checked')).map(i => i.value));
    state.prices = new Set(Array.from(document.querySelectorAll('input[name="price"]:checked')).map(i => i.dataset.priceRange).filter(Boolean));
    state.search = searchInput?.value || '';
    state.sort = sortSelect?.value || 'featured';
  };

  const buildQuery = () => {
    const params = new URLSearchParams();
    if (state.search) params.set('search', state.search);
    if (state.sort) params.set('sort', state.sort);
    if (state.page) params.set('page', String(state.page));
    state.categories.forEach(c => params.append('category[]', c));
    state.prices.forEach(p => params.append('price[]', p));
    return params;
  };

  const renderProducts = (products) => {
    if (!grid) return;
    grid.innerHTML = '';

    if (!products.length) {
      return;
    }

    products.forEach(p => {
      const img = p.image || '/assets/images/laptop.png';
      // Close suggestions when rendering product grid
      if (suggestionBox) suggestionBox.hidden = true;
      const stockOut = Number(p.quantity || 0) <= 0;
      const card = document.createElement('article');
      card.className = 'product-card';

      // Compute rating stars and counts from server-provided properties (avg_rating, review_count)
      const avgVal = Math.max(0, Math.min(5, Number(p.avg_rating ?? p.avgRating ?? 0)));
      const countVal = Number(p.review_count ?? p.reviewCount ?? p.reviews_count ?? 0);
      let full = Math.floor(avgVal);
      const frac = avgVal - full;
      const hasHalf = (frac >= 0.25 && frac < 0.75) ? 1 : 0;
      if (frac >= 0.75) full++;
      const empty = Math.max(0, 5 - full - hasHalf);
      let starsHtml = '';
      for (let i = 0; i < full; i++) starsHtml += '<i class="bi bi-star-fill text-warning"></i>';
      if (hasHalf) starsHtml += '<i class="bi bi-star-half text-warning"></i>';
      for (let i = 0; i < empty; i++) starsHtml += '<i class="bi bi-star text-warning"></i>';

      card.innerHTML = `
        ${p.featured ? `<div class="product-badge"><i class="bi bi-lightning-charge"></i>Hot</div>` : ''}
        <div class="product-card__image">
          <img src="${img}" alt="${p.name}">
        </div>
        <h3 class="product-card__title">${p.name}</h3>
        <p class="product-card__price">$${Number(p.price).toLocaleString()}</p>
        <div class="product-card__meta" aria-label="Rating ${avgVal.toFixed(1)} out of 5">
          ${starsHtml}
          <span>${avgVal.toFixed(1)}</span>
          <span>· ${countVal} reviews</span>
        </div>
        <div class="product-card__actions">
          <a class="btn btn-primary rounded-pill" href="/product/${encodeURIComponent(p.slug)}">View Details</a>
          <button class="btn btn-outline-primary rounded-pill" type="button" data-add-to-cart data-product-id="${Number(p.id)}" data-product-name="${p.name}" data-product-price="${Number(p.price)}" data-product-image="${img}" ${stockOut ? 'disabled' : ''}>${stockOut ? 'Stock Out' : 'Buy'}</button>
        </div>
      `;
      grid.appendChild(card);
    });
  };

  const renderPagination = (paginationData) => {
    if (!pagination) return;
    const { current_page, last_page } = paginationData || {};
    if (!current_page || !last_page || last_page <= 1) {
      pagination.innerHTML = '';
      return;
    }
    const params = buildQuery();
    const ul = document.createElement('ul');
    ul.className = 'pagination';

    const makeItem = (page, label, disabled = false, active = false, ariaLabel = '') => {
      const li = document.createElement('li');
      li.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;
      if (active) li.setAttribute('aria-current', 'page');
      params.set('page', String(page));
      const a = document.createElement('a');
      a.className = 'page-link';
      a.href = `/shop?${params.toString()}`;
      if (ariaLabel) a.setAttribute('aria-label', ariaLabel);
      a.textContent = label;
      li.appendChild(a);
      return li;
    };

    ul.appendChild(makeItem(Math.max(1, current_page - 1), '<', current_page <= 1, false, 'Previous'));
    for (let i = 1; i <= last_page; i++) {
      ul.appendChild(makeItem(i, String(i), false, i === current_page));
    }
    ul.appendChild(makeItem(Math.min(last_page, current_page + 1), '>', current_page >= last_page, false, 'Next'));

    pagination.innerHTML = '';
    pagination.appendChild(ul);
  };

  const updateResultsText = (json) => {
    if (sectionTitle) {
      sectionTitle.textContent = json.no_results ? 'No product found' : 'All Products';
    }
    if (resultsText) {
      resultsText.textContent = json.no_results ? 'Showing recommended products' : `${json.pagination?.total ?? 0} results`;
    }
  };

  const fetchProducts = async (saveAsOriginal = false) => {
    // Abort any active search request
    if (activeSearchRequest) {
      activeSearchRequest.abort();
      activeSearchRequest = null;
    }
    
    const params = buildQuery();
    const controller = new AbortController();
    activeSearchRequest = controller;
    
    try {
      const res = await fetch(`/shop?${params.toString()}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        signal: controller.signal
      });
      const json = await res.json();
      if (json && json.success) {
        renderProducts(json.products || []);
        renderPagination(json.pagination || {});
        updateResultsText(json);
        
        // Save original state when page first loads (no search active)
        if (saveAsOriginal && !state.isSearchActive) {
          originalProducts = json.products || [];
          originalPagination = json.pagination || {};
          // Also capture the current UI filters/sort/search as original
          originalFilters.categories = new Set(state.categories);
          originalFilters.prices = new Set(state.prices);
          originalSort = state.sort;
          originalSearch = state.search;
        }
      }
    } catch (err) {
      if (err.name !== 'AbortError') {
        console.error('Failed to fetch products', err);
      }
    } finally {
      if (activeSearchRequest === controller) {
        activeSearchRequest = null;
      }
    }
  };

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

  const updateStateAndFetch = () => {
    initStateFromInputs();
    state.page = 1;
    fetchProducts();
  };

  document.addEventListener('change', (e) => {
    if (e.target && (e.target.matches('input[name="category"]') || e.target.matches('input[name="price"]'))) {
      updateStateAndFetch();
    }
  });

  sortSelect?.addEventListener('change', () => {
    state.sort = sortSelect.value;
    state.page = 1;
    fetchProducts();
  });

  pagination?.addEventListener('click', (e) => {
    const link = e.target.closest('a.page-link');
    if (!link) return;
    e.preventDefault();
    const url = new URL(link.href, window.location.origin);
    const page = Number(url.searchParams.get('page') || 1);
    state.page = page;
    fetchProducts();
  });

  searchForm?.addEventListener('submit', (e) => {
    e.preventDefault(); // Prevent page reload
    // Live search handles it
  });
  
  // Live search with debouncing
  searchInput?.addEventListener('input', (e) => {
    const term = (e.target.value || '').trim();
    
    // Clear debounce timer
    if (searchDebounceTimer) {
      clearTimeout(searchDebounceTimer);
      searchDebounceTimer = null;
    }
    
    // Abort active search request
    if (activeSearchRequest) {
      activeSearchRequest.abort();
      activeSearchRequest = null;
    }
    
    // Empty search - restore original state
    if (!term) {
      state.search = '';
      state.isSearchActive = false;
      state.page = 1;
      
      // Restore original products and pagination
      if (originalProducts.length > 0) {
        renderProducts(originalProducts);
        renderPagination(originalPagination);
        updateResultsText({ 
          products: originalProducts, 
          pagination: originalPagination,
          no_results: false 
        });
      } else {
        // Fetch original if not saved
        fetchProducts(true);
      }
      return;
    }
    
    // Debounce live search (300ms delay)
    searchDebounceTimer = setTimeout(() => {
      // Hide suggestions when performing full search
      if (suggestionBox) suggestionBox.hidden = true;
      state.search = term;
      state.isSearchActive = true;
      state.page = 1;
      fetchProducts(false);
    }, 300);
  });
  
  // NOTE: Clear search button is now handled by search.js
  // DO NOT create clear button here to avoid conflicts

  // NOTE: Suggestion system is now handled by search.js
  // Removed old suggestion code to avoid conflicts

  panel.setAttribute('tabindex', '-1');
  initStateFromInputs();
  
  // Save original filter state
  originalFilters.categories = new Set(state.categories);
  originalFilters.prices = new Set(state.prices);
  
  // Save original state on page load
  fetchProducts(true);
});
