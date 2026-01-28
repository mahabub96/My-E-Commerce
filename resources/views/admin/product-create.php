<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Create product">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>Create Product</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/volt.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-app d-flex">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="flex-grow-1">
    <header class="admin-topbar navbar navbar-light px-3">
      <button class="btn btn-outline-secondary d-lg-none" type="button" data-admin-toggle><i class="bi bi-list"></i></button>
      <span class="navbar-brand mb-0">Create Product</span>
    </header>

    <main class="admin-main container">
      <?php \App\Core\Views::partial('partials.flash'); ?>
      <div class="admin-card">
        <?php $isEdit = !empty($product); ?>
        <h5 class="mb-3">Product Details</h5>
        <form id="admin-product-form" class="row g-3" aria-label="Create product form" enctype="multipart/form-data" method="post" action="<?= $isEdit ? '/admin/products/' . (int)$product['id'] : '/admin/products' ?>">
          <input type="hidden" name="_token" value="<?= csrf_token() ?>">
          <div class="col-md-6">
            <label class="form-label" for="prodName">Name</label>
            <input class="form-control" id="prodName" name="name" type="text" placeholder="Asus Zenbook UX-430 US" required value="<?= $product['name'] ?? '' ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="prodSku">SKU</label>
            <input class="form-control" id="prodSku" name="sku" type="text" placeholder="SKU-0001" value="<?= $product['sku'] ?? '' ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="prodCategory">Category</label>
            <select id="prodCategory" name="category_id" class="form-select">
              <option value="">Select category</option>
              <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>" <?= (!empty($product) && (int)$product['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>><?= $cat['name'] ?></option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="prodPrice">Price</label>
            <input class="form-control" id="prodPrice" name="price" type="number" value="<?= $product['price'] ?? 1299 ?>" min="0" step="0.01" required>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="prodStock">Stock</label>
            <input class="form-control" id="prodStock" name="quantity" type="number" value="<?= $product['quantity'] ?? 100 ?>" min="0" required>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="prodDiscount">Discount Price</label>
            <input class="form-control" id="prodDiscount" name="discount_price" type="number" step="0.01" min="0" value="<?= $product['discount_price'] ?? '' ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label" for="prodFeatured">Featured</label>
            <div class="form-check">
              <input class="form-check-input" id="prodFeatured" name="featured" type="checkbox" value="1" <?= !empty($product['featured']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="prodFeatured">Mark as featured</label>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label" for="prodDesc">Description</label>
            <textarea class="form-control" id="prodDesc" name="description" rows="3" placeholder="Product description"><?= $product['description'] ?? '' ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Images</label>
            <div class="d-flex flex-wrap gap-2">
              <input class="form-control file-input" id="prodImages" name="images[]" type="file" accept="image/*">
              <input class="form-control file-input" id="prodImages2" name="images[]" type="file" accept="image/*">
              <input class="form-control file-input" id="prodImages3" name="images[]" type="file" accept="image/*">
              <input class="form-control file-input" id="prodImages4" name="images[]" type="file" accept="image/*">
            </div>
            <small class="text-muted">Use up to four image slots. Click a slot to choose a file; you can replace or remove existing images in edit mode. The first image will be set as primary unless you choose otherwise.</small>
            <small class="text-muted d-block">Server limits: upload_max_filesize = <?= ini_get('upload_max_filesize') ?>, post_max_size = <?= ini_get('post_max_size') ?>.</small>
          </div>
          <?php if (!empty($isEdit)): ?>
            <div class="col-12">
              <label class="form-label">Existing Images</label>
              <div id="existingImages" class="d-flex flex-wrap gap-3" aria-live="polite"></div>
              <small class="text-muted d-block mt-1">Remove individual images or replace them; uploading new images will be appended to the gallery (max 4 images).</small>
            </div>
          <?php endif; ?>
          <div class="col-md-6">
            <label class="form-label" for="prodStatus">Status</label>
            <select id="prodStatus" name="status" class="form-select">
              <?php $status = $product['status'] ?? 'active'; ?>
              <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
          <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="/admin/products">Cancel</a>
            <button class="btn btn-primary" type="submit">Save Product</button>
          </div>
        </form>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>
  <script src="/assets/js/admin-products.js"></script>
