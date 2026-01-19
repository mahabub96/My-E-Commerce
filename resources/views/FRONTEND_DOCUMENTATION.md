# Frontend Documentation (resources/views)

## Summary ✅
- I copied the entire `frontend/assets/` folder into `resources/views/assets/` so the PHP view files can reference assets with the same relative paths that existed in the original static files.
- No HTML links, CSS rules or JS files were modified — paths remain exactly as originally present in the HTML files (e.g., `../assets/css/style-refactored.css`, `../assets/js/cart.js`, `../assets/images/logo.svg`).

---

## Why this was done 🔧
- The project was converted to PHP views that live under `resources/views/...` and many pages use relative references like `../assets/...`.
- Copying the `assets` directory into `resources/views` preserves those relative links and ensures identical runtime behavior without changing the original HTML references.

---

## What was copied 📁
- `frontend/assets/css/*` → `resources/views/assets/css/` (admin.css, cart.css, product.css, shop.css, style-refactored.css, volt.css)
- `frontend/assets/js/*` → `resources/views/assets/js/` (admin.js, cart.js, main.js, product.js, shop.js)
- `frontend/assets/images/*` → `resources/views/assets/images/` (all images, including `banners/`, `categories/`, `icons/`, `products/` subfolders)

You can confirm locally by listing `resources/views/assets/`.

---

## File mapping table (static → view) 🔁
- `frontend/customer/index.html` → `resources/views/customer/index.php`
- `frontend/customer/shop.html` → `resources/views/customer/shop.php`
- `frontend/customer/product.html` → `resources/views/customer/product.php`
- `frontend/customer/category.html` → `resources/views/customer/category.php`
- `frontend/customer/checkout.html` → `resources/views/customer/checkout.php`
- `frontend/customer/contact.html` → `resources/views/customer/contact.php`
- `frontend/customer/forgot-password.html` → `resources/views/customer/forgot-password.php`
- `frontend/customer/login.html` → `resources/views/customer/login.php`
- `frontend/customer/order-success.html` → `resources/views/customer/order-success.php`
- `frontend/customer/profile.html` → `resources/views/customer/profile.php`
- `frontend/customer/register.html` → `resources/views/customer/register.php`
- `frontend/admin/login.html` → `resources/views/admin/login.php`
- `frontend/admin/product-create.html` → `resources/views/admin/product-create.php`
- `frontend/admin/products.html` → `resources/views/admin/products.php`
- `frontend/admin/orders.html` → `resources/views/admin/orders.php`
- `frontend/admin/order-details.html` → `resources/views/admin/order-details.php`
- `frontend/admin/dashboard.html` → `resources/views/admin/dashboard.php`
- `frontend/admin/category-create.html` → `resources/views/admin/category-create.php`
- `frontend/admin/categories.html` → `resources/views/admin/categories.php`

Partials created:
- `resources/views/partials/header.php`
- `resources/views/partials/footer.php` (contains cart drawer + scripts)
- `resources/views/admin/partials/sidebar.php`
- `resources/views/admin/partials/footer.php`

---

## Important notes & best practices ⚠️
- Asset paths in the views were kept 100% unchanged to preserve pixel-perfect output and JavaScript behavior. Because assets are now duplicated under `resources/views/assets`, the original relative links in the views resolve correctly.
- Internal page links (hrefs) were updated from `.html` to `.php` across the `resources/views` files so navigation points to the new PHP views (search for `href="*.php"` to confirm).
- Keep asset sources synchronized. If you update anything under `frontend/assets/` later, replicate that change into `resources/views/assets/` (or use a build step / symlink in future to avoid duplication).

---

## How to verify locally ✔️
1. Start PHP built-in server from repository root (for a quick local test):
   - php -S localhost:8000 -t resources/views
   - Or configure your web server so that `resources/views` maps to the view-serving routes you use.
2. Open `http://localhost:8000/customer/index.php` in a browser and compare with original static `frontend/customer/index.html`.
3. Test navigation: click links in the header/footer and ensure pages load without 404s; all internal links were converted to `.php`.
4. Test cart: click the cart trigger button on any page — confirm slide-in behavior, add/remove/quantity updates (JS files are unchanged).

---

## Next options (pick one) 🛠️
- I can add a small deploy/build task (e.g., a PowerShell or npm script) to sync `frontend/assets` → `resources/views/assets` automatically.  
- I can extract the cart into `partials/cart.php` for clarity and include it explicitly from pages that need it (if you prefer explicit componentization).

---

If you want, I can now set up a sync script and add it to `package.json` (or a PowerShell script) to keep the two `assets` folders in sync automatically — say the word and I'll add it. 👍

---

## Integration & wiring (short guide) 🧩

- Asset paths: Views reference assets with relative paths like `../assets/css/...` and `../assets/js/...`. If your backend serves static files from a different root (for example `/public`), either:
  - copy `resources/views/assets/` into a publicly served folder in your app and adjust the view references, or
  - keep `resources/views` as a served folder (as we did during testing) so the relative paths keep working.

- Active nav: The header now sets the active nav item server-side. No additional JS is required for the main navigation. If your backend’s routing uses routes without `.php` filenames, ensure your router maps route names (e.g., `/`, `/shop`, `/contact`) so the header logic still receives a path that maps cleanly (you may update the nav matching rules if your backend uses named routes).

- Wiring data: Views contain `<!-- TODO -->` markers where dynamic data should be injected. Typical wiring steps:
  1. Load the data in your backend controller (products, categories, order details).
  2. Pass variables into the view (e.g., `$products`, `$categories`).
  3. Replace the static placeholders in the PHP views with loops/echoes that use those variables.

- Quick smoke-test after integration:
  1. Start your backend dev server and visit key pages (index, shop, product, admin pages).
  2. Verify asset loads in the Network panel and that no 404s appear for `../assets/*` files.
  3. Confirm header active highlighting and sidebar active highlighting on admin pages.

If you want, I can also create a small `CLEANUP.md` with step-by-step wiring instructions for your backend (next step I can add now).