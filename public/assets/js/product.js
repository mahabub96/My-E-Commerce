document.addEventListener('DOMContentLoaded', () => {
  initQuantityControls();
  initTabs();
  bindBuyNowButtons();
  bindGallery();
  initReviews();
});

function initQuantityControls() {
  document.querySelectorAll('[data-qty]').forEach(wrapper => {
    const input = wrapper.querySelector('input');
    wrapper.querySelectorAll('button').forEach(button => {
      button.addEventListener('click', () => {
        const dir = button.dataset.action === 'inc' ? 1 : -1;
        input.value = Math.max(1, (parseInt(input.value, 10) || 1) + dir);
      });
    });
  });
}

function initTabs() {
  const tabButtons = document.querySelectorAll('[data-tab-target]');
  if (!tabButtons.length) return;

  tabButtons.forEach(button => {
    button.addEventListener('click', () => {
      const targetId = button.dataset.tabTarget;
      const target = document.getElementById(targetId);
      if (!target) return;

      tabButtons.forEach(btn => btn.classList.remove('is-active'));
      document.querySelectorAll('[data-tab-panel]').forEach(panel => panel.classList.remove('is-active'));

      button.classList.add('is-active');
      target.classList.add('is-active');
    });
  });
}

function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

function bindGallery() {
  const main = document.querySelector('[data-product-main]');
  const thumbsWrapper = document.querySelector('[data-product-thumbs]');
  if (!main || !thumbsWrapper) return;

  const thumbs = Array.from(thumbsWrapper.querySelectorAll('[data-product-thumb]'));
  if (!thumbs.length) return;

  // Set initial active thumbnail
  let activeIndex = 0;
  const setActive = (index) => {
    index = (index + thumbs.length) % thumbs.length;
    activeIndex = index;
    const thumb = thumbs[activeIndex];
    const src = thumb.getAttribute('data-src');
    if (src) {
      main.setAttribute('src', src);
      main.setAttribute('alt', thumb.querySelector('img')?.getAttribute('alt') || main.getAttribute('alt'));
    }
    thumbs.forEach((t, i) => {
      if (i === activeIndex) {
        t.style.border = '2px solid #0d6efd';
        t.style.boxShadow = '0 0 0 2px rgba(13,110,253,0.08)';
      } else {
        t.style.border = '1px solid #e3e7ed';
        t.style.boxShadow = 'none';
      }
    });
  };

  const clearHighlight = () => {
    thumbs.forEach((t) => {
      t.style.border = '1px solid #e3e7ed';
      t.style.boxShadow = 'none';
    });
  };

  thumbs.forEach((btn, i) => btn.addEventListener('click', () => setActive(i)));

  // Arrow controls (inject into main image area)
  const left = document.createElement('button');
  const right = document.createElement('button');
  left.type = 'button'; right.type = 'button';
  left.innerHTML = '&#10094;'; right.innerHTML = '&#10095;';
  [left, right].forEach(b => {
    b.style.position = 'absolute';
    b.style.top = '50%';
    b.style.transform = 'translateY(-50%)';
    b.style.background = 'rgba(0,0,0,0.35)';
    b.style.color = '#fff';
    b.style.border = 'none';
    b.style.width = '36px';
    b.style.height = '36px';
    b.style.borderRadius = '50%';
    b.style.cursor = 'pointer';
    b.style.display = 'flex';
    b.style.alignItems = 'center';
    b.style.justifyContent = 'center';
  });
  left.style.left = '12px';
  right.style.right = '12px';

  const mainWrap = main.closest('.product-media__main');
  if (mainWrap) {
    mainWrap.style.position = 'relative';
    mainWrap.appendChild(left);
    mainWrap.appendChild(right);
  }

  left.addEventListener('click', () => setActive(activeIndex - 1));
  right.addEventListener('click', () => setActive(activeIndex + 1));

  // Clicking outside the image area removes highlight
  document.addEventListener('click', (e) => {
    if (thumbsWrapper.contains(e.target) || main.contains(e.target)) return;
    clearHighlight();
  });

  // Keyboard navigation
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') setActive(activeIndex - 1);
    if (e.key === 'ArrowRight') setActive(activeIndex + 1);
  });

  // Ensure at least one active image
  setActive(0);
}



