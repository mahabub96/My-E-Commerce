const products = [
  {
    id: 'asus-ux430',
    name: 'Asus Zenbook UX-430 US',
    price: 1299,
    image: '../assets/images/laptop.png',
    gallery: ['../assets/images/laptop.png', '../assets/images/asuszenbokpro.png', '../assets/images/acer.png'],
    rating: 4.8,
    reviews: '21K reviews',
    blurb: 'Slim, light, and powerful with Intel® Core™ processors and vibrant display for productivity and entertainment.'
  },
  {
    id: 'audio-ath-m20',
    name: 'Audio Technica ATH M20 BT',
    price: 199,
    image: '../assets/images/headphone.png',
    gallery: ['../assets/images/headphone.png', '../assets/images/headphone.png'],
    rating: 5.0,
    reviews: '300K reviews',
    blurb: 'Wireless freedom with studio-grade clarity and long battery life for daily listening.'
  },
  {
    id: 'sk-ii-cream',
    name: 'SK II - Anti Aging Cream',
    price: 79,
    image: '../assets/images/cream.png',
    gallery: ['../assets/images/cream.png'],
    rating: 4.9,
    reviews: '89K reviews',
    blurb: 'Hydrating anti-aging cream that delivers smoother, radiant skin day after day.'
  },
  {
    id: 'modena-blender',
    name: 'Modena Juice Blender',
    price: 129,
    image: '../assets/images/blender.png',
    gallery: ['../assets/images/blender.png'],
    rating: 4.8,
    reviews: '871 reviews',
    blurb: 'Powerful yet compact blender for smoothies, soups, and everyday kitchen prep.'
  },
  {
    id: 'acer-swift',
    name: 'Acer Swift Air SF-313',
    price: 999,
    image: '../assets/images/acer.png',
    gallery: ['../assets/images/acer.png'],
    rating: 4.7,
    reviews: '12K reviews',
    blurb: 'Featherweight laptop with vivid display and all-day battery for work on the go.'
  },
  {
    id: 'lenovo-thinkpad',
    name: 'Lenovo Thinkpad Y51 X1',
    price: 1499,
    image: '../assets/images/lenevo2.png',
    gallery: ['../assets/images/lenevo2.png'],
    rating: 4.8,
    reviews: '8K reviews',
    blurb: 'Business-ready performance with durable build, fast storage, and crisp display.'
  }
];

document.addEventListener('DOMContentLoaded', () => {
  initQuantityControls();
  initTabs();
  hydrateProductPage();
});

function initQuantityControls() {
  document.querySelectorAll('[data-qty]').forEach(wrapper => {
    const input = wrapper.querySelector('input');
    wrapper.querySelectorAll('button').forEach(button => {
      button.addEventListener('click', () => {
        const dir = button.dataset.action === 'inc' ? 1 : -1;
        const value = Math.max(1, (parseInt(input.value, 10) || 1) + dir);
        input.value = value;
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

function hydrateProductPage() {
  const params = new URLSearchParams(window.location.search);
  const requestedId = params.get('id');
  const product = products.find(item => item.id === requestedId) || products[0];

  if (!product) return;

  const titleEl = document.querySelector('[data-product-title]');
  const priceEl = document.querySelector('[data-product-price]');
  const ratingEl = document.querySelector('[data-product-rating]');
  const ratingValueEl = document.querySelector('[data-product-rating-value]');
  const descriptionEl = document.querySelector('[data-product-description]');
  const reviewsEl = document.querySelector('[data-product-reviews]');
  const mainImage = document.querySelector('[data-product-main]');
  const thumbContainer = document.querySelector('[data-product-thumbs]');
  const addToCartBtn = document.querySelector('[data-add-to-cart]');
  const breadcrumbCurrent = document.querySelector('[data-breadcrumb-current]');

  if (titleEl) titleEl.textContent = product.name;
  if (priceEl) priceEl.textContent = `$${product.price.toLocaleString()}`;
  if (ratingEl) ratingEl.innerHTML = `<i class="bi bi-star-fill text-warning"></i><span>${product.rating}</span><span>· ${product.reviews}</span>`;
  if (ratingValueEl) ratingValueEl.textContent = product.rating;
  if (reviewsEl) reviewsEl.textContent = product.reviews;
  if (descriptionEl) descriptionEl.textContent = product.blurb;
  if (breadcrumbCurrent) breadcrumbCurrent.textContent = product.name;

  renderGallery(product, mainImage, thumbContainer);
  renderRelated(product.id);

  if (addToCartBtn) {
    addToCartBtn.dataset.productId = product.id;
    addToCartBtn.dataset.productName = product.name;
    addToCartBtn.dataset.productPrice = product.price;
    addToCartBtn.dataset.productImage = product.image;
  }
}

function renderGallery(product, mainImage, thumbContainer) {
  if (!mainImage || !thumbContainer) return;
  mainImage.setAttribute('src', product.image);
  mainImage.setAttribute('alt', product.name);

  thumbContainer.innerHTML = '';

  product.gallery.forEach((src, index) => {
    const button = document.createElement('button');
    button.className = 'product-media__thumb';
    button.type = 'button';
    button.dataset.productThumb = '';
    button.dataset.src = src;
    button.innerHTML = `<img src="${src}" alt="${product.name} view ${index + 1}">`;
    button.addEventListener('click', () => {
      mainImage.setAttribute('src', src);
      mainImage.setAttribute('alt', `${product.name} view ${index + 1}`);
    });
    thumbContainer.appendChild(button);
  });
}

function renderRelated(activeId) {
  const grid = document.querySelector('[data-related-grid]');
  if (!grid) return;

  grid.innerHTML = '';
  const related = products.filter(item => item.id !== activeId).slice(0, 4);

  related.forEach(item => {
    const article = document.createElement('article');
    article.className = 'product-card';
    article.innerHTML = `
      <div class="product-card__image"><img src="${item.image}" alt="${item.name}"></div>
      <h3 class="product-card__title">${item.name}</h3>
      <p class="product-card__price">$${item.price.toLocaleString()}</p>
      <div class="product-card__meta" aria-label="Rating ${item.rating} out of 5">
        <i class="bi bi-star-fill text-warning"></i><span>${item.rating}</span><span>· ${item.reviews}</span>
      </div>
      <div class="product-card__actions">
        <a class="btn btn-primary rounded-pill" href="product.html?id=${item.id}">View Details</a>
        <button class="btn btn-outline-primary rounded-pill" type="button" data-add-to-cart data-product-id="${item.id}" data-product-name="${item.name}" data-product-price="${item.price}" data-product-image="${item.image}">Buy</button>
      </div>
    `;
    grid.appendChild(article);
  });
}
