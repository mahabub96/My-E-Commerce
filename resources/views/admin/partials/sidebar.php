<aside class="admin-sidebar" data-admin-sidebar>
  <h5 class="px-3 mb-3">Admin</h5>
  <?php
    // Determine current page filename robustly (fallback to PHP_SELF if needed)
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $current = basename($path ? $path : $_SERVER['PHP_SELF']);
  ?>
  <nav class="d-flex flex-column gap-1">
    <a class="<?php echo $current === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a class="<?php echo $current === 'categories.php' ? 'active' : ''; ?>" href="categories.php"><i class="bi bi-folder2"></i> Categories</a>
    <a class="<?php echo $current === 'category-create.php' ? 'active' : ''; ?>" href="category-create.php"><i class="bi bi-plus-circle"></i> New Category</a>
    <a class="<?php echo $current === 'products.php' ? 'active' : ''; ?>" href="products.php"><i class="bi bi-box-seam"></i> Products</a>
    <a class="<?php echo $current === 'product-create.php' ? 'active' : ''; ?>" href="product-create.php"><i class="bi bi-plus-circle"></i> New Product</a>
    <a class="<?php echo $current === 'orders.php' ? 'active' : ''; ?>" href="orders.php"><i class="bi bi-receipt"></i> Orders</a>
  </nav>
</aside>
