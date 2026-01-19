<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Product detail">
  <title>Product</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="../assets/css/style-refactored.css">
  <link rel="stylesheet" href="../assets/css/product.css">
  <link rel="stylesheet" href="../assets/css/cart.css">
</head>
<body>
  <!-- ================= NAVBAR ================= -->
  <?php include __DIR__ . '/../partials/header.php'; ?>

  <main>
    <section class="py-5">
      <div class="container product-page">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
            <li class="breadcrumb-item active" aria-current="page" data-breadcrumb-current>Asus Zenbook UX-430 US</li>
          </ol>
        </nav>

        <!-- TODO: Replace product fields with real product data later -->
        <div class="product-summary">
          <div class="product-media">
            <div class="product-media__main">
              <img src="../assets/images/laptop.png" alt="Asus Zenbook" data-product-main>
            </div>
            <div class="product-media__thumbs" data-product-thumbs></div>
          </div>

          <div class="product-info">
            <h1 class="product-info__title" data-product-title>Asus Zenbook UX-430 US</h1>
            <p class="product-info__price" data-product-price>$1,299</p>
            <div class="product-info__meta" data-product-rating aria-label="Rating 4.8 out of 5">
              <i class="bi bi-star-fill text-warning"></i>
              <span>4.8</span>
              <span>· 21K reviews</span>
            </div>
            <p class="text-muted" data-product-description>Slim, light, and powerful with Intel® Core™ processors and vibrant display for productivity and entertainment.</p>

            <div class="d-flex align-items-center gap-3">
              <div class="product-qty" data-qty data-qty-wrapper>
                <button type="button" data-action="dec" aria-label="Decrease quantity">-</button>
                <input type="text" value="1" inputmode="numeric" aria-label="Quantity" data-qty-input>
                <button type="button" data-action="inc" aria-label="Increase quantity">+</button>
              </div>
              <div class="product-actions">
                <button class="btn btn-primary rounded-pill" type="button" data-add-to-cart>Add to Cart</button>
                <a class="btn btn-outline-primary rounded-pill" href="checkout.php">Buy Now</a>
              </div>
            </div>

            <ul class="list-unstyled text-muted mt-3 mb-0">
              <li><i class="bi bi-check-circle text-primary"></i> Free delivery</li>
              <li><i class="bi bi-check-circle text-primary"></i> 30-day return policy</li>
              <li><i class="bi bi-check-circle text-primary"></i> 24/7 customer support</li>
            </ul>
          </div>
        </div>

        <div class="product-tabs mt-4">
          <div class="tab-list" role="tablist">
            <button class="tab-button is-active" type="button" data-tab-target="tab-description">Description</button>
            <button class="tab-button" type="button" data-tab-target="tab-reviews">Reviews</button>
          </div>
          <div id="tab-description" class="tab-panel is-active" data-tab-panel>
            <p>Experience premium craftsmanship with the Asus Zenbook UX-430 US. Designed for mobility, it features a vivid 14" Full HD display, fast SSD storage, and long-lasting battery life so you can create, stream, and collaborate without compromise.</p>
            <ul>
              <li>Intel® Core™ i3 7100U Processor</li>
              <li>8GB DDR4 RAM, 256GB SSD</li>
              <li>14" Full HD display</li>
            </ul>
          </div>
          <div id="tab-reviews" class="tab-panel" data-tab-panel>
            <p class="mb-2">Average rating <strong data-product-rating-value>4.8</strong> from <span data-product-reviews>21K reviews</span>.</p>
            <p class="mb-0">“Amazing build quality and battery life. Perfect for remote work.”</p>
          </div>
        </div>

        <section class="related-products">
          <div class="section-header">
            <h2 class="section-title">Related Products</h2>
            <a href="shop.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">Show All</a>
          </div>
          <!-- TODO: fill related items dynamically -->
          <div class="shop-grid" data-related-grid></div>
        </section>
      </div>
    </section>
  </main>

  <!-- Page-specific scripts -->
  <script src="../assets/js/product.js"></script>

  <!-- ================= FOOTER ================= -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>
