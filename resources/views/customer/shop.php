<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Shop products - E-Commerce Store">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>Shop</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="../assets/css/style-refactored.css">
  <link rel="stylesheet" href="../assets/css/shop.css">
  <link rel="stylesheet" href="../assets/css/cart.css">
</head>
<body>
  <!-- ================= NAVBAR ================= -->
  <?php include __DIR__ . '/../partials/header.php'; ?>

  <main>
    <section class="py-5">
      <div class="container">
        <div class="page-hero">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 position-relative">
            <div class="order-1">
              <h1 class="page-hero__title">Shop</h1>
              <p class="page-hero__subtitle">Find the best products curated for you.</p>
            </div>

            <div class="hero-search-wrapper order-2 w-100 d-flex justify-content-center align-items-center">
              <form class="search-box w-100" role="search" aria-label="Product search" method="get" action="/shop">
                <label for="shop-search" class="visually-hidden">Search products</label>
                <input type="search" id="shop-search" name="search" value="<?= $filters['search'] ?? '' ?>" class="form-control" placeholder="I want to buy..." autocomplete="off">
                <button type="submit" class="btn btn-search" aria-label="Search">
                  <img src="../assets/images/search.svg" alt="" aria-hidden="true" width="20" height="20">
                </button>
              </form>
            </div>

            <div class="d-flex gap-2 align-items-center order-3">
              <button class="btn btn-outline-primary rounded-pill filter-toggle" type="button" data-filter-toggle aria-expanded="false">
                <i class="bi bi-funnel"></i> Filters
              </button>
              <select class="form-select sort-select" data-sort-select aria-label="Sort products">
                <option value="featured" <?= ($filters['sort'] ?? 'featured') === 'featured' ? 'selected' : '' ?>>Sort by: Featured</option>
                <option value="price-asc" <?= ($filters['sort'] ?? '') === 'price-asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price-desc" <?= ($filters['sort'] ?? '') === 'price-desc' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="newest" <?= ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest</option>
              </select>
            </div>
          </div>


        </div>

        <div class="filter-overlay" data-filter-overlay></div>

        <div class="shop-layout">
          <aside class="shop-filters" data-filter-panel tabindex="-1" aria-hidden="true">
            <div class="filter-header">
              <h2 class="filter-title">Filters</h2>
              <button class="btn btn-sm btn-outline-primary rounded-pill" type="button" data-filter-close>Close</button>
            </div>

            <div class="filter-group">
              <p class="filter-label">Categories</p>
              <div class="filter-options">
                <?php
                  $selectedCategories = $filters['category'] ?? [];
                  if (is_string($selectedCategories) && strpos($selectedCategories, ',') !== false) {
                    $selectedCategories = array_filter(array_map('trim', explode(',', $selectedCategories)));
                  }
                  if (!is_array($selectedCategories)) {
                    $selectedCategories = [$selectedCategories];
                  }
                ?>
                <?php if (!empty($categories) && is_array($categories)): ?>
                  <?php foreach ($categories as $c): ?>
                    <label>
                      <input type="checkbox" name="category" value="<?= (int)$c['id'] ?>" <?= in_array((string)$c['id'], $selectedCategories, true) ? 'checked' : '' ?>> <?= $c['name'] ?>
                    </label>
                  <?php endforeach; ?>
                <?php else: ?>
                  <label><input type="checkbox"> General</label>
                <?php endif; ?>
              </div>
            </div>

            <div class="filter-group">
              <p class="filter-label">Price</p>
              <div class="filter-options">
                <?php
                  $selectedPrices = $filters['price'] ?? [];
                  if (is_string($selectedPrices) && strpos($selectedPrices, ',') !== false) {
                    $selectedPrices = array_filter(array_map('trim', explode(',', $selectedPrices)));
                  }
                  if (!is_array($selectedPrices)) {
                    $selectedPrices = [$selectedPrices];
                  }
                ?>
                <label><input type="radio" name="price" <?= in_array('under-100', $selectedPrices, true) ? 'checked' : '' ?>> Under $100</label>
                <label><input type="radio" name="price" <?= in_array('100-500', $selectedPrices, true) ? 'checked' : '' ?>> $100 - $500</label>
                <label><input type="radio" name="price" <?= in_array('500-1000', $selectedPrices, true) ? 'checked' : '' ?>> $500 - $1000</label>
                <label><input type="radio" name="price" <?= in_array('1000-plus', $selectedPrices, true) ? 'checked' : '' ?>> $1000 +</label>
              </div>
            </div>

            <div class="filter-group">
              <p class="filter-label">Ratings</p>
              <div class="filter-options">
                <label><input type="checkbox"> 4 stars & up</label>
                <label><input type="checkbox"> 3 stars & up</label>
                <label><input type="checkbox"> 2 stars & up</label>
              </div>
            </div>
          </aside>

          <div class="shop-content">
            <div class="shop-toolbar">
              <h2 class="section-title m-0"><?= !empty($no_results) ? 'No product found' : 'All Products' ?></h2>
              <div class="d-flex align-items-center gap-2">
                <span class="text-muted"><?= !empty($no_results) ? 'Showing recommended products' : (($pagination['total'] ?? count($products ?? [])) . ' results') ?></span>
              </div>
            </div>

            <!-- TODO: replace with real product list from database -->
            <div class="shop-grid">
              <?php if (!empty($products) && is_array($products)): ?>
                <?php foreach ($products as $p): ?>
                  <?php $stockOut = ((int)($p['quantity'] ?? 0)) <= 0; ?>
                  <article class="product-card">
                    <?php if (!empty($p['featured'])): ?>
                      <div class="product-badge">
                        <i class="bi bi-lightning-charge"></i>
                        Hot
                      </div>
                    <?php endif; ?>
                    <div class="product-card__image">
                      <img src="<?= $p['image'] ?? '../assets/images/laptop.png' ?>" alt="<?= $p['name'] ?>">
                    </div>
                    <h3 class="product-card__title"><?= $p['name'] ?></h3>
                    <?php if (!empty($p['has_discount'])): ?>
                      <p class="product-card__price"><span class="text-muted text-decoration-line-through">$<?= number_format((float)$p['price'], 0, '.', ',') ?></span> <span class="text-primary fw-semibold">$<?= number_format((float)($p['effective_price'] ?? $p['price']), 0, '.', ',') ?></span></p>
                    <?php else: ?>
                      <p class="product-card__price">$<?= number_format((float)($p['effective_price'] ?? $p['price']), 0, '.', ',') ?></p>
                    <?php endif; ?>
                    <?php if (!empty($p['short_description'])): ?>
                      <p class="text-muted mb-2"><?= e($p['short_description']) ?></p>
                    <?php endif; ?>
                    <?php
                      $avg = (float)($p['avg_rating'] ?? 0);
                      $count = (int)($p['review_count'] ?? 0);
                      $full = (int)floor($avg);
                      $frac = $avg - $full;
                      $half = ($frac >= 0.25 && $frac < 0.75) ? 1 : 0;
                      if ($frac >= 0.75) { $full++; }
                      $empty = max(0, 5 - $full - $half);
                    ?>
                    <div class="product-card__meta" aria-label="Rating <?= number_format($avg, 1) ?> out of 5">
                      <?php for ($i = 0; $i < $full; $i++): ?>
                        <i class="bi bi-star-fill text-warning"></i>
                      <?php endfor; ?>
                      <?php if ($half): ?>
                        <i class="bi bi-star-half text-warning"></i>
                      <?php endif; ?>
                      <?php for ($i = 0; $i < $empty; $i++): ?>
                        <i class="bi bi-star text-warning"></i>
                      <?php endfor; ?>
                      <span><?= number_format($avg, 1) ?></span>
                      <span>· <?= $count ?> reviews</span>
                    </div>
                    <div class="product-card__actions">
                      <a class="btn btn-primary rounded-pill" href="/product/<?= rawurlencode($p['slug']) ?>">View Details</a>
                      <button class="btn btn-outline-primary rounded-pill" type="button" data-add-to-cart data-product-id="<?= (int)$p['id'] ?>" data-product-name="<?= $p['name'] ?>" data-product-price="<?= $p['effective_price'] ?? $p['price'] ?>" data-product-image="<?= $p['image'] ?? '../assets/images/laptop.png' ?>" <?= $stockOut ? 'disabled' : '' ?>><?= $stockOut ? 'Out of Stock' : 'Buy' ?></button>
                    </div>
                  </article>
                <?php endforeach; ?>
              <?php else: ?>
                <article class="product-card">
                  <div class="product-card__image">
                    <img src="../assets/images/laptop.png" alt="Laptop">
                  </div>
                  <h3 class="product-card__title">Asus Zenbook UX-430 US</h3>
                  <p class="product-card__price">$1,299</p>
                  <div class="product-card__meta" aria-label="Rating 4.8 out of 5">
                    <i class="bi bi-star-fill text-warning"></i>
                    <span>4.8</span>
                    <span>· 21K reviews</span>
                  </div>
                  <div class="product-card__actions">
                    <a class="btn btn-primary rounded-pill" href="/product/asus-ux430">View Details</a>
                    <button class="btn btn-outline-primary rounded-pill" type="button" data-add-to-cart data-product-id="asus-ux430" data-product-name="Asus Zenbook UX-430 US" data-product-price="1299" data-product-image="../assets/images/laptop.png">Buy</button>
                  </div>
                </article>
              <?php endif; ?>
            </div>

            <nav aria-label="Shop pagination" class="d-flex justify-content-center mt-4">
              <?php if (!empty($pagination) && isset($pagination['last_page'])): ?>
                <ul class="pagination">
                  <?php $current = (int)($pagination['current_page'] ?? 1); $last = (int)($pagination['last_page'] ?? 1); $qs = $_GET; ?>
                  <li class="page-item <?= $current <= 1 ? 'disabled' : '' ?>">
                    <?php $qs['page'] = max(1, $current - 1); ?>
                    <a class="page-link" href="/shop?<?= http_build_query($qs) ?>" aria-label="Previous"><span aria-hidden="true">&lt;</span><span class="visually-hidden">Previous</span></a>
                  </li>
                  <?php for ($i = 1; $i <= $last; $i++): ?>
                    <?php $qs['page'] = $i; ?>
                    <li class="page-item <?= $i === $current ? 'active' : '' ?>" <?= $i === $current ? 'aria-current="page"' : '' ?>><a class="page-link" href="/shop?<?= http_build_query($qs) ?>"><?= $i ?></a></li>
                  <?php endfor; ?>
                  <li class="page-item <?= $current >= $last ? 'disabled' : '' ?>">
                    <?php $qs['page'] = min($last, $current + 1); ?>
                    <a class="page-link" href="/shop?<?= http_build_query($qs) ?>" aria-label="Next"><span aria-hidden="true">&gt;</span><span class="visually-hidden">Next</span></a>
                  </li>
                </ul>
              <?php endif; ?>
            </nav>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Page-specific scripts -->
  <script src="../assets/js/search.js"></script>
  <script src="../assets/js/shop.js"></script>

  <!-- ================= FOOTER ================= -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>