function bindBuyNowButtons() {
  document.querySelectorAll('[data-buy-now]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const productId = Number(btn.dataset.productId || 0);
      if (!productId) return;
      const qty = Number(document.querySelector('[data-qty-input]')?.value || 1);
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
          window.location.href = '/checkout';
        } else {
          alert(json.message || 'Failed to add item for checkout');
        }
      } catch (err) {
        console.error(err);
        alert('Failed to process request. Please try again.');
      }
    });
  });
}

// ---------------- Reviews -----------------
function initReviews() {
  const reviewsTabBtn = document.querySelector('[data-tab-target="tab-reviews"]');
  if (!reviewsTabBtn) return;

  const productRoot = document.querySelector('[data-reviews-root]');
  const container = document.querySelector('[data-product-id]');
  const productId = Number(container?.dataset.productId || document.querySelector('input[name="product_id"]')?.value || document.querySelector('[data-add-to-cart]')?.dataset.productId || 0);

  if (!productId || productId === 0) {
    console.error('Product ID not found!');
    return;
  }

  async function loadReviews() {
    try {
      // Try REST-style URL first
      let res = await fetch(`/products/${productId}/reviews`, { headers: { 'Accept': 'application/json' } });
      
      // If 404, try query parameter fallback
      if (res.status === 404) {
        res = await fetch(`/index.php?route=products_reviews&id=${productId}`, { headers: { 'Accept': 'application/json' } });
      }
      
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }
      
      const json = await res.json();
      
      if (!json || !json.success) {
        console.error('API returned unsuccessful response:', json);
        return;
      }
      renderReviews(json);
    } catch (err) {
      console.error('Failed to load reviews:', err);
    }
  }

  function renderStarRating(container, rating) {
    container.innerHTML = '';
    const r = Math.max(0, Math.min(5, Number(rating || 0)));
    let full = Math.floor(r);
    const frac = r - full;
    const hasHalf = frac >= 0.25 && frac < 0.75 ? 1 : 0;
    if (frac >= 0.75) full += 1;
    const empty = Math.max(0, 5 - full - hasHalf);

    for (let i = 0; i < full; i++) {
      const icon = document.createElement('i');
      icon.className = 'bi bi-star-fill text-warning me-1';
      container.appendChild(icon);
    }
    if (hasHalf) {
      const icon = document.createElement('i');
      icon.className = 'bi bi-star-half text-warning me-1';
      container.appendChild(icon);
    }
    for (let i = 0; i < empty; i++) {
      const icon = document.createElement('i');
      icon.className = 'bi bi-star text-warning me-1';
      container.appendChild(icon);
    }

    const span = document.createElement('small');
    span.className = 'ms-2 text-muted';
    span.textContent = `${r.toFixed(1)} / 5`;
    container.appendChild(span);
  }

  function renderReviews(data) {
    const list = document.querySelector('[data-reviews-list]');
    const avgEl = document.querySelector('[data-product-rating-value]');
    const countEl = document.querySelector('[data-product-reviews-count]');
    const formWrapper = document.querySelector('[data-review-form-hidden]');
    const eligibilityMsg = document.querySelector('[data-review-eligibility-message]');
    const orderInput = document.getElementById('review_order_id');
    const writeLink = document.getElementById('write-review-link');

    // ALWAYS UPDATE RATING/COUNT (PUBLIC DATA)
    const avg = Number(data.avg_rating || 0);
    const count = Number(data.review_count || 0);
    if (avgEl) avgEl.textContent = avg.toFixed(1);
    if (countEl) countEl.textContent = count;

    // Update header rating summary (product-info meta)
    const meta = document.querySelector('[data-product-rating]');
    if (meta) {
      const spans = meta.querySelectorAll('span');
      if (spans[0]) spans[0].textContent = avg.toFixed(1);
      if (spans[1]) spans[1].textContent = '· ' + count + ' reviews';
    }

    // ALWAYS RENDER REVIEWS (PUBLIC DATA)
    if (list) {
      list.innerHTML = '';
      
      if (!data.reviews || !data.reviews.length) {
        list.innerHTML = '<p class="text-muted">No reviews yet.</p>';
      } else {
        data.reviews.forEach(r => {
          const card = document.createElement('div');
          card.className = 'mb-3';
          const header = document.createElement('div');
          header.className = 'd-flex justify-content-between';
          const who = document.createElement('div');
          who.innerHTML = `<strong>${r.user_name ?? 'Anonymous'}</strong> <small class="text-muted ms-2">${new Date(r.created_at).toLocaleDateString()}</small>`;
          const stars = document.createElement('div');
          renderStarRating(stars, r.rating);
          header.appendChild(who);
          header.appendChild(stars);
          const comment = document.createElement('div');
          comment.className = 'mt-1';
          comment.textContent = r.comment || '';
          card.appendChild(header);
          card.appendChild(comment);

          if (parseInt(r.user_id) === parseInt(window.CURRENT_USER_ID || 0)) {
            card.style.background = 'rgba(13,110,253,0.04)';
            card.style.padding = '8px';
            card.style.borderRadius = '6px';
          }

          list.appendChild(card);
        });
      }
    } else {
      console.error('Reviews list element not found!');
    }

    // CONDITIONALLY SHOW REVIEW FORM (GATED LOGIC)
    const canReview = Boolean(data.can_review);

    if (canReview) {
      // User is eligible - show form, hide message
      if (formWrapper) formWrapper.style.display = 'block';
      if (eligibilityMsg) eligibilityMsg.innerHTML = '';
      if (orderInput) orderInput.value = data.candidate_order_id || '';
      if (writeLink) {
        writeLink.classList.remove('d-none');
        writeLink.onclick = (ev) => {
          ev.preventDefault();
          const btn = document.querySelector('[data-tab-target="tab-reviews"]');
          btn?.click();
          setTimeout(() => document.getElementById('review_comment')?.focus(), 250);
          document.getElementById('tab-reviews')?.scrollIntoView({behavior: 'smooth'});
        };
      }
    } else {
      // User is NOT eligible - hide form, show message
      if (formWrapper) formWrapper.style.display = 'none';
      if (writeLink) writeLink.classList.add('d-none');
      
      if (eligibilityMsg) {
        if (data.user_review) {
          // Already reviewed
          eligibilityMsg.textContent = 'You have already reviewed this product.';
        } else if (!window.CURRENT_USER_ID) {
          // Guest user
          eligibilityMsg.innerHTML = 'Please <a href="/login">login</a> to write a review.';
        } else {
          // Logged-in but no completed order
          eligibilityMsg.textContent = 'You can review this product after completing your order.';
        }
      }
    }
  }

  // Bind form submit and star UI
  const form = document.getElementById('review-form');
  if (form) {
    const stars = form.querySelectorAll('#review-stars [data-star]');
    const ratingInput = document.getElementById('review_rating');

    const updateStarButtons = (val) => {
      const r = Math.max(1, Math.min(5, Number(val || 5)));
      let full = Math.floor(r);
      const frac = r - full;
      const half = frac >= 0.25 && frac < 0.75 ? 1 : 0;
      if (frac >= 0.75) full += 1;
      let idx = 0;
      stars.forEach((btn) => {
        const i = idx + 1;
        if (i <= full) {
          btn.innerHTML = '<i class="bi bi-star-fill text-warning"></i>';
        } else if (half && i === full + 1) {
          btn.innerHTML = '<i class="bi bi-star-half text-warning"></i>';
        } else {
          btn.innerHTML = '<i class="bi bi-star text-warning"></i>';
        }
        idx++;
      });
    };

    updateStarButtons(ratingInput?.value || 5);

    stars.forEach(s => s.addEventListener('click', (e) => {
      const base = Number(s.dataset.star || 5);
      const val = e.shiftKey ? Math.max(1, base - 0.5) : base;
      ratingInput.value = val.toFixed(1);
      updateStarButtons(val);
    }));

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      try {
        const res = await fetch('/reviews', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(Object.fromEntries(fd.entries()))
        });
        const json = await res.json();
        if (json && json.success) {
          document.querySelector('[data-review-success]').classList.remove('d-none');
          document.querySelector('[data-review-error]').classList.add('d-none');
          // Disable form to prevent edits
          form.querySelectorAll('input, textarea, button').forEach(el => el.disabled = true);
          // Reload reviews
          renderReviews(json);
          await loadReviews();
        } else {
          document.querySelector('[data-review-error]').classList.remove('d-none');
          document.querySelector('[data-review-error]').textContent = json.message || 'Failed to submit review.';
        }
      } catch (err) {
        console.error(err);
        document.querySelector('[data-review-error]').classList.remove('d-none');
        document.querySelector('[data-review-error]').textContent = 'Failed to submit review.';
      }
    });
  }

  // Load when reviews tab clicked
  reviewsTabBtn.addEventListener('click', () => {
    loadReviews();
  });

  // Pre-load reviews immediately on page load
  setTimeout(loadReviews, 100);
}
