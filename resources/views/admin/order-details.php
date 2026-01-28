<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Order details">
  <title>Order Details</title>

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
      <span class="navbar-brand mb-0">Order <?= $order['order_number'] ?? ('#' . ($order['id'] ?? '')) ?></span>
      <div class="ms-auto d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.print()">Print</button>
      </div>
    </header>

    <main class="admin-main container">
      <?php \App\Core\Views::partial('partials.flash'); ?>
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="mb-0">Items</h5>
              <?php $status = $order['order_status'] ?? 'pending'; ?>
              <?php
                $badge = 'bg-secondary';
                if ($status === 'processing') $badge = 'bg-success';
                elseif ($status === 'pending') $badge = 'bg-warning text-dark';
                elseif ($status === 'completed') $badge = 'bg-primary';
                elseif ($status === 'cancelled') $badge = 'bg-danger';
              ?>
              <span class="badge <?= $badge ?>"><?= ucfirst($status) ?></span>
            </div>
            <div class="table-responsive">
              <!-- Order items -->
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th scope="col">Product</th>
                    <th scope="col">SKU</th>
                    <th scope="col">Qty</th>
                    <th scope="col">Price</th>
                    <th scope="col" class="text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $subtotal = 0.0; ?>
                  <?php if (!empty($items)): ?>
                    <?php foreach ($items as $it): ?>
                      <?php $lineTotal = (float)($it['total'] ?? ((float)($it['price'] ?? 0) * (int)($it['quantity'] ?? 0))); ?>
                      <?php $subtotal += $lineTotal; ?>
                      <tr>
                        <td><?= $it['product_name'] ?? '' ?></td>
                        <td><?= $it['product_sku'] ?? ($it['product_id'] ?? '-') ?></td>
                        <td><?= (int)($it['quantity'] ?? 0) ?></td>
                        <td>$<?= number_format((float)($it['price'] ?? 0), 2) ?></td>
                        <td class="text-end">$<?= number_format($lineTotal, 2) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted">No items found.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="4" class="text-end">Subtotal</th>
                    <th class="text-end">$<?= number_format($subtotal, 2) ?></th>
                  </tr>
                  <tr>
                    <th colspan="4" class="text-end">Shipping</th>
                    <th class="text-end">$<?= number_format(0, 2) ?></th>
                  </tr>
                  <tr>
                    <th colspan="4" class="text-end">Total</th>
                    <th class="text-end">$<?= number_format((float)($order['total_amount'] ?? $subtotal), 2) ?></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="admin-card mb-3">
            <h6 class="mb-2">Customer</h6>
            <p class="mb-1 fw-semibold"><?= $order['customer_name'] ?? 'Customer' ?></p>
            <p class="mb-1"><?= $order['customer_email'] ?? '' ?></p>
            <p class="mb-0"><?= $order['shipping_address'] ?? '' ?></p>
          </div>
          <div class="admin-card mb-3">
            <h6 class="mb-2">Shipping Address</h6>
            <p class="mb-0"><?= nl2br($order['shipping_address'] ?? '') ?></p>
          </div>
          
          <!-- Payment Information (READ-ONLY) -->
          <div class="admin-card mb-3">
            <h6 class="mb-2">Payment Information</h6>
            <div class="mb-2">
              <small class="text-muted d-block">Payment Method</small>
              <strong><?= strtoupper($order['payment_method'] ?? 'N/A') ?></strong>
            </div>
            <div class="mb-0">
              <small class="text-muted d-block">Payment Status</small>
              <?php 
                $paymentStatus = $order['payment_status'] ?? 'unpaid';
                $paymentBadge = match($paymentStatus) {
                  'paid' => 'bg-success',
                  'unpaid' => 'bg-warning text-dark',
                  'failed' => 'bg-danger',
                  'refunded' => 'bg-info',
                  default => 'bg-secondary'
                };
              ?>
              <span class="badge <?= $paymentBadge ?>"><?= ucfirst($paymentStatus) ?></span>
            </div>
            <?php if ($order['payment_method'] !== 'cod'): ?>
              <small class="text-muted d-block mt-2">
                <i class="bi bi-info-circle"></i> Payment status is managed by the payment gateway
              </small>
            <?php else: ?>
              <small class="text-muted d-block mt-2">
                <i class="bi bi-info-circle"></i> COD orders are marked as paid when completed
              </small>
            <?php endif; ?>
          </div>
          
          <div class="admin-card">
            <h6 class="mb-3">Order Status</h6>
            <form class="d-flex flex-column gap-2" method="post" action="/admin/orders/<?= (int)($order['id'] ?? 0) ?>/status">
              <input type="hidden" name="_token" value="<?= csrf_token() ?>">
              <select class="form-select" name="order_status">
                <?php $status = $order['order_status'] ?? 'pending'; ?>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
              </select>
              <textarea class="form-control" name="notes" rows="2" placeholder="Internal note"><?= $order['notes'] ?? '' ?></textarea>
              <div class="d-flex gap-2 justify-content-end">
                <a class="btn btn-outline-secondary" href="/admin/orders">Cancel</a>
                <button class="btn btn-primary" type="submit">Save</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>