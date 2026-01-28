<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Profile">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>Profile</title>

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
        <div class="page-hero">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
              <h1 class="page-hero__title"><?= htmlspecialchars($user['name'] ?? ($_SESSION['user_name'] ?? '')) ?></h1>
              <p class="page-hero__subtitle">Manage your personal info and orders.</p>
            </div>
            <div class="d-flex align-items-center gap-3">
              <!-- Notification Bell -->
              <div class="position-relative">
                <button class="btn btn-outline-secondary rounded-circle p-2 position-relative" id="notificationBell" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 42px; height: 42px;">
                  <i class="bi bi-bell fs-5"></i>
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">
                    0
                  </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="width: 320px; max-height: 400px; overflow-y: auto;" id="notificationDropdown">
                  <li class="dropdown-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Notifications</span>
                    <button class="btn btn-link btn-sm text-decoration-none p-0" id="markAllRead">Mark all read</button>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li id="notificationList" class="px-2">
                    <p class="text-muted text-center py-3 mb-0">Loading...</p>
                  </li>
                </ul>
              </div>
              <?php
                $profileIncomplete = empty(trim((string)($user['phone'] ?? ''))) || empty(trim((string)($user['address'] ?? ''))) || empty(trim((string)($user['city'] ?? ''))) || empty(trim((string)($user['country'] ?? ''))) || empty(trim((string)($user['postal_code'] ?? '')));
              ?>
              <?php if (!$profileIncomplete): ?>
                <a class="btn btn-primary rounded-pill" href="/checkout">Go to Checkout</a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-lg-4">
            <div class="feature-card h-100">
              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="feature-card__icon"><i class="bi bi-person"></i></div>
                <div>
                  <h3 class="feature-card__title"><?= htmlspecialchars($user['name'] ?? ($_SESSION['user_name'] ?? '')) ?></h3>
                  <p class="feature-card__text mb-0"><?= htmlspecialchars($user['email'] ?? ($_SESSION['auth']['email'] ?? '')) ?></p>
                </div>
              </div>
              <p class="feature-card__text mb-2">
                Address:
                <?php if ($profileIncomplete): ?>
                  <a href="#" class="text-decoration-underline" data-profile-popover>Click to add</a>
                <?php else: ?>
                  <?= htmlspecialchars($user['address'] ?? 'Not provided') ?>
                <?php endif; ?>
              </p>
              <p class="feature-card__text mb-0">
                Phone:
                <?php if ($profileIncomplete): ?>
                  <a href="#" class="text-decoration-underline" data-profile-popover>Click to add</a>
                <?php else: ?>
                  <?= htmlspecialchars($user['phone'] ?? 'Not provided') ?>
                <?php endif; ?>
              </p>
            </div>
          </div>

          <div class="col-lg-8">
            <div class="feature-card">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="feature-card__title mb-0">Order History</h3>
                <a class="btn btn-outline-primary rounded-pill btn-sm" href="/orders">View All</a>
              </div>
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead>
                    <tr>
                      <th scope="col">Order ID</th>
                      <th scope="col">Date</th>
                      <th scope="col">Total</th>
                      <th scope="col">Status</th>
                      <th scope="col">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($orders) && is_array($orders)): ?>
                      <?php foreach ($orders as $o): ?>
                        <tr>
                          <td><?= htmlspecialchars($o['order_number'] ?? ('#' . ($o['id'] ?? ''))) ?></td>
                          <td><?= htmlspecialchars(date('M j, Y', strtotime($o['created_at'] ?? 'now'))) ?></td>
                          <td>$<?= number_format((float)($o['total_amount'] ?? 0), 2, '.', ',') ?></td>
                          <td>
                            <?php $status = $o['order_status'] ?? 'pending'; ?>
                            <?php if ($status === 'processing' || $status === 'pending'): ?>
                              <span class="badge bg-success"><?= htmlspecialchars(ucfirst($status)) ?></span>
                            <?php else: ?>
                              <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($status)) ?></span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <a class="btn btn-outline-primary btn-sm rounded-pill" href="/order-success?order_id=<?= urlencode($o['id'] ?? '') ?>">View</a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="5"><p class="text-muted mb-0">You have no orders yet.</p></td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ================= FOOTER ================= -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>
  
  <!-- Notification Bell JavaScript -->
  <script>
    const CURRENT_USER_ID = <?= json_encode($_SESSION['auth']['id'] ?? null) ?>;
    
    function getCsrfToken() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.getAttribute('content') : '';
    }
    
    // Fetch and display notifications
    async function loadNotifications() {
      if (!CURRENT_USER_ID) return;
      
      const badge = document.getElementById('notificationBadge');
      const list = document.getElementById('notificationList');
      
      try {
        const response = await fetch('/api/notifications?limit=10', {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        
        if (!response.ok) {
          throw new Error('Failed to fetch notifications');
        }
        
        const data = await response.json();
        
        // Update badge
        if (data.unread_count > 0) {
          badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
          badge.style.display = 'block';
        } else {
          badge.style.display = 'none';
        }
        
        // Render notification list
        if (!data.notifications || data.notifications.length === 0) {
          list.innerHTML = '<p class="text-muted text-center py-3 mb-0">No notifications</p>';
        } else {
          list.innerHTML = data.notifications.map(notif => {
            const isUnread = notif.is_read == 0;
            const timeAgo = getTimeAgo(notif.created_at);
            return `
              <div class="notification-item p-2 mb-1 rounded ${isUnread ? 'bg-light' : ''}" data-id="${notif.id}" style="cursor: pointer;">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="flex-grow-1">
                    <h6 class="mb-1 ${isUnread ? 'fw-bold' : ''}">${escapeHtml(notif.title)}</h6>
                    ${notif.message ? `<p class="mb-1 small text-muted">${escapeHtml(notif.message)}</p>` : ''}
                    <small class="text-muted">${timeAgo}</small>
                  </div>
                  ${isUnread ? '<span class="badge bg-primary ms-2">New</span>' : ''}
                </div>
              </div>
            `;
          }).join('');
          
          // Add click handlers
          list.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', async () => {
              const id = item.dataset.id;
              await markAsRead(id);
              const notif = data.notifications.find(n => n.id == id);
              if (notif && notif.link) {
                window.location.href = notif.link;
              }
            });
          });
        }
      } catch (error) {
        console.error('Failed to load notifications:', error);
        if (list) {
          list.innerHTML = '<p class="text-danger text-center py-3 mb-0">Failed to load notifications</p>';
        }
      }
    }
    
    // Mark notification as read
    async function markAsRead(notificationId) {
      try {
        const response = await fetch('/api/notifications/mark-read', {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ notification_id: notificationId })
        });
        const data = await response.json();
        if (data.success) {
          loadNotifications(); // Reload
        }
      } catch (error) {
        console.error('Failed to mark as read:', error);
      }
    }
    
    // Mark all as read
    const markAllBtn = document.getElementById('markAllRead');
    if (markAllBtn) {
      markAllBtn.addEventListener('click', async () => {
        try {
          const response = await fetch('/api/notifications/mark-read', {
            method: 'POST',
            headers: { 
              'Content-Type': 'application/json',
              'X-CSRF-Token': getCsrfToken(),
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({})
          });
          const data = await response.json();
          if (data.success) {
            loadNotifications();
          }
        } catch (error) {
          console.error('Failed to mark all as read:', error);
        }
      });
    }
    
    // Utility: Time ago
    function getTimeAgo(timestamp) {
      const now = new Date();
      const then = new Date(timestamp);
      const seconds = Math.floor((now - then) / 1000);
      
      if (seconds < 60) return 'Just now';
      if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
      if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
      if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`;
      return then.toLocaleDateString();
    }
    
    // Utility: Escape HTML
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
    
    // Load on page load
    document.addEventListener('DOMContentLoaded', loadNotifications);
    
    // Refresh every 30 seconds
    setInterval(loadNotifications, 30000);
  </script>
  
  <!-- Popover template (hidden) -->
  <div id="profile-popover-template" class="d-none">
    <div class="alert alert-danger d-none" data-profile-error></div>
    <div class="alert alert-success d-none" data-profile-success></div>
    <form data-profile-primary-form>
      <div class="mb-2">
        <label class="form-label" for="profile_phone">Primary Phone</label>
        <input class="form-control" id="profile_phone" name="phone" type="text" placeholder="Digits only" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
      </div>
      <div class="mb-2">
        <label class="form-label" for="profile_address">Primary Address</label>
        <input class="form-control" id="profile_address" name="address" type="text" placeholder="123 Main St" required value="<?= htmlspecialchars($user['address'] ?? '') ?>">
      </div>
      <div class="mb-2">
        <label class="form-label" for="profile_city">City</label>
        <input class="form-control" id="profile_city" name="city" type="text" placeholder="City" required value="<?= htmlspecialchars($user['city'] ?? '') ?>">
      </div>
      <div class="mb-2">
        <label class="form-label" for="profile_country">Country</label>
        <input class="form-control" id="profile_country" name="country" type="text" placeholder="Country" required value="<?= htmlspecialchars($user['country'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="profile_postal">Postal Code</label>
        <input class="form-control" id="profile_postal" name="postal_code" type="text" placeholder="ZIP" required value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>">
      </div>
      <button class="btn btn-primary rounded-pill w-100" type="submit">Save Profile</button>
    </form>
  </div>
