<header class="site-header">
  <nav class="navbar navbar-expand-lg py-3" aria-label="Main navigation">
    <div class="container">
      
      <!-- Brand Logo -->
      <div class="navbar-brand d-flex align-items-center">
        <img src="../assets/images/logo.svg" alt="">
        <div class="f-l-div ms-2">
          <h6 class="lo fw-bold d-flex justify-content-center">LOGO</h6>
          <small id="small">Lorem ipsum dolor sit amet</small>
        </div>
      </div>

      <!-- Mobile Toggle -->
      <button 
        class="navbar-toggler" 
        type="button"
        data-bs-toggle="collapse" 
        data-bs-target="#navMenu"
        aria-controls="navMenu"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Navigation Menu -->
      <?php
        // Determine current page (strip extensions and treat empty path as index)
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $current = basename($path ? $path : $_SERVER['PHP_SELF']);
        $current = preg_replace('/\.(php|html)$/i', '', strtolower($current));
        if ($current === '') $current = 'index';

        function nav_active(...$names) {
          global $current;
          foreach ($names as $n) {
            $n = preg_replace('/\.(php|html)$/i', '', strtolower($n));
            if ($n === $current) return 'active';
          }
          return '';
        }

        function nav_aria(...$names) {
          global $current;
          foreach ($names as $n) {
            $n = preg_replace('/\.(php|html)$/i', '', strtolower($n));
            if ($n === $current) return ' aria-current="page"';
          }
          return '';
        }
      ?>
      <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3 gap-2">
          <li class="nav-item">
            <a class="nav-link <?php echo nav_active('index'); ?>" href="index.php"<?php echo nav_aria('index'); ?>>Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo nav_active('shop'); ?>" href="shop.php"<?php echo nav_aria('shop'); ?>>Shop</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?php echo nav_active('index'); ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Pages</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="index.php#about">Why Choose Us</a></li>
              <li><a class="dropdown-item" href="index.php#site-footer">About</a></li>
              <li><a class="dropdown-item <?php echo nav_active('contact'); ?>" href="contact.php"<?php echo nav_aria('contact'); ?>>Contact</a></li>
            </ul>
          </li>
          <li class="nav-item">
            <button class="btn btn-outline-primary px-3 rounded-pill cart-trigger" type="button" data-cart-open aria-label="Open cart">
              <i class="bi bi-cart"></i>
              <span class="cart-count badge bg-primary rounded-pill" data-cart-count hidden>0</span>
            </button>
          </li>
          <li class="nav-item mt-2 mt-lg-0">
            <a href="login.php" class="btn btn-outline-primary px-4 rounded-pill">Sign In</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>
