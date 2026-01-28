<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Orders list">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>Orders</title>

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
      <span class="navbar-brand mb-0">Orders</span>
    </header>

    <main class="admin-main container">
      <?php \App\Core\Views::partial('partials.flash'); ?>
      <div class="admin-card">
        <form class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2" method="get" action="/admin/orders">
          <h5 class="mb-0">Recent Orders</h5>
          <div class="d-flex gap-2">
            <input class="form-control" type="search" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Search orders">
            <select class="form-select" name="status" aria-label="Filter status">
              <?php $status = $_GET['status'] ?? ''; ?>
              <option value="" <?= $status === '' ? 'selected' : '' ?>>Status: All</option>
              <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
              <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
              <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <button class="btn btn-outline-secondary" type="submit">Filter</button>
          </div>
        </form>
        <div class="table-responsive">
          <!-- Orders loaded from database -->
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">Order #</th>
                <th scope="col">Customer</th>
                <th scope="col">Date</th>
                <th scope="col">Total</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $o): ?>
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
                    <td><?= date('Y-m-d', strtotime($o['created_at'] ?? 'now')) ?></td>
                    <td>$<?= number_format((float)($o['total_amount'] ?? 0), 2) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst($status) ?></span></td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-primary" href="/admin/orders?id=<?= (int)$o['id'] ?>">View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center text-muted">No orders found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>

  <script src="/assets/js/admin-orders.js"></script>
</body>
</html>
