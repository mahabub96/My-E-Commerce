<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Order success">
  <title>Order Success</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="../assets/css/style-refactored.css">
  <link rel="stylesheet" href="../assets/css/cart.css">
</head>
<body>
  <!-- ================= NAVBAR ================= -->
  <?php include __DIR__ . '/../partials/header.php'; ?>

  <main>
    <section class="py-5">
      <div class="container text-center">
        <div class="promo-box">
          <div class="row justify-content-center">
            <div class="col-lg-8">
              <div class="d-flex justify-content-center mb-3">
                <div class="feature-card__icon" style="width:64px;height:64px;">
                  <i class="bi bi-check-lg"></i>
                </div>
              </div>
              <h1 class="section-title--centered">Order Placed Successfully</h1>
              <p class="section-subtitle">Thank you for your purchase. We sent the receipt and delivery details to your email.</p>

              <!-- Dynamic order details -->
              <div class="row g-3 text-start mt-4">
                <div class="col-md-6">
                  <div class="feature-card">
                    <h3 class="feature-card__title">Order Summary</h3>
                    <p class="feature-card__text mb-2">Order ID: <?= htmlspecialchars($order['order_number'] ?? ('#' . ($order['id'] ?? ''))) ?></p>
                    <p class="feature-card__text mb-2">Payment: <?= htmlspecialchars(ucfirst($order['payment_method'] ?? '')) ?> (<?= htmlspecialchars(ucfirst($order['payment_status'] ?? '')) ?>)</p>
                    <p class="feature-card__text mb-0">Order Status: <?= htmlspecialchars(ucfirst($order['order_status'] ?? $order['status'] ?? 'pending')) ?></p>
                    <?php if (!empty($order['transaction_id'])): ?>
                      <p class="feature-card__text mb-0">Transaction ID: <?= htmlspecialchars($order['transaction_id']) ?></p>
                    <?php endif; ?>
                    <p class="feature-card__text mt-2 mb-0">Placed: <?= htmlspecialchars(date('M j, Y g:ia', strtotime($order['created_at'] ?? 'now'))) ?></p>
                    <hr>
                    <h5 class="mt-2">Items</h5>
                    <?php if (!empty($items) && is_array($items)): ?>
                      <?php foreach ($items as $it): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <div>
                            <div class="fw-semibold"><?= htmlspecialchars($it['product_name'] ?? '') ?></div>
                            <small class="text-muted">Qty: <?= (int)($it['quantity'] ?? 1) ?></small>
                          </div>
                          <div>$<?= number_format((float)($it['total'] ?? (($it['price'] ?? 0) * ($it['quantity'] ?? 1))), 2, '.', ',') ?></div>
                        </div>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <p class="text-muted">No items found.</p>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between">
                      <strong>Total</strong>
                      <strong>$<?= number_format((float)($order['total_amount'] ?? 0), 2, '.', ',') ?></strong>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="feature-card">
                    <h3 class="feature-card__title">Shipping</h3>
                    <p class="feature-card__text mb-2"><?= htmlspecialchars($order['full_name'] ?? ($order['shipping_name'] ?? '')) ?></p>
                    <p class="feature-card__text mb-0"><?= htmlspecialchars(implode(', ', array_filter([$order['shipping_address'] ?? '', $order['shipping_city'] ?? '', $order['shipping_country'] ?? '', $order['shipping_postal_code'] ?? '']))) ?></p>
                    <p class="feature-card__text mt-2 mb-0">Phone: <?= htmlspecialchars($order['phone'] ?? '') ?></p>
                  </div>
                </div>
              </div>

              <div class="mt-4 d-flex justify-content-center gap-2">
                <a class="btn btn-primary rounded-pill" href="/shop">Continue Shopping</a>
                <a class="btn btn-outline-primary rounded-pill" href="/profile">View Orders</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ================= FOOTER ================= -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>
