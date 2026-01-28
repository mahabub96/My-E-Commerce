document.addEventListener('DOMContentLoaded', () => {
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const tbody = document.getElementById('admin-products-tbody');
  const searchInput = document.getElementById('admin-search');

  async function fetchProducts(query = '') {
    try {
      const url = '/admin/products' + (query ? '?search=' + encodeURIComponent(query) : '');
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
      const json = await res.json();
      renderProducts(json.products || json.data || []);
    } catch (err) {
      console.error('Failed to load admin products', err);
    }
  }

  function renderProducts(items) {
    if (!tbody) return;
    tbody.innerHTML = '';
    items.forEach(p => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${p.name}</td>
        <td>${p.category_name ?? ''}</td>
        <td>$${Number(p.price).toFixed(2)}</td>
        <td>${Number(p.quantity)}</td>
        <td>${p.status === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td>
        <td class="admin-actions">
          <a class="btn btn-outline-primary btn-sm" href="/admin/products/${p.id}/edit">Edit</a>
          <button class="btn btn-outline-danger btn-sm" data-delete data-id="${p.id}">Delete</button>
        </td>
      `;
      tbody.appendChild(tr);
    });

    // bind delete buttons
    tbody.querySelectorAll('[data-delete]').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        const id = btn.dataset.id;
        if (!confirm('Delete this product?')) return;
        try {
          const res = await fetch(`/admin/products/${id}/delete`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': csrf(),
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({})
          });
          const json = await res.json();
          if (json && json.success) {
            fetchProducts(searchInput?.value || '');
          } else {
            alert(json.message || 'Delete failed');
          }
        } catch (err) {
          console.error(err);
          alert('Request failed');
        }
      });
    });
  }

  // Search debounce
  let debounce;
  searchInput?.addEventListener('input', (e) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => fetchProducts(e.target.value), 300);
  });

  // Add clear button to search input
  if (searchInput && window.addClearButton) {
    window.addClearButton(searchInput);
  }

  // Product-create form submit (create or update)
  const form = document.getElementById('admin-product-form');
  if (form) {
    // Collect all four individual file inputs (created in the form)
    const imageInputs = Array.from(document.querySelectorAll('#prodImages, #prodImages2, #prodImages3, #prodImages4'));
    const existingContainer = document.getElementById('existingImages');
    let newPreviewContainer = document.getElementById('newImagesPreview');
    if (!newPreviewContainer) {
      newPreviewContainer = document.createElement('div');
      newPreviewContainer.id = 'newImagesPreview';
      newPreviewContainer.style.display = 'flex';
      newPreviewContainer.style.flexWrap = 'wrap';
      newPreviewContainer.style.gap = '8px';
      newPreviewContainer.style.marginTop = '8px';
      // insert after the last file input
      const lastInput = imageInputs[imageInputs.length - 1];
      lastInput && lastInput.insertAdjacentElement('afterend', newPreviewContainer);
    }

    const ensureHidden = (name) => {
      let input = form.querySelector(`input[type="hidden"][name="${name}"]`);
      if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.appendChild(input);
      }
      return input;
    };

    const primaryPathInput = ensureHidden('primary_image_path');
    const primaryUploadInput = ensureHidden('primary_image_upload_index');

    const clearPrimaryHighlight = () => {
      const all = document.querySelectorAll('.existing-image, .new-image');
      all.forEach(w => {
        w.style.border = '1px solid #e3e7ed';
        const star = w.querySelector('[data-primary-toggle]');
        if (star) star.textContent = '☆';
        w.dataset.primary = '';
      });
    };

    const setPrimaryPath = (path) => {
      primaryPathInput.value = path || '';
      primaryUploadInput.value = '';
      primaryUploadInput.dataset.baseIndex = '';
      clearPrimaryHighlight();
      const wrap = document.querySelector(`.existing-image[data-path="${path}"]`);
      if (wrap) {
        wrap.style.border = '2px solid #0d6efd';
        const star = wrap.querySelector('[data-primary-toggle]');
        if (star) star.textContent = '★';
        wrap.dataset.primary = '1';
      }
    };

    const setPrimaryUploadIndex = (index) => {
      primaryPathInput.value = '';
      primaryUploadInput.value = String(index);
      primaryUploadInput.dataset.baseIndex = String(index);
      clearPrimaryHighlight();
      const wrap = document.querySelector(`.new-image[data-index="${index}"]`);
      if (wrap) {
        wrap.style.border = '2px solid #0d6efd';
        const star = wrap.querySelector('[data-primary-toggle]');
        if (star) star.textContent = '★';
        wrap.dataset.primary = '1';
      }
    };

    const updateUploadEnabled = () => {
      const existingCount = existingContainer ? existingContainer.querySelectorAll('.existing-image').length : 0;
      imageInputs.forEach(inp => { inp.disabled = existingCount >= 4; });
    };

    const getFlattenedFiles = () => {
      const arr = [];
      imageInputs.forEach((inp, inpIdx) => {
        Array.from(inp.files || []).forEach((f, fileIdx) => {
          arr.push({ file: f, inputIndex: inpIdx, fileIndexInInput: fileIdx });
        });
      });
      return arr;
    };

    const renderNewUploads = () => {
      if (!newPreviewContainer) return;
      const files = getFlattenedFiles();
      newPreviewContainer.innerHTML = '';
      files.forEach((item, idx) => {
        const file = item.file;
        const wrapper = document.createElement('div');
        wrapper.className = 'new-image';
        wrapper.style.width = '96px';
        wrapper.style.height = '96px';
        wrapper.style.position = 'relative';
        wrapper.style.border = '1px solid #e3e7ed';
        wrapper.style.borderRadius = '8px';
        wrapper.style.overflow = 'hidden';
        wrapper.style.cursor = 'pointer';
        wrapper.dataset.index = String(idx);
        wrapper.dataset.input = String(item.inputIndex);
        wrapper.dataset.fileIndex = String(item.fileIndexInInput);

        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = 'New upload';
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.innerHTML = '&times;';
        removeBtn.title = 'Remove image';
        removeBtn.style.position = 'absolute';
        removeBtn.style.top = '6px';
        removeBtn.style.right = '6px';
        removeBtn.style.width = '24px';
        removeBtn.style.height = '24px';
        removeBtn.style.borderRadius = '50%';
        removeBtn.style.border = 'none';
        removeBtn.style.background = 'rgba(0,0,0,0.6)';
        removeBtn.style.color = '#fff';
        removeBtn.style.fontSize = '16px';
        removeBtn.style.lineHeight = '24px';
        removeBtn.style.textAlign = 'center';
        removeBtn.style.cursor = 'pointer';

        const primaryBtn = document.createElement('button');
        primaryBtn.type = 'button';
        primaryBtn.textContent = '☆';
        primaryBtn.title = 'Set as primary';
        primaryBtn.setAttribute('data-primary-toggle', 'true');
        primaryBtn.style.position = 'absolute';
        primaryBtn.style.top = '6px';
        primaryBtn.style.left = '6px';
        primaryBtn.style.width = '24px';
        primaryBtn.style.height = '24px';
        primaryBtn.style.borderRadius = '50%';
        primaryBtn.style.border = 'none';
        primaryBtn.style.background = 'rgba(0,0,0,0.6)';
        primaryBtn.style.color = '#fff';
        primaryBtn.style.fontSize = '14px';
        primaryBtn.style.lineHeight = '24px';
        primaryBtn.style.textAlign = 'center';
        primaryBtn.style.cursor = 'pointer';

        primaryBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          setPrimaryUploadIndex(idx);
        });

        removeBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          // rebuild the specific input's FileList excluding this file
          const inpIdx = parseInt(wrapper.dataset.input, 10);
          const fileIdx = parseInt(wrapper.dataset.fileIndex, 10);
          const targetInput = imageInputs[inpIdx];
          if (!targetInput) return;
          const dt = new DataTransfer();
          Array.from(targetInput.files).forEach((f, i) => { if (i !== fileIdx) dt.items.add(f); });
          targetInput.files = dt.files;
          // If primary pointed to a flattened index that is removed, clear it
          if (primaryUploadInput.dataset.baseIndex === String(idx)) {
            primaryUploadInput.value = '';
            primaryUploadInput.dataset.baseIndex = '';
          }
          renderNewUploads();
        });

        wrapper.appendChild(img);
        wrapper.appendChild(removeBtn);
        wrapper.appendChild(primaryBtn);
        newPreviewContainer.appendChild(wrapper);
      });
    };

    // Attach change handlers to each input to enforce limits and re-render previews
    imageInputs.forEach((inp) => {
      inp.addEventListener('change', () => {
        const existingCount = existingContainer ? existingContainer.querySelectorAll('.existing-image').length : 0;
        const maxAllowed = Math.max(0, 4 - existingCount);

        // Combine all files and enforce total maxAllowed
        let files = getFlattenedFiles().map(i => i.file);

        // Client-side size validation (8MB limit) - remove oversized files and inform the user
        const maxSizeBytes = 8 * 1024 * 1024;
        const tooLarge = files.filter(f => f.size > maxSizeBytes);
        if (tooLarge.length) {
          alert('The following files exceed the 8MB limit and were removed: ' + tooLarge.map(f => f.name).join(', '));
          // remove oversized files from their inputs
          imageInputs.forEach((inputEl) => {
            const dt = new DataTransfer();
            Array.from(inputEl.files).forEach(f => { if (f.size <= maxSizeBytes) dt.items.add(f); });
            inputEl.files = dt.files;
          });
          files = getFlattenedFiles().map(i => i.file);
        }

        if (files.length > maxAllowed) {
          alert(`You can upload up to ${maxAllowed} more image(s).`);
          // Trim extras from the current input (last selected items)
          // Rebuild current input files to fit remaining allowance
          const currFileList = Array.from(inp.files || []);
          const available = Math.max(0, maxAllowed - (files.length - currFileList.length));
          const trimmed = currFileList.slice(0, available);
          const dt = new DataTransfer();
          trimmed.forEach(f => dt.items.add(f));
          inp.files = dt.files;
        }

        renderNewUploads();
      });
    });

    renderNewUploads();
    // If editing, prefill when ?id= is present
    const params = new URLSearchParams(window.location.search);
    let editingId = params.get('id');
    if (!editingId) {
      const match = window.location.pathname.match(/\/admin\/products\/(\d+)\/edit/);
      if (match) editingId = match[1];
    }
    let editingCategoryId = null;
    if (editingId) {
      (async () => {
        try {
          const res = await fetch(`/admin/products/${editingId}/edit`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
          const json = await res.json();
          if (json && json.product) {
            document.getElementById('prodName').value = json.product.name || '';
            document.getElementById('prodPrice').value = json.product.price || 0;
            document.getElementById('prodStock').value = json.product.quantity || 0;
            document.getElementById('prodDesc').value = json.product.description || '';
            const discountInput = document.getElementById('prodDiscount');
            if (discountInput) discountInput.value = json.product.discount_price || '';
            const skuInput = document.getElementById('prodSku');
            if (skuInput) skuInput.value = json.product.sku || '';
            const featuredInput = document.getElementById('prodFeatured');
            if (featuredInput) featuredInput.checked = !!json.product.featured;
            // status mapping
            document.getElementById('prodStatus').value = json.product.status === 'active' ? 'active' : 'inactive';
            editingCategoryId = json.product.category_id || null;

            // categories will be fetched below

            // If product has existing images, show thumbnails with remove (X), replace-on-click, and set primary
            try {
              const addRemoveHidden = (path) => {
                if (form.querySelector(`input[type="hidden"][name="remove_images[]"][value="${path}"]`)) return;
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'remove_images[]';
                hidden.value = path;
                form.appendChild(hidden);
              };

              if (existingContainer && Array.isArray(json.images)) {
                existingContainer.innerHTML = '';
                json.images.forEach((img) => {
                  const path = img.image_path || img.url || img.path || img;
                  const wrapper = document.createElement('div');
                  wrapper.className = 'existing-image';
                  wrapper.style.width = '96px';
                  wrapper.style.height = '96px';
                  wrapper.style.position = 'relative';
                  wrapper.style.border = '1px solid #e3e7ed';
                  wrapper.style.borderRadius = '8px';
                  wrapper.style.overflow = 'hidden';
                  wrapper.style.cursor = 'pointer';
                  wrapper.dataset.path = path;

                  const thumb = document.createElement('img');
                  thumb.src = img.url || path;
                  thumb.alt = json.product.name || 'Image';
                  thumb.style.width = '100%';
                  thumb.style.height = '100%';
                  thumb.style.objectFit = 'cover';

                  const removeBtn = document.createElement('button');
                  removeBtn.type = 'button';
                  removeBtn.innerHTML = '&times;';
                  removeBtn.title = 'Remove image';
                  removeBtn.style.position = 'absolute';
                  removeBtn.style.top = '6px';
                  removeBtn.style.right = '6px';
                  removeBtn.style.width = '24px';
                  removeBtn.style.height = '24px';
                  removeBtn.style.borderRadius = '50%';
                  removeBtn.style.border = 'none';
                  removeBtn.style.background = 'rgba(0,0,0,0.6)';
                  removeBtn.style.color = '#fff';
                  removeBtn.style.fontSize = '16px';
                  removeBtn.style.lineHeight = '24px';
                  removeBtn.style.textAlign = 'center';
                  removeBtn.style.cursor = 'pointer';

                  const primaryBtn = document.createElement('button');
                  primaryBtn.type = 'button';
                  primaryBtn.textContent = '☆';
                  primaryBtn.title = 'Set as primary';
                  primaryBtn.setAttribute('data-primary-toggle', 'true');
                  primaryBtn.style.position = 'absolute';
                  primaryBtn.style.top = '6px';
                  primaryBtn.style.left = '6px';
                  primaryBtn.style.width = '24px';
                  primaryBtn.style.height = '24px';
                  primaryBtn.style.borderRadius = '50%';
                  primaryBtn.style.border = 'none';
                  primaryBtn.style.background = 'rgba(0,0,0,0.6)';
                  primaryBtn.style.color = '#fff';
                  primaryBtn.style.fontSize = '14px';
                  primaryBtn.style.lineHeight = '24px';
                  primaryBtn.style.textAlign = 'center';
                  primaryBtn.style.cursor = 'pointer';

                  // hidden input for replacement file
                  const replaceInput = document.createElement('input');
                  replaceInput.type = 'file';
                  replaceInput.accept = 'image/*';
                  replaceInput.style.display = 'none';

                  const openReplace = () => replaceInput.click();
                  wrapper.addEventListener('click', (e) => {
                    if (e.target === removeBtn || e.target === primaryBtn) return;
                    openReplace();
                  });

                  primaryBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    setPrimaryPath(path);
                  });

                  replaceInput.addEventListener('change', () => {
                    if (!replaceInput.files || !replaceInput.files.length) return;
                    const file = replaceInput.files[0];
                    if (file.size > 8 * 1024 * 1024) {
                      alert('Replacement file must be <= 8MB');
                      replaceInput.value = '';
                      return;
                    }
                    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!allowed.includes(file.type)) {
                      alert('Only JPG, PNG or WEBP files are allowed');
                      replaceInput.value = '';
                      return;
                    }
                    wrapper._replacement = file;
                    addRemoveHidden(path);
                    if (primaryPathInput.value === path) {
                      wrapper.dataset.primaryReplacement = '1';
                    }
                    thumb.src = URL.createObjectURL(file);
                  });

                  removeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    addRemoveHidden(path);
                    if (primaryPathInput.value === path) {
                      primaryPathInput.value = '';
                    }
                    wrapper.remove();
                    updateUploadEnabled();
                  });

                  wrapper.appendChild(thumb);
                  wrapper.appendChild(removeBtn);
                  wrapper.appendChild(primaryBtn);
                  wrapper.appendChild(replaceInput);
                  existingContainer.appendChild(wrapper);
                });

                updateUploadEnabled();
              }
            } catch (err) { console.error('Failed to render images', err); }
          }
        } catch (err) { console.error(err); }
      })();
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Enforce client-side limits and prepare FormData
      const existingCount = existingContainer ? existingContainer.querySelectorAll('.existing-image').length : 0;
      const uploadCount = getFlattenedFiles().length;
      const finalCount = existingCount + uploadCount;

      if (finalCount > 4) {
        alert('Final images count exceeds 4. Remove images or reduce uploads to 4 or fewer.');
        return;
      }

      // Resolve replacement + upload order
      const replacementFiles = [];
      if (existingContainer) {
        existingContainer.querySelectorAll('.existing-image').forEach(w => {
          if (w._replacement) replacementFiles.push({ file: w._replacement, wrapper: w });
        });
      }
      const uploadFiles = getFlattenedFiles().map(i => i.file);

      // Determine primary upload index when needed
      let primaryUploadIndexFinal = null;
      if (primaryPathInput.value) {
        const idx = replacementFiles.findIndex(r => r.wrapper.dataset.path === primaryPathInput.value && r.wrapper.dataset.primaryReplacement === '1');
        if (idx >= 0) {
          primaryUploadIndexFinal = idx;
          primaryPathInput.value = '';
        }
      }

      if (!primaryPathInput.value) {
        const baseIndex = primaryUploadInput.dataset.baseIndex !== '' ? parseInt(primaryUploadInput.dataset.baseIndex, 10) : NaN;
        if (!Number.isNaN(baseIndex)) {
          primaryUploadIndexFinal = replacementFiles.length + baseIndex;
        }
      }

      primaryUploadInput.value = primaryUploadIndexFinal !== null ? String(primaryUploadIndexFinal) : '';

      const payloadUrl = editingId ? `/admin/products/${editingId}` : '/admin/products';
      const method = editingId ? 'POST' : 'POST'; // both endpoints accept POST

      const buildFd = () => {
        const _fd = new FormData();
        new FormData(form).forEach((v, k) => {
          if (/^images(\[.*\])?$/.test(k)) return;
          _fd.append(k, v);
        });
        const _clientNames = [];
        replacementFiles.forEach(r => { _fd.append('images[]', r.file); _clientNames.push(r.file.name); });
        uploadFiles.forEach(f => { _fd.append('images[]', f); _clientNames.push(f.name); });
        _fd.append('client_file_names', JSON.stringify(_clientNames));
        _fd.append('client_file_count', String(_clientNames.length));
        // If debug_upload=1 is present in the page URL, include it in the FormData so server returns diagnostics
        if (window.location.search.indexOf('debug_upload=1') !== -1) {
          _fd.append('debug_upload', '1');
        }
        return _fd;
      };

      const sendWithRetry = async (attempt = 0) => {
        try {
          const _fd = buildFd();
          const res = await fetch(payloadUrl, {
            method,
            headers: { 'X-CSRF-Token': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            body: _fd
          });
          const json = await res.json();
          if (res.status === 400 && json && /Upload incomplete/i.test(json.message || '')) {
            if (attempt < 2) {
              // small backoff
              await new Promise(r => setTimeout(r, 1000 * (attempt + 1)));
              return sendWithRetry(attempt + 1);
            }
            alert('Upload failed: incomplete upload. Please try again or use a different browser.');
            return;
          }
          if (json && json.success) {
            window.location.href = '/admin/products';
          } else if (json && json.errors) {
            // Show specific validation errors returned by the server
            console.error('Server validation errors:', json.errors);
            const messages = [];
            Object.keys(json.errors).forEach(k => {
              const v = json.errors[k];
              if (Array.isArray(v)) {
                v.forEach(msg => messages.push(`${k}: ${msg}`));
              } else if (typeof v === 'string') {
                messages.push(`${k}: ${v}`);
              }
            });
            alert(messages.length ? messages.join('\n') : (json.message || 'Validation failed'));
          } else {
            alert(json.message || 'Save failed');
          }
        } catch (err) {
          console.error(err);
          alert('Save failed');
        }
      };

      await sendWithRetry();
    });

    // Load categories into select
    (async () => {
      try {
        const res = await fetch('/admin/products/create', { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        const sel = document.getElementById('prodCategory');
        if (json && json.categories && sel) {
          sel.innerHTML = '';
          json.categories.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            sel.appendChild(opt);
          });
          if (editingCategoryId) {
            sel.value = String(editingCategoryId);
          }
        }
      } catch (err) { console.error(err); }
    })();
  }

  // initial load
  fetchProducts();
});