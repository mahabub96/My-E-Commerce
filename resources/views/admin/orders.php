<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Orders list">
  <title>Orders</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/volt.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-app d-flex">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="flex-grow-1">
    <header class="admin-topbar navbar navbar-light px-3">
      <button class="btn btn-outline-secondary d-lg-none" type="button" data-admin-toggle><i class="bi bi-list"></i></button>
      <span class="navbar-brand mb-0">Orders</span>
    </header>

    <main class="admin-main container">
      <div class="admin-card">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
          <h5 class="mb-0">Recent Orders</h5>
          <div class="d-flex gap-2">
            <input class="form-control" type="search" placeholder="Search orders">
            <select class="form-select" aria-label="Filter status">
              <option selected>Status: All</option>
              <option>Pending</option>
              <option>Paid</option>
              <option>Shipped</option>
              <option>Delivered</option>
            </select>
          </div>
        </div>
        <div class="table-responsive">
          <!-- TODO: load orders from database -->
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
              <tr>
                <td>#1322</td>
                <td>Jane Doe</td>
                <td>2024-06-12</td>
                <td>$1,799</td>
                <td><span class="badge bg-success">Paid</span></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="order-details.php">View</a>
                </td>
              </tr>
              <tr>
                <td>#1321</td>
                <td>Mark Smith</td>
                <td>2024-06-11</td>
                <td>$249</td>
                <td><span class="badge bg-warning text-dark">Pending</span></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="order-details.php">View</a>
                </td>
              </tr>
              <tr>
                <td>#1320</td>
                <td>Sarah Lee</td>
                <td>2024-06-10</td>
                <td>$899</td>
                <td><span class="badge bg-info text-dark">Shipped</span></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="order-details.php">View</a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>
