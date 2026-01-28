<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Create category">
  <title>Create Category</title>

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
      <span class="navbar-brand mb-0">Create Category</span>
    </header>

    <main class="admin-main container">
      <?php \App\Core\Views::partial('partials.flash'); ?>
      <div class="admin-card">
        <?php $isEdit = !empty($category); ?>
        <h5 class="mb-3">Category Details</h5>
        <form class="row g-3" aria-label="Create category form" method="post" action="<?= $isEdit ? '/admin/categories/' . (int)$category['id'] : '/admin/categories' ?>" enctype="multipart/form-data">
          <input type="hidden" name="_token" value="<?= csrf_token() ?>">
          <div class="col-md-6">
            <label class="form-label" for="catName">Name</label>
            <input class="form-control" id="catName" name="name" type="text" placeholder="Laptops" required value="<?= $category['name'] ?? '' ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="catSlug">Slug</label>
            <input class="form-control" id="catSlug" name="slug" type="text" placeholder="laptops" required value="<?= $category['slug'] ?? '' ?>">
          </div>
          <div class="col-12">
            <label class="form-label" for="catDesc">Description</label>
            <textarea class="form-control" id="catDesc" name="description" rows="3" placeholder="Category description"><?= $category['description'] ?? '' ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="catImage">Category Icon</label>
            <input class="form-control" id="catImage" name="image" type="file" accept="image/*" data-current-icon="<?= $category['icon_path'] ?? $category['image'] ?? '' ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="catStatus">Status</label>
            <select id="catStatus" name="status" class="form-select">
              <option value="active" <?= (($category['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= (($category['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
          <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="/admin/categories">Cancel</a>
            <button class="btn btn-primary" type="submit">Save Category</button>
          </div>
        </form>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>
