<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Browse millions of products for your needs - E-Commerce Store">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>E-Commerce Landing</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="../assets/css/style-refactored.css">
  <link rel="stylesheet" href="../assets/css/cart.css">
</head>
<body>

  <!-- ================= NAVBAR ================= -->
  <?php include __DIR__ . '/../partials/header.php'; ?>

  <main>
<!-- ================= HERO ================= -->
<section class="hero-section" aria-labelledby="hero-title">
  <div class="container">
    <div class="hero-wrapper">
      <div class="row align-items-center">
        <div class="col-lg-6 hero-content">
          <h1 id="hero-title" class="hero-title">
            Browse Million<br>
            Products for Your Needs
          </h1>

          <form class="search-box mt-4" role="search" aria-label="Product search">
            <label for="hero-search" class="visually-hidden">Search products</label>
            <input type="search" id="hero-search" class="form-control" placeholder="I want to buy..." autocomplete="off">
            <button type="submit" class="btn btn-search" aria-label="Search">
              <img src="../assets/images/search.svg" alt="" aria-hidden="true" width="20" height="20">
            </button>
          </form>
        </div>

        <div class="col-lg-6 text-center mt-4 mt-lg-0">
          <img src="../assets/images/Shopping Checkout.png" class="img-fluid hero-image" alt="Shopping checkout illustration" loading="eager">
        </div>
      </div>
    </div>
  </div>
