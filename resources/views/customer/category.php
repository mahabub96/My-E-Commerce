<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Browse categories and featured picks">
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
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Categories</li>
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
              <h2 class="section-title m-0">Featured Picks</h2>
              <div class="d-flex align-items-center gap-2">
                <span class="text-muted">6 results</span>
              </div>
            </div>

            <!-- TODO: replace with category product list from database -->
            <div class="shop-grid">
              <article class="product-card">
                <div class="product-badge">
                  <i class="bi bi-lightning-charge"></i>
                  Hot
                </div>
                <div class="product-card__image">
                  <img src="../assets/images/laptop.png" alt="Laptop">
                </div>
                <h3 class="product-card__title">Asus Zenbook UX-430 US</h3>
                <p class="product-card__price">$1,299</p>
                <div class="product-card__meta" aria-label="Rating 4.8 out of 5">
                  <i class="bi bi-star-fill text-warning"></i>
                  <span>4.8</span>
                  <span> 21K reviews</span>
                </div>
                <div class="product-card__actions">
                  <a class="btn btn-primary rounded-pill" href="product.php?id=asus-ux430">View Details</a>
                  <button class="btn btn-outline-primary rounded-pill" type="button" data-add-to-cart data-product-id="asus-ux430" data-product-name="Asus Zenbook UX-430 US" data-product-price="1299" data-product-image="../assets/images/laptop.png">Buy</button>
                </div>
              </article>

              <article class="product-card">
                <div class="product-card__image">
                  <img src="../assets/images/headphone.png" alt="Headphone">
                </div>
                <h3 class="product-card__title">Audio Technica ATH M20 BT</h3>
                <p class="product-card__price">$199</p>
                <div class="product-card__meta" aria-label="Rating 5.0 out of 5">
                  <i class="bi bi-star-fill text-warning"></i>
                  <span>5.0</span>
                  <span> 300K reviews</span>
                </div>
                <div class="product-card__actions">
                  <a class="btn btn-primary rounded-pill" href="product.php?id=audio-ath-m20">View Details</a>
                  <button class="btn btn-outline-primary rounded-pill" type="button" data-add-to-cart data-product-id="audio-ath-m20" data-product-name="Audio Technica ATH M20 BT" data-product-price="199" data-product-image="../assets/images/headphone.png">Buy</button>
                </div>
              </article>

              <article class="product-card">
                <div class="product-card__image">
                  <img src="../assets/images/cream.png" alt="SK II Cream">
                </div>
                <h3 class="product-card__title">SK II - Anti Aging Cream</h3>
                <p class="product-card__price">$79</p>
                <div class="product-card__meta" aria-label="Rating 4.9 out of 5">
                  <i class="bi bi-star-fill text-warning"></i>
                  <span>4.9</span>
                  <span> 89K reviews</span>
                </div>
                <div class="product-card__actions">
                  <a class="btn btn-primary rounded-pill" href="product.php?id=sk-ii-cream">View Details</a>
                  <button class="btn btn-outline-primary rounded-pill" type="button" data-add-to-cart data-product-id="sk-ii-cream" data-product-name="SK II - Anti Aging Cream" data-product-price="79" data-product-image="../assets/images/cream.png">Buy</button>
                </div>
              </article>

              <article class="product-card">
                <div class="product-card__image">
                  <img src="../assets/images/blender.png" alt="Blender">
                </div>
                <h3 class="product-card__title">Modena Juice Blender</h3>
                <p class="product-card__price">$129</p>
                <div class="product-card__meta" aria-label="Rating 4.8 out of 5">
                  <i class="bi bi-star-fill text-warning"></i>
                  <span>4.8</span>
                  <span> 871 reviews</span>
                </div>
                <div class="product-card__actions">
                  <a class="btn btn-primary rounded-pill" href="product.php?id=modena-blender">View Details</a>
                  <button class="btn btn-outline-primary rounded-pill" type="button" data-add-to-cart data-product-id="modena-blender" data-product-name="Modena Juice Blender" data-product-price="129" data-product-image="../assets/images/blender.png">Buy</button>
                </div>
              </article>

              <article class="product-card">
                <div class="product-card__image">
                  <img src="../assets/images/acer.png" alt="Acer Swift">
                </div>
                <h3 class="product-card__title">Acer Swift Air SF-313</h3>
                <p class="product-card__price">$999</p>
                <div class="product-card__meta" aria-label="Rating 4.7 out of 5">
                  <i class="bi bi-star-fill text-warning"></i>
                  <span>4.7</span>
                  <span> 12K reviews</span>
                </div>
                <div class="product-card__actions">
                  <a class="btn btn-primary rounded-pill" href="product.php?id=acer-swift">View Details</a>
                  <button class="btn btn-outline-primary rounded-pill" type="button" data-add-to-cart data-product-id="acer-swift" data-product-name="Acer Swift Air SF-313" data-product-price="999" data-product-image="../assets/images/acer.png">Buy</button>
                </div>
              </article>

              <article class="product-card">
                <div class="product-card__image">
                  <img src="../assets/images/iphone.png" alt="iPhone">
                </div>
                <h3 class="product-card__title">iPhone 11 Pro Max</h3>
                <p class="product-card__price">$1,099</p>
                <div class="product-card__meta" aria-label="Rating 4.9 out of 5">
                  <i class="bi bi-star-fill text-warning"></i>
                  <span>4.9</span>
                  <span> 1.2K reviews</span>
                </div>
                <div class="product-card__actions">
                  <a class="btn btn-primary rounded-pill" href="product.php?id=iphone-11-pro">View Details</a>
                  <button class="btn btn-outline-primary rounded-pill" type="button" data-add-to-cart data-product-id="iphone-11-pro" data-product-name="iPhone 11 Pro Max" data-product-price="1099" data-product-image="../assets/images/iphone.png">Buy</button>
                </div>
              </article>
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
