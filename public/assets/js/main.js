document.addEventListener('DOMContentLoaded', () => {
  initCompareCards();
  initStickyHeader();
  // initHeroSearch(); // Handled by search.js
  initProfileOrdersModal();
  initProfilePrimaryPopover();
  initLeaderboardModal();
});

function initCompareCards() {
  const cards = document.querySelectorAll('[data-card]');
  if (!cards.length) return;

  cards.forEach(card => {
    const toggleBtn = card.querySelector('[data-toggle]');
    const details = card.querySelector('.compare-card__details');
    if (!toggleBtn || !details) return;

    toggleBtn.addEventListener('click', event => {
      event.stopPropagation();

      cards.forEach(otherCard => {
        if (otherCard !== card) {
          otherCard.classList.remove('is-expanded');
          const otherToggle = otherCard.querySelector('[data-toggle]');
          const otherDetails = otherCard.querySelector('.compare-card__details');
          if (otherToggle) otherToggle.setAttribute('aria-expanded', 'false');
          if (otherDetails) otherDetails.setAttribute('aria-hidden', 'true');
        }
      });

      const isExpanded = card.classList.toggle('is-expanded');
      toggleBtn.setAttribute('aria-expanded', isExpanded);
      details.setAttribute('aria-hidden', (!isExpanded).toString());
    });
  });
}

function initStickyHeader() {
  const header = document.querySelector('.site-header');
  if (!header) return;

  const toggleShadow = () => {
    if (window.scrollY > 10) header.classList.add('is-scrolled');
    else header.classList.remove('is-scrolled');
  };

  toggleShadow();
  window.addEventListener('scroll', toggleShadow, { passive: true });
}

