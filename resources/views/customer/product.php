<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Product detail">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
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
      <div class="container product-page" data-product-id="<?= (int)($product['id'] ?? 0) ?>">

        <!-- TODO: Replace product fields with real product data later -->
        <div class="product-summary">
          <div class="product-media">
            <div class="product-media__main">
              <?php $mainImage = ($images[0] ?? $product['image'] ?? '../assets/images/laptop.png'); ?>
              <img src="<?= $mainImage ?>" alt="<?= $product['name'] ?? 'Product' ?>" data-product-main>
            </div>
            <div class="product-media__thumbs" data-product-thumbs>
              <?php if (!empty($images) && is_array($images)): ?>
                <?php foreach ($images as $img): ?>
                  <button type="button" class="product-media__thumb" data-product-thumb data-src="<?= $img ?>" aria-label="View image"><img src="<?= $img ?>" alt="<?= $product['name'] ?? 'Product' ?>"></button>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="product-info">
            <h1 class="product-info__title" data-product-title><?= $product['name'] ?? 'Product' ?></h1>
            <?php if (!empty($product['has_discount'])): ?>
              <p class="product-info__price" data-product-price><span class="text-muted text-decoration-line-through">$<?= number_format((float)($product['price'] ?? 0), 0, '.', ',') ?></span> <span class="text-primary fw-semibold">$<?= number_format((float)($product['effective_price'] ?? $product['price'] ?? 0), 0, '.', ',') ?></span></p>
            <?php else: ?>
              <p class="product-info__price" data-product-price>$<?= number_format((float)($product['effective_price'] ?? $product['price'] ?? 0), 0, '.', ',') ?></p>
            <?php endif; ?>
            <?php
              $avgRating = (float)($product['avg_rating'] ?? 0);
              $reviewCount = (int)($product['review_count'] ?? 0);
              $fullStars = (int)floor($avgRating);
              $frac = $avgRating - $fullStars;
              $halfStar = ($frac >= 0.25 && $frac < 0.75) ? 1 : 0;
              if ($frac >= 0.75) { $fullStars++; }
              $emptyStars = max(0, 5 - $fullStars - $halfStar);
            ?>
            <div class="product-info__meta" data-product-rating aria-label="Rating <?= number_format($avgRating, 1) ?> out of 5">
              <?php for ($i = 0; $i < $fullStars; $i++): ?>
                <i class="bi bi-star-fill text-warning"></i>
              <?php endfor; ?>
              <?php if ($halfStar): ?>
                <i class="bi bi-star-half text-warning"></i>
              <?php endif; ?>
              <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                <i class="bi bi-star text-warning"></i>
              <?php endfor; ?>
              <span><?= number_format($avgRating, 1) ?></span>
              <span>· <?= $reviewCount ?> reviews</span>
              <a href="#tab-reviews" id="write-review-link" class="ms-2 d-none">Write a review</a>
            </div>
            <p class="text-muted" data-product-description><?= e($product['description'] ?? '') ?></p>

            <?php $stockOut = ((int)($product['quantity'] ?? 0)) <= 0; ?>
            <div class="d-flex align-items-center gap-3">
              <div class="product-qty" data-qty data-qty-wrapper>
                <button type="button" data-action="dec" aria-label="Decrease quantity">-</button>
                <input type="text" value="1" inputmode="numeric" aria-label="Quantity" data-qty-input>
                <button type="button" data-action="inc" aria-label="Increase quantity">+</button>
              </div>
              <div class="product-actions">
                <button class="btn btn-primary rounded-pill" type="button" data-add-to-cart data-product-id="<?= (int)($product['id'] ?? 0) ?>" data-product-name="<?= $product['name'] ?? '' ?>" data-product-price="<?= $product['effective_price'] ?? $product['price'] ?? 0 ?>" data-product-image="<?= $product['image'] ?? '../assets/images/laptop.png' ?>" <?= $stockOut ? 'disabled' : '' ?>><?= $stockOut ? 'Out of Stock' : 'Add to Cart' ?></button>
                <a class="btn btn-outline-primary rounded-pill<?= $stockOut ? ' disabled' : '' ?>" href="/checkout" data-buy-now data-product-id="<?= (int)($product['id'] ?? 0) ?>" <?= $stockOut ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Buy Now</a>
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
            <p><?= nl2br(e($product['description'] ?? '')) ?></p>
          </div>
          <div id="tab-reviews" class="tab-panel" data-tab-panel>
            <div data-reviews-root>
              <div class="mb-2">
                <strong>Average rating</strong>
                <span class="ms-2"><strong data-product-rating-value><?= number_format($avgRating, 1) ?></strong> / 5</span>
                <span class="ms-2 text-muted">from <span data-product-reviews-count><?= $reviewCount ?></span> reviews</span>
              </div>

              <div data-reviews-list class="mb-3">
                <!-- Reviews will be loaded here via JS -->
                <p class="text-muted">No reviews yet.</p>
              </div>

              <div data-review-form-wrapper>
                <!-- Review form will be injected/controlled via JS -->
                <div class="mb-2" data-review-form-hidden style="display:none;">
                  <h5>Write a review</h5>
                  <form id="review-form">
                    <div class="mb-2">
                      <label class="form-label">Rating</label>
                      <div id="review-stars" aria-label="Rating">
                        <button type="button" data-star="1">☆</button>
                        <button type="button" data-star="2">☆</button>
                        <button type="button" data-star="3">☆</button>
                        <button type="button" data-star="4">☆</button>
                        <button type="button" data-star="5">☆</button>
                      </div>
                      <input type="hidden" name="rating" id="review_rating" value="5">
                    </div>
                    <div class="mb-2">
                      <label class="form-label" for="review_comment">Comment</label>
                      <textarea class="form-control" id="review_comment" name="comment" rows="3" placeholder="Share your experience..."></textarea>
                    </div>
                    <input type="hidden" name="product_id" value="<?= (int)($product['id'] ?? 0) ?>">
                    <input type="hidden" name="order_id" id="review_order_id" value="">
                    <button type="submit" class="btn btn-primary rounded-pill">Submit Review</button>
                    <div class="mt-2 text-success d-none" data-review-success>Thanks — your review has been submitted.</div>
                    <div class="mt-2 text-danger d-none" data-review-error></div>
                  </form>
                </div>

                <!-- If user not eligible we'll show a message here -->
                <div data-review-eligibility-message class="text-muted"></div>
              </div>
            </div>
          </div>
        </div>

        <section class="related-products">
          <div class="section-header">
            <h2 class="section-title">Related Products</h2>
            <a href="/shop" class="btn btn-outline-primary btn-sm rounded-pill px-3">Show All</a>
          </div>
          <!-- TODO: fill related items dynamically -->
          <div class="shop-grid" data-related-grid>
            <?php if (!empty($related) && is_array($related)): ?>
              <?php foreach (array_slice($related, 0, 4) as $r): ?>
                <?php if ((int)($r['id'] ?? 0) === (int)($product['id'] ?? 0)) continue; ?>
                <?php $relOut = ((int)($r['quantity'] ?? 0)) <= 0; ?>
                <article class="product-card">
                  <div class="product-card__image"><img src="<?= $r['image'] ?? '../assets/images/laptop.png' ?>" alt="<?= $r['name'] ?? '' ?>"></div>
                  <h3 class="product-card__title"><?= $r['name'] ?? '' ?></h3>
                  <?php if (!empty($r['has_discount'])): ?>
                    <p class="product-card__price"><span class="text-muted text-decoration-line-through">$<?= number_format((float)($r['price'] ?? 0), 0, '.', ',') ?></span> <span class="text-primary fw-semibold">$<?= number_format((float)($r['effective_price'] ?? $r['price'] ?? 0), 0, '.', ',') ?></span></p>
                  <?php else: ?>
                    <p class="product-card__price">$<?= number_format((float)($r['effective_price'] ?? $r['price'] ?? 0), 0, '.', ',') ?></p>
                  <?php endif; ?>
                  <?php
                    $relAvg = (float)($r['avg_rating'] ?? 0);
                    $relCount = (int)($r['review_count'] ?? 0);
                    $relFull = (int)floor($relAvg);
                    $relFrac = $relAvg - $relFull;
                    $relHalf = ($relFrac >= 0.25 && $relFrac < 0.75) ? 1 : 0;
                    if ($relFrac >= 0.75) { $relFull++; }
                    $relEmpty = max(0, 5 - $relFull - $relHalf);
                  ?>
                  <div class="product-card__meta" aria-label="Rating <?= number_format($relAvg, 1) ?> out of 5">
                    <?php for ($i = 0; $i < $relFull; $i++): ?>
                      <i class="bi bi-star-fill text-warning"></i>
                    <?php endfor; ?>
                    <?php if ($relHalf): ?>
                      <i class="bi bi-star-half text-warning"></i>
                    <?php endif; ?>
                    <?php for ($i = 0; $i < $relEmpty; $i++): ?>
                      <i class="bi bi-star text-warning"></i>
                    <?php endfor; ?>
                    <span><?= number_format($relAvg, 1) ?></span>
                    <span>· <?= $relCount ?> reviews</span>
                  </div>
                  <div class="product-card__actions">
                    <a class="btn btn-primary rounded-pill" href="/product/<?= rawurlencode($r['slug'] ?? '') ?>">View Details</a>
                    <button class="btn btn-outline-primary rounded-pill" type="button" data-add-to-cart data-product-id="<?= (int)$r['id'] ?>" data-product-name="<?= $r['name'] ?? '' ?>" data-product-price="<?= $r['effective_price'] ?? $r['price'] ?? 0 ?>" data-product-image="<?= $r['image'] ?? '../assets/images/laptop.png' ?>" <?= $relOut ? 'disabled' : '' ?>><?= $relOut ? 'Out of Stock' : 'Buy' ?></button>
                  </div>
                </article>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>
      </div>
    </section>
  </main>

  <!-- Page-specific scripts -->
  <script>
    window.CURRENT_USER_ID = <?= json_encode($_SESSION['auth']['id'] ?? null) ?>;
  </script>
  <script src="../assets/js/product.js"></script>

  <!-- ================= FOOTER ================= -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>
