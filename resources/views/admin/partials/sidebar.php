<aside class="admin-sidebar" data-admin-sidebar>
  <h5 class="px-3 mb-3">Admin</h5>
  <?php
    // Determine current route path
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
    $current = '/' . trim($path, '/');
    if ($current === '/') $current = '/admin';
  ?>
  <?php
    $isDashboard = ($current === '/admin' || $current === '/admin/dashboard');
    $isCategories = (strpos($current, '/admin/categories') === 0);
    $isProducts = (strpos($current, '/admin/products') === 0);
    $isOrders = (strpos($current, '/admin/orders') === 0);
  ?>
  <nav class="d-flex flex-column gap-1">
    <a class="<?php echo $isDashboard ? 'active' : ''; ?>" href="/admin/dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a class="<?php echo $isCategories && $current !== '/admin/categories/create' ? 'active' : ''; ?>" href="/admin/categories"><i class="bi bi-folder2"></i> Categories</a>
    <a class="<?php echo $current === '/admin/categories/create' ? 'active' : ''; ?>" href="/admin/categories/create"><i class="bi bi-plus-circle"></i> New Category</a>
    <a class="<?php echo $isProducts && $current !== '/admin/products/create' ? 'active' : ''; ?>" href="/admin/products"><i class="bi bi-box-seam"></i> Products</a>
    <a class="<?php echo $current === '/admin/products/create' ? 'active' : ''; ?>" href="/admin/products/create"><i class="bi bi-plus-circle"></i> New Product</a>
    <a class="<?php echo $isOrders ? 'active' : ''; ?>" href="/admin/orders"><i class="bi bi-receipt"></i> Orders</a>
  </nav>
  <!-- Logout button at bottom (responsive, uses existing utility classes) -->
  <div class="mt-auto px-3 pb-3 w-100">
    <form method="post" action="/admin/logout" class="m-0">
      <input type="hidden" name="_token" value="<?= csrf_token() ?>">
      <button type="submit" class="btn btn-outline-danger w-100 d-flex align-items-center">
        <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>
        Logout
      </button>
    </form>
  </div>
</aside>
