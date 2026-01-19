<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Create product">
  <title>Create Product</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/volt.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-app d-flex">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="flex-grow-1">
    <header class="admin-topbar navbar navbar-light px-3">
      <button class="btn btn-outline-secondary d-lg-none" type="button" data-admin-toggle><i class="bi bi-list"></i></button>
      <span class="navbar-brand mb-0">Create Product</span>
    </header>

    <main class="admin-main container">
      <div class="admin-card">
        <h5 class="mb-3">Product Details</h5>
        <form class="row g-3" aria-label="Create product form">
          <div class="col-md-6">
            <label class="form-label" for="prodName">Name</label>
            <input class="form-control" id="prodName" type="text" placeholder="Asus Zenbook UX-430 US" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="prodSku">SKU</label>
            <input class="form-control" id="prodSku" type="text" placeholder="SKU-0001" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="prodCategory">Category</label>
            <select id="prodCategory" class="form-select">
              <option selected>Laptops</option>
              <option>Audio</option>
              <option>Home Appliances</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="prodPrice">Price</label>
            <input class="form-control" id="prodPrice" type="number" value="1299" min="0">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="prodStock">Stock</label>
            <input class="form-control" id="prodStock" type="number" value="100" min="0">
          </div>
          <div class="col-12">
            <label class="form-label" for="prodDesc">Description</label>
            <textarea class="form-control" id="prodDesc" rows="3" placeholder="Product description"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label" for="prodImages">Images</label>
            <input class="form-control" id="prodImages" type="file" multiple>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="prodStatus">Status</label>
            <select id="prodStatus" class="form-select">
              <option selected>Active</option>
              <option>Draft</option>
            </select>
          </div>
          <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="products.php">Cancel</a>
            <a class="btn btn-primary" href="products.php">Save Product</a>
          </div>
        </form>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>
