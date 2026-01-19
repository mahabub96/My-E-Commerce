<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Create category">
  <title>Create Category</title>

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
      <span class="navbar-brand mb-0">Create Category</span>
    </header>

    <main class="admin-main container">
      <div class="admin-card">
        <h5 class="mb-3">Category Details</h5>
        <form class="row g-3" aria-label="Create category form">
          <div class="col-md-6">
            <label class="form-label" for="catName">Name</label>
            <input class="form-control" id="catName" type="text" placeholder="Laptops" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="catSlug">Slug</label>
            <input class="form-control" id="catSlug" type="text" placeholder="laptops" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="catDesc">Description</label>
            <textarea class="form-control" id="catDesc" rows="3" placeholder="Category description"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="catStatus">Status</label>
            <select id="catStatus" class="form-select">
              <option selected>Active</option>
              <option>Draft</option>
            </select>
          </div>
          <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="categories.php">Cancel</a>
            <a class="btn btn-primary" href="categories.php">Save Category</a>
          </div>
        </form>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>