function initHeroSearch() {
  const input = document.querySelector('#hero-search');
  if (!input) return;
  const form = input.closest('form');

  let suggestionBox = null;
  let debounceTimer = null;

  const ensureSuggestionBox = () => {
    if (suggestionBox) return;
    const parent = input.parentElement;
    if (parent) parent.style.position = 'relative';
    suggestionBox = document.createElement('div');
    suggestionBox.className = 'list-group position-absolute w-100';
    suggestionBox.style.top = '100%';
    suggestionBox.style.left = '0';
    suggestionBox.style.zIndex = '1000';
    suggestionBox.hidden = true;
    parent?.appendChild(suggestionBox);
  };

  const renderSuggestions = (items) => {
    if (!suggestionBox) return;
    suggestionBox.innerHTML = '';
    if (!items.length) {
      suggestionBox.hidden = true;
      return;
    }
    items.forEach(item => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'list-group-item list-group-item-action';
      btn.textContent = item.name;
      btn.addEventListener('click', () => {
        window.location.href = `/product/${encodeURIComponent(item.slug)}`;
      });
      suggestionBox.appendChild(btn);
    });
    suggestionBox.hidden = false;
  };

  const fetchSuggestions = async (term) => {
    try {
      const res = await fetch(`/search/suggestions?q=${encodeURIComponent(term)}`, {
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();
      if (json && json.success) {
        renderSuggestions(json.suggestions || []);
      }
    } catch (err) {
      console.error('Failed to fetch suggestions', err);
    }
  };

  input.addEventListener('input', (e) => {
    const term = e.target.value.trim();
    ensureSuggestionBox();
    clearTimeout(debounceTimer);
    if (term.length < 2) {
      renderSuggestions([]);
      return;
    }
    debounceTimer = setTimeout(() => fetchSuggestions(term), 400);
  });

  document.addEventListener('click', (e) => {
    if (!suggestionBox) return;
    if (!suggestionBox.contains(e.target) && e.target !== input) {
      suggestionBox.hidden = true;
    }
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const term = input.value.trim();
    if (!term) return;
    try {
      const res = await fetch(`/search?q=${encodeURIComponent(term)}`, {
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();
      if (json && json.redirect) {
        window.location.href = json.redirect;
        return;
      }
    } catch (err) {
      // fall through
    }
    window.location.href = `/shop?search=${encodeURIComponent(term)}`;
  });
}

function initProfileOrdersModal() {
  const viewAllLink = document.querySelector('a[href="/orders"]');
  if (!viewAllLink) return;

  viewAllLink.addEventListener('click', async (e) => {
    e.preventDefault();

    const existing = document.getElementById('ordersModal');
    if (!existing) {
      const modal = document.createElement('div');
      modal.className = 'modal fade';
      modal.id = 'ordersModal';
      modal.tabIndex = -1;
      modal.setAttribute('aria-hidden', 'true');
      modal.innerHTML = `
        <div class="modal-dialog modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">All Orders</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead>
                    <tr>
                      <th scope="col">Order ID</th>
                      <th scope="col">Date</th>
                      <th scope="col">Total</th>
                      <th scope="col">Status</th>
                      <th scope="col">Action</th>
                    </tr>
                  </thead>
                  <tbody data-orders-body></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
    }

    const modalEl = document.getElementById('ordersModal');
    const tbody = modalEl.querySelector('[data-orders-body]');
    tbody.innerHTML = '<tr><td colspan="5">Loading...</td></tr>';

    try {
      const res = await fetch('/profile/orders', { headers: { 'Accept': 'application/json' } });
      const json = await res.json();
      if (json && json.success && Array.isArray(json.orders)) {
        tbody.innerHTML = '';
        json.orders.forEach(o => {
          const tr = document.createElement('tr');
          const dateStr = o.updated_at ? new Date(o.updated_at).toLocaleDateString() : '';
          const status = o.order_status || 'pending';
          tr.innerHTML = `
            <td>${o.order_number || ('#' + (o.id || ''))}</td>
            <td>${dateStr}</td>
            <td>$${Number(o.total_amount || 0).toFixed(2)}</td>
            <td><span class="badge ${status === 'processing' || status === 'pending' ? 'bg-success' : 'bg-secondary'}">${status.charAt(0).toUpperCase() + status.slice(1)}</span></td>
            <td><a class="btn btn-outline-primary btn-sm rounded-pill" href="/order-success?order_id=${encodeURIComponent(o.id || '')}">View</a></td>
          `;
          tbody.appendChild(tr);
        });
      } else {
        tbody.innerHTML = '<tr><td colspan="5">No orders found.</td></tr>';
      }
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="5">Failed to load orders.</td></tr>';
    }

    if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
      const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
      modalInstance.show();
    }
  });
}

function initProfilePrimaryPopover() {
  const triggers = document.querySelectorAll('[data-profile-popover]');
  const template = document.getElementById('profile-popover-template');
  if (!triggers.length || !template || !window.bootstrap || !window.bootstrap.Popover) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const createContent = () => {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = template.innerHTML;
    return wrapper;
  };

  const bindForm = (container) => {
    const form = container.querySelector('[data-profile-primary-form]');
    const errorBox = container.querySelector('[data-profile-error]');
    const successBox = container.querySelector('[data-profile-success]');
    if (!form) return;

    const showError = (msg) => {
      if (!errorBox) return;
      errorBox.textContent = msg;
      errorBox.classList.remove('d-none');
    };
    const clearError = () => errorBox && errorBox.classList.add('d-none');
    const showSuccess = (msg) => {
      if (!successBox) return;
      successBox.textContent = msg;
      successBox.classList.remove('d-none');
    };
    const clearSuccess = () => successBox && successBox.classList.add('d-none');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearError();
      clearSuccess();

      const formData = new FormData(form);
      const payload = Object.fromEntries(formData.entries());

      try {
        const res = await fetch('/profile/update-primary', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrf,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(payload)
        });

        const json = await res.json();
        if (json && json.success) {
          showSuccess('Profile saved. You can now proceed to checkout.');
          window.location.reload();
          return;
        }

        if (json && json.errors) {
          const messages = Object.keys(json.errors).map(k => `${k}: ${json.errors[k]}`);
          showError(messages.join(' | '));
          return;
        }

        showError(json.message || 'Failed to update profile.');
      } catch (err) {
        showError('Failed to update profile.');
      }
    });
  };

  triggers.forEach((trigger) => {
    const popover = new window.bootstrap.Popover(trigger, {
      html: true,
      trigger: 'click',
      placement: 'auto',
      content: () => {
        const content = createContent();
        bindForm(content);
        return content;
      }
    });

    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      popover.show();
    });
  });
}

function initLeaderboardModal() {
  const link = document.querySelector('.most-sold-section .link-primary.fw-medium[href="/shop"]');
  if (!link) return;

  link.addEventListener('click', async (e) => {
    e.preventDefault();

    const existing = document.getElementById('leaderboardModal');
    if (!existing) {
      const modal = document.createElement('div');
      modal.className = 'modal fade';
      modal.id = 'leaderboardModal';
      modal.tabIndex = -1;
      modal.setAttribute('aria-hidden', 'true');
      modal.innerHTML = `
        <div class="modal-dialog modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Leaderboard</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead>
                    <tr>
                      <th scope="col">Rank</th>
                      <th scope="col">Product</th>
                      <th scope="col">Sold</th>
                      <th scope="col">Price</th>
                    </tr>
                  </thead>
                  <tbody data-leaderboard-body></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
    }

    const modalEl = document.getElementById('leaderboardModal');
    const tbody = modalEl.querySelector('[data-leaderboard-body]');
    tbody.innerHTML = '<tr><td colspan="4">Loading...</td></tr>';

    try {
      const res = await fetch('/leaderboard', { headers: { 'Accept': 'application/json' } });
      const json = await res.json();
      if (json && json.success && Array.isArray(json.items)) {
        tbody.innerHTML = '';
        json.items.forEach((item, index) => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${index + 1}</td>
            <td>${item.name || ''}</td>
            <td>${Number(item.total_sold || 0)}</td>
            <td>$${Number(item.price || 0).toFixed(2)}</td>
          `;
          tbody.appendChild(tr);
        });
        if (!json.items.length) {
          tbody.innerHTML = '<tr><td colspan="4">No results found.</td></tr>';
        }
      } else {
        tbody.innerHTML = '<tr><td colspan="4">No results found.</td></tr>';
      }
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="4">Failed to load leaderboard.</td></tr>';
    }

    if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
      const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
      modalInstance.show();
    }
  });
}
