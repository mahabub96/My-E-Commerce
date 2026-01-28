<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Admin dashboard">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>Admin Dashboard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/volt.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-app d-flex">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="flex-grow-1">
    <header class="admin-topbar navbar navbar-light px-3">
      <button class="btn btn-outline-secondary d-lg-none" type="button" data-admin-toggle>
        <i class="bi bi-list"></i>
      </button>
      <span class="navbar-brand mb-0">Dashboard</span>
    </header>

    <main class="admin-main container">
      <?php \App\Core\Views::partial('partials.flash'); ?>
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="admin-card admin-stat">
            <div>
              <p class="text-muted mb-1">Revenue</p>
              <h3 class="mb-0">$<?= number_format((float)($stats['total_revenue'] ?? 0), 2) ?></h3>
            </div>
            <span class="badge bg-success">+12%</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="admin-card admin-stat">
            <div>
              <p class="text-muted mb-1">Orders</p>
              <h3 class="mb-0"><?= number_format((int)($stats['total_orders'] ?? 0)) ?></h3>
            </div>
            <span class="badge bg-success">+5%</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="admin-card admin-stat">
            <div>
              <p class="text-muted mb-1">Customers</p>
              <h3 class="mb-0"><?= number_format((int)($stats['total_customers'] ?? 0)) ?></h3>
            </div>
            <span class="badge bg-secondary">Stable</span>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-7">
          <div class="admin-card">
            <h5 class="mb-3">Recent Orders</h5>
            <div class="table-responsive">
              <!-- Recent orders from DB -->
              <table class="table admin-table align-middle mb-0">
                <thead>
                  <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $o): ?>
                      <?php $status = $o['order_status'] ?? 'pending'; ?>
                      <?php
                        $badge = 'bg-secondary';
                        if ($status === 'processing') $badge = 'bg-success';
                        elseif ($status === 'pending') $badge = 'bg-warning text-dark';
                        elseif ($status === 'completed') $badge = 'bg-primary';
                        elseif ($status === 'cancelled') $badge = 'bg-danger';
                      ?>
                      <tr>
                        <td><?= $o['order_number'] ?? ('#' . $o['id']) ?></td>
                        <td><?= $o['customer_name'] ?? 'Customer' ?></td>
                        <td><?= date('M d', strtotime($o['created_at'] ?? 'now')) ?></td>
                        <td>$<?= number_format((float)($o['total_amount'] ?? 0), 2) ?></td>
                        <td><span class="badge <?= $badge ?>"><?= ucfirst($status) ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted">No recent orders.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="admin-card">
            <h5 class="mb-3">Top Categories</h5>
            <ul class="list-group list-group-flush">
              <?php if (!empty($top_categories)): ?>
                <?php foreach ($top_categories as $c): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= $c['name'] ?? 'Category' ?>
                    <span class="badge bg-primary"><?= (int)($c['product_count'] ?? 0) ?></span>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  No categories <span class="badge bg-secondary">0</span>
                </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>