</section>

    <!-- ================= WEEKLY DEALS ================= -->
    <!-- 
      RESPONSIVE: 3-column on desktop, stacks vertically on mobile
      Flexbox-based layout with proportional sizing
    -->
    <section class="weekly-deals-section" aria-labelledby="weekly-deals-title">
      <div class="container">
        <h2 id="weekly-deals-title" class="visually-hidden">Weekly Deals</h2>
        
        <div class="deals-wrapper">
          
          <!-- Left Card - Featured Deal -->
          <article class="deal-card deal-card--featured">
            <div class="deal-card__content">
              <span class="deal-card__tag">Weekly Deals</span>
              <h3 class="deal-card__title">Free Delivery</h3>
              <a href="/shop" class="deal-card__link">Learn More →</a>
            </div>
            <img
              src="../assets/images/Delivery.png"
              alt="Free delivery service"
              class="deal-card__image deal-card__image--large"
              loading="lazy"
            >
          </article>

          <!-- Middle Column - Two Stacked Cards -->
          <div class="deals-middle">
            
            <article class="deal-card deal-card--horizontal">
              <div class="deal-card__content">
                <h3 class="deal-card__subtitle">Disc Up to 25%</h3>
                <a href="/shop" class="deal-card__link">Learn More →</a>
              </div>
              <img
                src="../assets/images/Shopping Cart Mobile.png"
                alt="Shopping cart with discount"
                class="deal-card__image deal-card__image--medium"
                loading="lazy"
              >
            </article>

            <article class="deal-card deal-card--horizontal deal-card--reverse">
              <div class="deal-card__content">
                <h3 class="deal-card__subtitle">Free 5GB<br>Data</h3>
                <a href="/shop" class="deal-card__link">Learn More →</a>
              </div>
                <img
                src="../assets/images/Call Center.png"
                alt="Customer support"
                class="deal-card__image deal-card__image--medium"
                loading="lazy"
                >
            </article>
          </div>

          <!-- Right Card -->
          <article class="deal-card deal-card--vertical">
            <div class="deal-card__content text-center">
              <h3 class="deal-card__subtitle">Anniversary<br>Monthly Deals</h3>
              <a href="/shop" class="">Learn More →</a>
            </div>
            <img
              src="../assets/images/Welcome Screen For E Commerce.png"
              alt="E-commerce welcome screen"
              class="deal-card__image deal-card__image--bottom"
              loading="lazy"
            >
          </article>
        </div>
      </div>
    </section>

    <!-- ================= CATEGORIES ================= -->
    <!-- 
      RESPONSIVE: 8 columns on desktop, 4 on tablet, 2 on mobile
      Cards wrap naturally with consistent sizing
    -->
    <section class="categories-section" aria-labelledby="categories-title">
      <div class="container">
        
        <!-- Section Header -->
        <header class="section-header">
          <h2 id="categories-title" class="section-title">Categories</h2>
          <a href="/shop" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            Show All
          </a>
        </header>

        <!-- Category Grid -->
        <!-- TODO: replace categories with dynamic categories from database -->
        <div class="categories-grid" role="list">
          <?php if (!empty($categories) && is_array($categories)): ?>
            <?php foreach ($categories as $cat): ?>
              <?php $iconSrc = $cat['icon_url'] ?? ($cat['image'] ?? '/assets/images/Fashion.svg'); ?>
              <?php if (empty($iconSrc)) { $iconSrc = '/assets/images/Fashion.svg'; } ?>
              <?php $iconSrc = preg_match('#^https?://#i', $iconSrc) || strpos($iconSrc, '/') === 0 ? $iconSrc : ('/' . ltrim($iconSrc, '/')); ?>
              <article class="category-card" role="listitem">
                <img src="<?= $iconSrc ?>" alt="" aria-hidden="true" width="40" height="40">
                <h3 class="category-card__title"><?= $cat['name'] ?></h3>
                <p class="category-card__count"><?= (int)($cat['product_count'] ?? 0) ?> Items</p>
              </article>
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Fallback to a single placeholder to preserve layout -->
            <article class="category-card" role="listitem">
              <img src="" alt="" aria-hidden="true" width="40" height="40">
              <h3 class="category-card__title">General</h3>
              <p class="category-card__count">0 Items</p>
            </article>
          <?php endif; ?>
        </div> 
      </div>
    </section>

    <!-- ================= RECOMMENDED FOR YOU ================= -->
    <!-- 
      RESPONSIVE: 4 cards on desktop, 2 on tablet, 1 on mobile
      Consistent card sizing with fluid images
    -->
    <section class="recommended-section" aria-labelledby="recommended-title">
      <div class="container">
        
        <!-- Section Header -->
        <header class="section-header">
          <h2 id="recommended-title" class="section-title">Recommended for You</h2>
          <a href="/shop" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            Show All
          </a>
        </header>

        <!-- TODO: replace with real products from database -->
        <!-- Recommended Cards -->
        <div class="recommended-grid">
          <?php if (!empty($recommended) && is_array($recommended)): ?>
            <?php foreach ($recommended as $p): ?>
              <?php $stockOut = ((int)($p['quantity'] ?? 0)) <= 0; ?>
              <article class="recommend-card">
                <div class="recommend-card__image bg-light-blue">
                  <img src="<?= $p['image'] ?? '../assets/images/cream.png' ?>" alt="<?= $p['name'] ?>" loading="lazy">
                </div>
                <p class="recommend-card__text">
                  <?= $p['name'] ?><?= $stockOut ? ' • Out of Stock' : '' ?>
                </p>
              </article>
            <?php endforeach; ?>
          <?php else: ?>
            <article class="recommend-card">
              <div class="recommend-card__image bg-light-blue">
                <img src="../assets/images/cream.png" alt="Anti Aging Cream" loading="lazy">
              </div>
              <p class="recommend-card__text">
                The best Anti Aging Cream with cheap price
              </p>
            </article>
          <?php endif; ?>
        </div> 

        <!-- Section Divider -->
        <hr class="section-divider">
      </div>
    </section>

    <!-- ================= WHY CHOOSE US ================= -->
    <!-- 
      RESPONSIVE: Image left, cards right on desktop; stacks on mobile
      Cards in 2x2 grid on tablet+, single column on mobile
    -->
    <section class="why-choose-section" id="about" aria-labelledby="why-choose-title">
      <div class="container">
        
        <!-- Section Header -->
        <header class="text-center mb-5">
          <h2 id="why-choose-title" class="section-title--centered">
            Why the Choose us than other?
          </h2>
          <p class="section-subtitle">
            Many reasons why customer choose us than other ecommerce.
            We have some plus point that maybe other can't have.
          </p>
        </header>

        <div class="row align-items-center g-4">
          
          <!-- Illustration -->
          <div class="col-lg-6 text-center order-2 order-lg-1">
            <img 
              src="../assets/images/Finance Accounting.png" 
              class="img-fluid why-choose-image" 
              alt="Finance and accounting illustration"
              loading="lazy"
            >
          </div>

          <!-- Feature Cards -->
          <div class="col-lg-6 order-1 order-lg-2">
            <div class="row g-3 g-md-4">
              
              <div class="col-6">
                <article class="feature-card">
                  <div class="feature-card__icon">
                    <i class="bi bi-box-seam" aria-hidden="true"></i>
                  </div>
                  <h3 class="feature-card__title">Have Most Stock</h3>
                  <p class="feature-card__text">
                    We have many stock until next year to supply your needs.
                  </p>
                </article>
              </div>

              <div class="col-6">
                <article class="feature-card">
                  <div class="feature-card__icon">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                  </div>
                  <h3 class="feature-card__title">100% Secure</h3>
                  <p class="feature-card__text">
                    You don't need to worry when transaction on our platform now.
                  </p>
                </article>
              </div>

              <div class="col-6">
                <article class="feature-card">
                  <div class="feature-card__icon">
                    <i class="bi bi-headset" aria-hidden="true"></i>
                  </div>
                  <h3 class="feature-card__title">24/7 Support</h3>
                  <p class="feature-card__text">
                    If any problem use our platform you can contact us free.
                  </p>
                </article>
              </div>

              <div class="col-6">
                <article class="feature-card">
                  <div class="feature-card__icon">
                    <i class="bi bi-truck" aria-hidden="true"></i>
                  </div>
                  <h3 class="feature-card__title">Free Delivery</h3>
                  <p class="feature-card__text">
                    Wherever you are, we make sure you get free delivery service.
                  </p>
                </article>
              </div>

            </div>
          </div>
        </div>

        <!-- Section Divider -->
        <hr class="section-divider">
      </div>
    </section>

    <!-- ================= MOST SOLD ================= -->
    <!-- 
      RESPONSIVE: Horizontal cards on desktop, stack vertically on mobile
      Buttons and info adjust spacing accordingly
    -->
    <section class="most-sold-section" aria-labelledby="most-sold-title">
      <div class="container">
        
        <!-- Section Header -->
        <header class="text-center mb-5">
          <h2 id="most-sold-title" class="section-title--centered">
            Most Sold in Ehya Store
          </h2>
          <p class="section-subtitle">
            This is the section about the data which product with most sold in Ehya Store.
          </p>
        </header>

        <!-- Product List -->
        <!-- TODO: replace with dynamic "most sold" products later -->
        <div class="most-sold-list">
          <?php if (!empty($most_sold) && is_array($most_sold)): ?>
            <?php foreach ($most_sold as $p): ?>
              <?php $stockOut = ((int)($p['quantity'] ?? 0)) <= 0; ?>
              <article class="product-row">
                <img src="<?= $p['image'] ?? '../assets/images/laptop.png' ?>" class="product-row__image" alt="<?= $p['name'] ?>" loading="lazy">
                <div class="product-row__info">
                  <h3 class="product-row__title"><?= $p['name'] ?></h3>
                  <?php
                    $avg = (float)($p['avg_rating'] ?? 0);
                    $count = (int)($p['review_count'] ?? 0);
                    $full = (int)floor($avg);
                    $frac = $avg - $full;
                    $half = ($frac >= 0.25 && $frac < 0.75) ? 1 : 0;
                    if ($frac >= 0.75) { $full++; }
                    $empty = max(0, 5 - $full - $half);
                  ?>
                  <div class="product-row__rating" aria-label="Rating <?= number_format($avg, 1) ?> out of 5">
                    <?php for ($i = 0; $i < $full; $i++): ?>
                      <i class="bi bi-star-fill text-warning"></i>
                    <?php endfor; ?>
                    <?php if ($half): ?>
                      <i class="bi bi-star-half text-warning"></i>
                    <?php endif; ?>
                    <?php for ($i = 0; $i < $empty; $i++): ?>
                      <i class="bi bi-star text-warning"></i>
                    <?php endfor; ?>
                    <span class="ms-2 product-row__score"><?= number_format($avg, 1) ?></span>
                  </div>
                  <small class="product-row__reviews"><?= $count ?> reviews</small>
                </div>
                <div class="product-row__actions">
                  <a href="/product/<?= rawurlencode($p['slug']) ?>" class="btn btn-primary btn-sm rounded-pill">Read Reviews</a>
                  <button class="btn btn-outline-primary btn-sm rounded-pill" type="button" data-add-to-cart data-product-id="<?= (int)$p['id'] ?>" data-product-name="<?= $p['name'] ?>" data-product-price="<?= $p['effective_price'] ?? $p['price'] ?>" data-product-image="<?= $p['image'] ?? '../assets/images/laptop.png' ?>" <?= $stockOut ? 'disabled' : '' ?>>
                    <i class="bi bi-cart" aria-hidden="true"></i> <?= $stockOut ? 'Out of Stock' : 'Buy' ?>
                  </button>
                </div>
              </article>
            <?php endforeach; ?>
          <?php else: ?>
            <article class="product-row">
              <img src="../assets/images/laptop.png" class="product-row__image" alt="Asus Zenbook" loading="lazy">
              <div class="product-row__info">
                <h3 class="product-row__title">Asus Zenbook UX-430 US</h3>
                <div class="product-row__rating" aria-label="Rating 4.8 out of 5">
                  <span class="product-row__score">4,8</span>
                  <span class="product-row__stars" aria-hidden="true">★★★★☆</span>
                </div>
                <small class="product-row__reviews">21K Total Reviews</small>
              </div>
              <div class="product-row__actions">
                <a href="/product/asus-ux430" class="btn btn-primary btn-sm rounded-pill">Read Reviews</a>
                <button class="btn btn-outline-primary btn-sm rounded-pill" type="button" data-add-to-cart data-product-id="asus-ux430" data-product-name="Asus Zenbook UX-430 US" data-product-price="1299" data-product-image="../assets/images/laptop.png">
                  <i class="bi bi-cart" aria-hidden="true"></i> Buy
                </button>
              </div>
            </article>
          <?php endif; ?>
        </div>

        <!-- Footer Link -->
        <div class="text-center mt-4">
          <a href="/shop" class="link-primary fw-medium">See full Leaderboards</a>
        </div>
      </div>
    </section>

    <!-- ================= COMPARE PRODUCTS ================= -->
    <!-- 
      RESPONSIVE: 4 cards on desktop, 2 on tablet, 1 on mobile
      Expandable details with JS toggle
    -->
    <section class="compare-section" aria-labelledby="compare-title">
      <div class="container">
        
        <!-- Section Header -->
        <header class="section-header">
          <h2 id="compare-title" class="section-title">Compare the Product</h2>
          <a href="/shop" class="btn btn-outline-primary btn-sm rounded-pill px-4">
            + New Comparison
          </a>
        </header>

        <!-- Compare Cards Grid -->
        <div class="compare-grid">
          
          <!-- Card 1 -->
          <article class="compare-card" data-card>
            <img src="../assets/images/laptop.png" class="compare-card__image" alt="Asus Zenbook Pro" loading="lazy">
            <h3 class="compare-card__title">Asus Zenbook Pro<br>UX-430 US</h3>

            <div class="compare-card__feature">
              <div class="compare-card__icon">
                <i class="bi bi-cpu" aria-hidden="true"></i>
              </div>
              <h4 class="compare-card__feature-title">Processor</h4>
              <p class="compare-card__feature-text">Intel® Core™ i3 7100U Processor</p>
            </div>

            <div class="compare-card__feature">
              <div class="compare-card__icon">
                <i class="bi bi-windows" aria-hidden="true"></i>
              </div>
              <h4 class="compare-card__feature-title">Operating System</h4>
              <p class="compare-card__feature-text">Windows 10 Pro for business</p>
            </div>

            <button class="compare-card__toggle" data-toggle aria-expanded="false" aria-label="Show more details">
              <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>

            <div class="compare-card__details" aria-hidden="true">
              <hr>
              <p><strong>RAM:</strong> 8GB DDR4</p>
              <p><strong>Storage:</strong> 256GB SSD</p>
              <p><strong>Display:</strong> 14" Full HD</p>
            </div>
          </article>

          <!-- Card 2 -->
          <article class="compare-card" data-card>
            <img src="../assets/images/asuszenbokpro.png" class="compare-card__image" alt="Lenovo Legion" loading="lazy">
            <h3 class="compare-card__title">Lenovo Legion<br>Y545 2018</h3>

            <div class="compare-card__feature">
              <div class="compare-card__icon">
                <i class="bi bi-cpu" aria-hidden="true"></i>
              </div>
              <h4 class="compare-card__feature-title">Processor</h4>
              <p class="compare-card__feature-text">Intel® Core™ i7 9100P Processor</p>
            </div>

            <div class="compare-card__feature">
              <div class="compare-card__icon">
                <i class="bi bi-windows" aria-hidden="true"></i>
              </div>
              <h4 class="compare-card__feature-title">Operating System</h4>
              <p class="compare-card__feature-text">Windows 10 Pro Enterprise</p>
            </div>

            <button class="compare-card__toggle" data-toggle aria-expanded="false" aria-label="Show more details">
              <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>

            <div class="compare-card__details" aria-hidden="true">
              <hr>
              <p><strong>RAM:</strong> 16GB DDR4</p>
              <p><strong>Storage:</strong> 512GB SSD</p>
              <p><strong>Display:</strong> 15.6" Full HD</p>
            </div>
          </article>

          <!-- Card 3 -->
          <article class="compare-card" data-card>
            <img src="../assets/images/acer.png" class="compare-card__image" alt="Acer Swift Air" loading="lazy">
            <h3 class="compare-card__title">Acer Swift Air<br>SF-313 S1</h3>

            <div class="compare-card__feature">
              <div class="compare-card__icon">
                <i class="bi bi-cpu" aria-hidden="true"></i>
              </div>
              <h4 class="compare-card__feature-title">Processor</h4>
              <p class="compare-card__feature-text">Intel® Core™ i3 7100X Processor</p>
            </div>

            <div class="compare-card__feature">
              <div class="compare-card__icon">
                <i class="bi bi-windows" aria-hidden="true"></i>
              </div>
              <h4 class="compare-card__feature-title">Operating System</h4>
              <p class="compare-card__feature-text">Windows 10 Pro for business</p>
            </div>

            <button class="compare-card__toggle" data-toggle aria-expanded="false" aria-label="Show more details">
              <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>

            <div class="compare-card__details" aria-hidden="true">
              <hr>
              <p><strong>RAM:</strong> 8GB LPDDR4</p>
              <p><strong>Storage:</strong> 256GB SSD</p>
              <p><strong>Display:</strong> 13.3" Retina</p>
            </div>
          </article>

          <!-- Card 4 -->
          <article class="compare-card" data-card>
            <img src="../assets/images/lenevo2.png" class="compare-card__image" alt="Lenovo Thinkpad" loading="lazy">
            <h3 class="compare-card__title">Lenovo Thinkpad Y51<br>X1 2019</h3>

            <div class="compare-card__feature">
              <div class="compare-card__icon">
                <i class="bi bi-cpu" aria-hidden="true"></i>
              </div>
              <h4 class="compare-card__feature-title">Processor</h4>
              <p class="compare-card__feature-text">Intel® Core™ i5 8000C Processor</p>
            </div>

            <div class="compare-card__feature">
              <div class="compare-card__icon">
                <i class="bi bi-windows" aria-hidden="true"></i>
              </div>
              <h4 class="compare-card__feature-title">Operating System</h4>
              <p class="compare-card__feature-text">Windows 10 Pro for business</p>
            </div>

            <button class="compare-card__toggle" data-toggle aria-expanded="false" aria-label="Show more details">
              <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>

            <div class="compare-card__details" aria-hidden="true">
              <hr>
              <p><strong>RAM:</strong> 16GB DDR4</p>
              <p><strong>Storage:</strong> 1TB SSD</p>
              <p><strong>Display:</strong> 14" UHD</p>
            </div>
          </article>

        </div>
      </div>
    </section>

    <!-- ================= PROMO NEWSLETTER ================= -->
    <!-- 
      RESPONSIVE: Image left, form right on desktop; stacks on mobile
      Form adapts from inline to stacked on small screens
    -->
    <section class="promo-section" aria-labelledby="promo-title">
      <div class="container">
        <div class="promo-box">
          <div class="row align-items-center">
            
            <!-- Promo Image -->
            <div class="col-lg-5 text-center order-2 order-lg-1">
              <img
                src="../assets/images/Foreign Exchange Market.png"
                alt="Never miss a promo illustration"
                class="promo-image img-fluid"
                loading="lazy"
              >
            </div>

            <!-- Promo Content -->
            <div class="col-lg-7 order-1 order-lg-2 text-center text-lg-start mb-4 mb-lg-0">
              <h2 id="promo-title" class="promo-title">Never Miss a Promo</h2>
              <p class="promo-text">
                We always give our customer a promo to give the
                appreciate for loyalty to us. Just subscribe to us :)
              </p>

              <form class="promo-form" aria-label="Newsletter subscription">
                <label for="promo-email" class="visually-hidden">Email address</label>
                <input
                  type="email"
                  id="promo-email"
                  class="form-control promo-input"
                  placeholder="yourname@mail.com"
                  required
                  autocomplete="email"
                >
                <button type="submit" class="btn promo-btn">
                  Subscribe
                </button>
              </form>
            </div>

          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- ================= FOOTER ================= -->
  <!-- 
    RESPONSIVE: 5 columns on desktop, reflows to 2-3 columns on tablet, stacks on mobile
    Consistent spacing and typography across breakpoints
  -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>
  
  <!-- Live Search Script -->
  <script src="../assets/js/search.js"></script>
