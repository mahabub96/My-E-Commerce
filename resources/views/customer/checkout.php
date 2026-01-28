<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Checkout">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>Checkout</title>

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
      <div class="container">

        <div class="row g-4">
          <div class="col-lg-7">
            <div class="promo-box text-start">
              <h2 class="promo-title">Billing Details</h2>
              <!-- Billing form - submits via AJAX to /checkout/process -->
              <form id="checkout-form" class="row g-3 mt-3" aria-label="Billing form">
                <div class="col-md-6">
                  <label class="form-label" for="full_name">Full Name</label>
                  <input class="form-control" id="full_name" name="full_name" type="text" placeholder="John Doe" required value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <label class="form-label" for="email">Email</label>
                  <input class="form-control" id="email" name="email" type="email" placeholder="john@example.com" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <label class="form-label" for="primary_phone">Primary Phone</label>
                  <input class="form-control" id="primary_phone" name="primary_phone" type="text" required readonly value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <label class="form-label" for="secondary_phone">Secondary Phone (optional)</label>
                  <input class="form-control" id="secondary_phone" name="secondary_phone" type="text" placeholder="Optional" inputmode="numeric" pattern="\d*">
                </div>
                <div class="col-12">
                  <label class="form-label" for="address">Address</label>
                  <input class="form-control" id="address" name="address" type="text" placeholder="123 Main St" required value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="city">City</label>
                  <input class="form-control" id="city" name="city" type="text" placeholder="San Francisco" required value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="country">Country</label>
                  <input class="form-control" id="country" name="country" type="text" placeholder="USA" required value="<?= htmlspecialchars($user['country'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="zip">ZIP</label>
                  <input class="form-control" id="zip" name="zip" type="text" placeholder="94107" required value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <label class="form-label">Payment Method</label>
                  <div class="d-flex flex-column gap-2">
                    <?php $stripeConfigured = !empty(env('STRIPE_SECRET_KEY')) ?>
                    <?php $paypalConfigured = !empty(env('PAYPAL_CLIENT_ID')) && !empty(env('PAYPAL_SECRET')) ?>
                    <label class="d-flex align-items-center gap-2">
                      <input type="radio" name="payment_method" value="card" data-configured="<?= $stripeConfigured ? '1' : '0' ?>" <?php if ($stripeConfigured) echo 'checked'; ?>> Credit Card (Visa / MasterCard)
                      
                    </label>
                    <label class="d-flex align-items-center gap-2">
                      <input type="radio" name="payment_method" value="paypal" data-configured="<?= $paypalConfigured ? '1' : '0' ?>" <?php if ($paypalConfigured && !$stripeConfigured) echo 'checked'; ?>> PayPal
                      
                    </label>
                    <label class="d-flex align-items-center gap-2">
                      <input type="radio" name="payment_method" value="cod" data-configured="1" <?php if (!$stripeConfigured && !$paypalConfigured) echo 'checked'; ?>> Cash on Delivery
                    </label>
                  </div>
                </div>
                <div class="col-12 d-flex justify-content-end">
                  <button type="submit" class="btn btn-primary rounded-pill">Place Order</button>
                </div>
              </form>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="promo-box">
              <h2 class="promo-title">Order Summary</h2>
              <div class="mt-3" data-checkout-summary>
                <?php if (!empty($cart)): ?>
                  <?php foreach ($cart as $item): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <div class="d-flex align-items-center gap-3">
                        <img src="<?= $item['image'] ?? '../assets/images/laptop.png' ?>" alt="<?= $item['name'] ?? '' ?>" width="64">
                        <div>
                          <p class="mb-1 fw-semibold"><?= $item['name'] ?? '' ?></p>
                          <small class="text-muted">Qty: <?= (int)($item['quantity'] ?? $item['qty'] ?? 1) ?></small>
                        </div>
                      </div>
                      <span class="fw-bold">$<?= number_format((float)($item['price'] ?? 0) * (int)($item['quantity'] ?? $item['qty'] ?? 1), 2, '.', ',') ?></span>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p class="text-muted mb-0">Your cart is empty.</p>
                <?php endif; ?>
              </div>
              <hr>
              <div class="d-flex justify-content-between align-items-center">
                <strong>Total</strong>
                <strong data-cart-total>$<?= number_format((float)($total ?? 0), 2, '.', ',') ?></strong>
              </div>
              <button class="btn btn-primary rounded-pill w-100 mt-3" id="confirm-pay">Confirm and Pay</button>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Page-specific scripts -->
  <script src="../assets/js/checkout.js"></script>

  <!-- ================= FOOTER ================= -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>
