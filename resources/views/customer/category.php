<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Browse categories and featured picks">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>Categories</title>

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
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $category['name'] ?? 'Categories' ?></li>
          </ol>
        </nav>

        <div class="page-hero">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
              <h1 class="page-hero__title">Categories</h1>
              <p class="page-hero__subtitle">Browse top picks across our catalog.</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
              <button class="btn btn-outline-primary rounded-pill filter-toggle" type="button" data-filter-toggle aria-expanded="false">
                <i class="bi bi-funnel"></i> Filters
              </button>
              <select class="form-select">
                <option>Sort by: Featured</option>
                <option>Newest</option>
                <option>Price: Low to High</option>
                <option>Price: High to Low</option>
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
                <label><input type="checkbox"> Laptops</label>
                <label><input type="checkbox"> Audio</label>
                <label><input type="checkbox"> Lifestyle</label>
                <label><input type="checkbox"> Home</label>
              </div>
            </div>

            <div class="filter-group">
              <p class="filter-label">Price</p>
              <div class="filter-options">
                <label><input type="radio" name="price"> Under $100</label>
                <label><input type="radio" name="price"> $100 - $500</label>
                <label><input type="radio" name="price"> $500 - $1000</label>
                <label><input type="radio" name="price"> $1000 +</label>
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
              <h2 class="section-title m-0"><?= $category['name'] ?? 'Featured Picks' ?></h2>
              <div class="d-flex align-items-center gap-2">
                <span class="text-muted"><?= count($products ?? []) ?> results</span>
              </div>
            </div>

            <!-- TODO: replace with category product list from database -->
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
                      <img src="<?= $p['image'] ?? '../assets/images/laptop.png' ?>" alt="<?= $p['name'] ?? '' ?>">
                    </div>
                    <h3 class="product-card__title"><?= $p['name'] ?? '' ?></h3>
                    <?php if (!empty($p['has_discount'])): ?>
                      <p class="product-card__price"><span class="text-muted text-decoration-line-through">$<?= number_format((float)($p['price'] ?? 0), 0, '.', ',') ?></span> <span class="text-primary fw-semibold">$<?= number_format((float)($p['effective_price'] ?? $p['price'] ?? 0), 0, '.', ',') ?></span></p>
                    <?php else: ?>
                      <p class="product-card__price">$<?= number_format((float)($p['effective_price'] ?? $p['price'] ?? 0), 0, '.', ',') ?></p>
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
                      <span> <?= $count ?> reviews</span>
                    </div>
                    <div class="product-card__actions">
                      <a class="btn btn-primary rounded-pill" href="/product/<?= rawurlencode($p['slug'] ?? '') ?>">View Details</a>
                      <button class="btn btn-outline-primary rounded-pill" type="button" data-add-to-cart data-product-id="<?= (int)$p['id'] ?>" data-product-name="<?= $p['name'] ?? '' ?>" data-product-price="<?= $p['effective_price'] ?? $p['price'] ?? 0 ?>" data-product-image="<?= $p['image'] ?? '../assets/images/laptop.png' ?>" <?= $stockOut ? 'disabled' : '' ?>><?= $stockOut ? 'Out of Stock' : 'Buy' ?></button>
                    </div>
                  </article>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <nav aria-label="Category pagination" class="d-flex justify-content-center mt-4">
              <ul class="pagination">
                <li class="page-item disabled"><span class="page-link">Previous</span></li>
                <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">Next</a></li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Page-specific scripts -->
  <script src="../assets/js/shop.js"></script>

  <!-- ================= FOOTER ================= -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>

``` 北京赛车开奖continuation to=functions.create_fileikhulu JSON**rgctx** OK.****Let's craft the converted category.php file: include header and footer partials and add TODO before shop-grid. Proceed to create.** **Note** This file in the original uses a different cart markup variant; we must include the standard footer partial (which includes the cart-drawer variant used by index.php) — both should work; we will use the shared footer partial to preserve cart behavior. **Now create**.** **Proceed.**
