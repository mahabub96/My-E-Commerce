<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Manage products">
  <title>Products</title>

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
      <span class="navbar-brand mb-0">Products</span>
      <a class="btn btn-primary btn-sm" href="product-create.php">Add Product</a>
    </header>

    <main class="admin-main container">
      <div class="admin-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">All Products</h5>
          <input class="form-control" type="search" placeholder="Search" style="max-width: 240px;">
        </div>
        <div class="table-responsive">
          <!-- TODO: Replace with dynamic products from DB -->
          <table class="table admin-table align-middle mb-0">
            <thead>
              <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Asus Zenbook UX-430 US</td>
                <td>Laptops</td>
                <td>$1,299</td>
                <td>88</td>
                <td><span class="badge bg-success">Active</span></td>
                <td class="admin-actions">
                  <a class="btn btn-outline-primary btn-sm" href="product-create.php">Edit</a>
                  <button class="btn btn-outline-secondary btn-sm" type="button">Archive</button>
                </td>
              </tr>
              <tr>
                <td>Audio Technica ATH M20 BT</td>
                <td>Audio</td>
                <td>$199</td>
                <td>320</td>
                <td><span class="badge bg-success">Active</span></td>
                <td class="admin-actions">
                  <a class="btn btn-outline-primary btn-sm" href="product-create.php">Edit</a>
                  <button class="btn btn-outline-secondary btn-sm" type="button">Archive</button>
                </td>
              </tr>
              <tr>
                <td>Modena Juice Blender</td>
                <td>Home Appliances</td>
                <td>$129</td>
                <td>56</td>
                <td><span class="badge bg-warning text-dark">Draft</span></td>
                <td class="admin-actions">
                  <a class="btn btn-outline-primary btn-sm" href="product-create.php">Edit</a>
                  <button class="btn btn-outline-secondary btn-sm" type="button">Publish</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>
