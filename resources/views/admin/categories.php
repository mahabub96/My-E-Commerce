<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Manage categories">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>Categories</title>

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
      <span class="navbar-brand mb-0">Categories</span>
      <a class="btn btn-primary btn-sm" href="/admin/categories/create">Add Category</a>
    </header>

    <main class="admin-main container">
      <?php \App\Core\Views::partial('partials.flash'); ?>
      <div class="admin-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">All Categories</h5>
          <input class="form-control" type="search" placeholder="Search" style="max-width: 240px;">
        </div>
        <div class="table-responsive">
          <!-- Categories loaded from DB -->
          <table class="table admin-table align-middle mb-0">
            <thead>
              <tr>
                <th>Name</th>
                <th>Products</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                  <?php $status = $cat['status'] ?? 'active'; ?>
                  <tr>
                    <td><?= $cat['name'] ?? '' ?></td>
                    <td><?= (int)($cat['product_count'] ?? 0) ?></td>
                    <td>
                      <span class="badge <?= $status === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= ucfirst($status) ?>
                      </span>
                    </td>
                    <td class="admin-actions">
                      <a class="btn btn-outline-primary btn-sm" href="/admin/categories/<?= (int)$cat['id'] ?>/edit">Edit</a>
                      <form method="post" action="/admin/categories/<?= (int)$cat['id'] ?>/delete" style="display:inline;">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <button class="btn btn-outline-secondary btn-sm" type="submit">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" class="text-center text-muted">No categories found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>
