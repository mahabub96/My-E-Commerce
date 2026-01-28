document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('checkout-form');
  const confirmBtn = document.getElementById('confirm-pay');

  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  async function submitCheckout(e) {
    if (e) e.preventDefault();
    if (!form) return;

    // Prevent submission when cart is empty
    const summary = document.querySelector('[data-checkout-summary]');
    if (summary && summary.textContent.trim().toLowerCase().includes('your cart is empty')) {
      alert('Cart is empty.');
      return;
    }

    // Prevent submission when a non-configured payment method is selected (UX: selections allowed, but placement blocked)
    const selectedMethodEl = form.querySelector('input[name="payment_method"]:checked');
    if (selectedMethodEl) {
      const configured = selectedMethodEl.dataset.configured === '1';
      const method = selectedMethodEl.value;
      if ((method === 'card' || method === 'paypal') && !configured) {
        alert('Selected payment method is temporarily unavailable. Please choose Cash on Delivery (COD).');
        return;
      }
    }

    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());

    // normalize payment method to backend values
    if (payload.payment_method === 'card') payload.payment_method = 'stripe';

    try {
      const res = await fetch('/checkout/process', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      });

      // Try to parse JSON; if response is not JSON, show the raw text for easier diagnosis
      let json = null;
      try {
        json = await res.json();
      } catch (parseErr) {
        const text = await res.text();
        console.error('Non-JSON response from /checkout/process', res.status, text);
        alert(text || 'Checkout failed. Please try again.');
        return;
      }

      if (json && json.redirect) {
        window.location.href = json.redirect;
        return;
      }

      if (json && json.success) {
        window.location.href = '/order-success?order_id=' + encodeURIComponent(json.order_id || '');
        return;
      }

      if (json && json.errors) {
        // basic error display
        alert('Validation failed. Please correct your inputs.');
        return;
      }

      if (json && json.message) {
        // Keep user alerts simple for common cases
        const m = String(json.message || '');
        if (m === 'Cart is empty' || m === 'Cart appears malformed or empty') {
          alert('Cart is empty.');
        } else {
          alert(m);
        }
        return;
      }

      alert('Checkout failed. Please try again.');
    } catch (err) {
      console.error(err);
      alert('Could not process checkout. Please try again.');
    }
  }

  form?.addEventListener('submit', submitCheckout);
  confirmBtn?.addEventListener('click', submitCheckout);
});