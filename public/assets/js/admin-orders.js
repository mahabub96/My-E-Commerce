/**
 * Admin Orders Live Search
 * 
 * Implements AJAX-based live search for admin order listings
 * with debouncing and clear button functionality
 */

document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.querySelector('input[name="search"]');
  const statusFilter = document.querySelector('select[name="status"]');
  const filterForm = searchInput?.closest('form');
  const tableBody = document.querySelector('.table tbody');
  
  if (!searchInput || !tableBody) return;
  
  let searchDebounceTimer = null;
  let activeSearchRequest = null;
  let originalRows = tableBody.innerHTML;
  
  // Add clear button using utility function
  if (window.addClearButton) {
    window.addClearButton(searchInput);
  }
  
  const renderOrders = (orders) => {
    if (!orders || orders.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No orders found.</td></tr>';
      return;
    }
    
    tableBody.innerHTML = orders.map(o => {
      const status = o.order_status || 'pending';
      let badge = 'bg-secondary';
      if (status === 'processing') badge = 'bg-success';
      else if (status === 'pending') badge = 'bg-warning text-dark';
      else if (status === 'completed') badge = 'bg-primary';
      else if (status === 'cancelled') badge = 'bg-danger';
      
      const orderNumber = o.order_number || '#' + o.id;
      const customerName = o.customer_name || 'Customer';
      const date = new Date(o.created_at).toISOString().split('T')[0];
      const total = parseFloat(o.total_amount || 0).toFixed(2);
      
      return `
        <tr>
          <td>${orderNumber}</td>
          <td>${customerName}</td>
          <td>${date}</td>
          <td>$${total}</td>
          <td><span class="badge ${badge}">${status.charAt(0).toUpperCase() + status.slice(1)}</span></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="/admin/orders?id=${o.id}">View</a>
          </td>
        </tr>
      `;
    }).join('');
  };
  
  const fetchOrders = async (isReset = false) => {
    // Abort any active request
    if (activeSearchRequest) {
      activeSearchRequest.abort();
      activeSearchRequest = null;
    }
    
    const controller = new AbortController();
    activeSearchRequest = controller;
    
    const params = new URLSearchParams();
    const search = searchInput.value.trim();
    const status = statusFilter.value;
    
    if (search) params.set('search', search);
    if (status) params.set('status', status);
    
    try {
      const res = await fetch(`/admin/orders?${params.toString()}`, {
        headers: { 
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        signal: controller.signal
      });
      
      const json = await res.json();
      
      if (json && json.success) {
        renderOrders(json.orders || []);
        
        // Save original state if resetting
        if (isReset) {
          originalRows = tableBody.innerHTML;
        }
      }
    } catch (err) {
      if (err.name !== 'AbortError') {
        console.error('Failed to fetch orders:', err);
      }
    } finally {
      if (activeSearchRequest === controller) {
        activeSearchRequest = null;
      }
    }
  };
  
  // Live search on input with debouncing
  searchInput.addEventListener('input', () => {
    if (searchDebounceTimer) {
      clearTimeout(searchDebounceTimer);
    }
    
    if (activeSearchRequest) {
      activeSearchRequest.abort();
      activeSearchRequest = null;
    }
    
    const term = searchInput.value.trim();
    
    // Empty search - restore original
    if (!term) {
      tableBody.innerHTML = originalRows;
      return;
    }
    
    // Debounce 300ms
    searchDebounceTimer = setTimeout(() => {
      fetchOrders(false);
    }, 300);
  });
  
  // Status filter change
  statusFilter?.addEventListener('change', () => {
    fetchOrders(false);
  });
  
  // Prevent form submission (use AJAX instead)
  filterForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    fetchOrders(false);
  });
});
