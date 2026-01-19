  <footer class="site-footer" id="site-footer" role="contentinfo">
    <div class="container">
      <div class="row gy-4">

        <!-- Brand Column -->
        <div class="col-lg-4 col-md-6">
          <div class="footer-brand d-flex align-items-center mb-3">
            <img src="../assets/images/logo.svg" alt="">
            <div class="f-l-div ms-2">
              <h6 class="lo fw-bold d-flex justify-content-center">LOGO</h6>
              <small id="small">Lorem ipsum dolor sit amet</small>
            </div>
          </div>

          <p class="footer-text">
            Build a modern and creative website with crealand
          </p>

          <nav class="footer-social" aria-label="Social media links">
            <a href="#" aria-label="Google"><i class="bi bi-google" aria-hidden="true"></i></a>
            <a href="#" aria-label="Twitter"><i class="bi bi-twitter" aria-hidden="true"></i></a>
            <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
            <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin" aria-hidden="true"></i></a>
          </nav>
        </div>

        <!-- Product Links -->
        <div class="col-lg-2 col-md-3 col-6">
          <h3 class="footer-title">Product</h3>
          <ul class="footer-list">
            <li><a href="#">Documentation</a></li>
            <li><a href="#">Referral Program</a></li>
            <li><a href="#">Pricing</a></li>
            <li><a href="#">Features</a></li>
            <li><a href="#">Landingpage</a></li>
          </ul>
        </div>

        <!-- Services Links -->
        <div class="col-lg-2 col-md-3 col-6">
          <h3 class="footer-title">Services</h3>
          <ul class="footer-list">
            <li><a href="#">Themes</a></li>
            <li><a href="#">Illustrations</a></li>
            <li><a href="#">UI Kit</a></li>
            <li><a href="#">Design</a></li>
            <li><a href="#">Documentation</a></li>
          </ul>
        </div>

        <!-- Company Links -->
        <div class="col-lg-2 col-md-3 col-6">
          <h3 class="footer-title">Company</h3>
          <ul class="footer-list">
            <li><a href="#">Privacy Policy</a></li>
            <li><a href="#">Careers</a></li>
            <li><a href="#">Terms</a></li>
            <li><a href="#">About</a></li>
          </ul>
        </div>

        <!-- More Links -->
        <div class="col-lg-2 col-md-3 col-6">
          <h3 class="footer-title">More</h3>
          <ul class="footer-list">
            <li><a href="#">Changelog</a></li>
            <li><a href="#">License</a></li>
            <li><a href="#">Documentation</a></li>
          </ul>
        </div>

      </div>
    </div>
  </footer>

  <!-- Cart Drawer -->
  <div class="cart-drawer" data-cart-drawer>
    <div class="cart-overlay" data-cart-overlay></div>
    <aside class="cart-panel" role="dialog" aria-modal="true" aria-label="Shopping cart" data-cart-panel tabindex="-1">
      <div class="cart-header">
        <h3 class="cart-title">Your Cart</h3>
        <button class="cart-close" type="button" data-cart-close aria-label="Close cart"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="cart-items" data-cart-items></div>
      <div class="cart-empty" data-cart-empty hidden>Your cart is empty.</div>
      <div class="cart-footer">
        <div class="cart-summary"><span>Subtotal</span><span data-cart-total>$0</span></div>
        <div class="cart-actions">
          <a class="btn btn-primary rounded-pill" href="checkout.php">Go to Checkout</a>
          <button class="btn btn-outline-primary rounded-pill" type="button" data-cart-close>Continue Shopping</button>
        </div>
      </div>
    </aside>
  </div>

  <!-- Bootstrap Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/main.js"></script>
  <script src="../assets/js/cart.js"></script>
</body>
</html>
