/**
 * Live Product Search with Suggestions
 * Features: Single clear button, Smart pagination, No "Show More" if ≤ 4 results
 */

document.addEventListener('DOMContentLoaded', function() {
  'use strict';

  // Configuration
  const DEBOUNCE_DELAY = 300;
  const MIN_SEARCH_LENGTH = 2;
  const RESULTS_PER_PAGE = 4;

  // Shared state
  let debounceTimer = null;
  let abortController = null;
  const initializedInputs = new WeakSet(); // Track which inputs have been initialized

  /**
   * Main initialization function for search inputs
   */
  function initSearch(inputElement) {
    if (!inputElement) return;

    // Prevent double initialization
    if (initializedInputs.has(inputElement)) {
      return;
    }
    initializedInputs.add(inputElement);

    const form = inputElement.closest('form');
    if (!form) return;

    // Check if this input already has been initialized
    if (inputElement.dataset.searchInitialized === 'true') {
      return;
    }
    inputElement.dataset.searchInitialized = 'true';

    // Prevent double initialization of the clear button in the form
    // Use both dataset and a physical check for the button
    let clearBtn = form.querySelector('button[data-search-clear-btn="true"]');
    
    if (!clearBtn) {
      // Check for other common clear button classes just in case
      clearBtn = form.querySelector('button.search-clear-btn, button.btn-clear-search, button.btn-clear');
    }

    if (!clearBtn) {
      // Create clear button only if truly doesn't exist
      clearBtn = document.createElement('button');
      clearBtn.type = 'button';
      clearBtn.setAttribute('data-search-clear-btn', 'true');
      clearBtn.className = 'search-clear-btn';
      clearBtn.innerHTML = '&times;';
      clearBtn.setAttribute('aria-label', 'Clear search');
      clearBtn.style.cssText = `
        position: absolute;
        right: 60px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        font-size: 1.5rem;
        line-height: 1;
        cursor: pointer;
        display: none;
        padding: 0.25rem 0.5rem;
        z-index: 10;
        color: #999;
        transition: color 0.2s;
      `;

      clearBtn.addEventListener('mouseenter', () => clearBtn.style.color = '#000');
      clearBtn.addEventListener('mouseleave', () => clearBtn.style.color = '#999');

      form.style.position = 'relative';
      form.appendChild(clearBtn);
    }

    // Create suggestion dropdown (only if doesn't exist)
    let suggestionBox = form.querySelector('[data-search-suggestions]');
    if (!suggestionBox) {
      suggestionBox = document.createElement('div');
      suggestionBox.setAttribute('data-search-suggestions', 'true');
      suggestionBox.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 0 0 6px 6px;
        max-height: 500px;
        overflow-y: auto;
        z-index: 9999;
        display: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        margin-top: -1px;
      `;
      form.appendChild(suggestionBox);
    }

    // Clear button click handler (attach only once)
    if (!clearBtn.dataset.clickHandlerAttached) {
      clearBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if (abortController) {
          abortController.abort();
          abortController = null;
        }

        if (debounceTimer) {
          clearTimeout(debounceTimer);
          debounceTimer = null;
        }

        inputElement.value = '';
        suggestionBox.style.display = 'none';
        clearBtn.style.display = 'none';
        inputElement.focus();

        const evt = new Event('input', { bubbles: true });
        inputElement.dispatchEvent(evt);
      });
      clearBtn.dataset.clickHandlerAttached = 'true';
    }

    // Store current offset for pagination
    const paginationState = { offset: 0, query: '', totalResults: 0 };

    // Input listener
    inputElement.addEventListener('input', function(e) {
      const query = e.target.value.trim();

      // Reset pagination on new search
      paginationState.offset = 0;
      paginationState.query = query;

      // Update clear button visibility
      clearBtn.style.display = query.length > 0 ? 'block' : 'none';

      // Clear old timer
      if (debounceTimer) {
        clearTimeout(debounceTimer);
      }

      // Abort old request
      if (abortController) {
        abortController.abort();
      }

      // Hide if too short
      if (query.length < MIN_SEARCH_LENGTH) {
        suggestionBox.style.display = 'none';
        return;
      }

      // Debounce
      debounceTimer = setTimeout(function() {
        fetchSuggestions(query, 0, suggestionBox, paginationState);
      }, DEBOUNCE_DELAY);
    });

    // Fetch suggestions
    function fetchSuggestions(query, offset, box, state) {
      abortController = new AbortController();

      fetch('/api/search/suggestions?q=' + encodeURIComponent(query) + '&offset=' + offset, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        signal: abortController.signal
      })
      .then(r => r.json())
      .then(data => {
        if (data && data.success && data.suggestions) {
          state.totalResults = data.total || 0;
          renderSuggestions(data.suggestions, box, data, state);
        } else {
          renderNoResults(box);
        }
      })
      .catch(err => {
        if (err.name !== 'AbortError') {
          console.error(err);
        }
      });
    }

    // Render suggestions
    function renderSuggestions(suggestions, box, data, state) {
      box.innerHTML = '';

      if (!suggestions.length) {
        renderNoResults(box);
        return;
      }

      suggestions.forEach(item => {
        const div = document.createElement('div');
        div.style.cssText = `
          display: flex;
          align-items: center;
          padding: 10px 12px;
          cursor: pointer;
          border-bottom: 1px solid #f0f0f0;
          transition: background-color 0.15s;
        `;

        const img = document.createElement('img');
        img.src = item.image || '/assets/images/laptop.png';
        img.style.cssText = `
          width: 40px;
          height: 40px;
          object-fit: cover;
          border-radius: 4px;
          margin-right: 10px;
          flex-shrink: 0;
          border: 1px solid #eee;
        `;
        img.onerror = () => img.src = '/assets/images/laptop.png';

        const info = document.createElement('div');
        info.style.cssText = 'flex: 1; min-width: 0;';

        const name = document.createElement('div');
        name.textContent = item.name;
        name.style.cssText = `
          font-weight: 500;
          color: #333;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          margin-bottom: 2px;
          font-size: 0.95rem;
        `;

        const price = document.createElement('div');
        price.textContent = '$' + parseFloat(item.price).toFixed(2);
        price.style.cssText = `
          color: #0d6efd;
          font-size: 0.85rem;
          font-weight: 600;
        `;

        info.appendChild(name);
        info.appendChild(price);
        div.appendChild(img);
        div.appendChild(info);

        div.addEventListener('mouseenter', () => div.style.backgroundColor = '#f8f9fa');
        div.addEventListener('mouseleave', () => div.style.backgroundColor = 'transparent');

        div.addEventListener('click', () => {
          window.location.href = '/product/' + encodeURIComponent(item.slug);
        });

        box.appendChild(div);
      });

      // ONLY show "Show More" if:
      // 1. There are more results (hasMore = true)
      // 2. AND we're showing exactly 4 items (RESULTS_PER_PAGE)
      // This means if we have ≤4 total results, no "Show More" button
      if (data.hasMore && suggestions.length === RESULTS_PER_PAGE) {
        const showMoreDiv = document.createElement('div');
        showMoreDiv.style.cssText = `
          padding: 12px;
          text-align: center;
          border-top: 1px solid #f0f0f0;
          background: #f9f9f9;
          cursor: pointer;
          color: #0d6efd;
          font-weight: 600;
          font-size: 0.9rem;
          transition: background-color 0.15s;
        `;
        showMoreDiv.textContent = 'Show More';

        showMoreDiv.addEventListener('mouseenter', () => {
          showMoreDiv.style.backgroundColor = '#f0f0f0';
        });
        showMoreDiv.addEventListener('mouseleave', () => {
          showMoreDiv.style.backgroundColor = '#f9f9f9';
        });

        showMoreDiv.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          const newOffset = state.offset + RESULTS_PER_PAGE;
          state.offset = newOffset;
          fetchSuggestions(data.query, newOffset, box, state);
        });

        box.appendChild(showMoreDiv);
      }

      box.style.display = 'block';
    }

    // No results message
    function renderNoResults(box) {
      box.innerHTML = '';
      const div = document.createElement('div');
      div.textContent = 'No results found';
      div.style.cssText = `
        padding: 15px;
        text-align: center;
        color: #999;
        font-style: italic;
        font-size: 0.9rem;
      `;
      box.appendChild(div);
      box.style.display = 'block';
    }

    // Form submit
    const formElement = inputElement.closest('form');
    if (formElement) {
      formElement.addEventListener('submit', function(e) {
        e.preventDefault();
        const q = inputElement.value.trim();
        if (q) {
          window.location.href = '/shop?search=' + encodeURIComponent(q);
        }
      });
    }

    // Click outside
    document.addEventListener('click', function(e) {
      if (!suggestionBox.contains(e.target) && e.target !== inputElement && e.target !== clearBtn) {
        suggestionBox.style.display = 'none';
      }
    });

    // Escape key
    inputElement.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        suggestionBox.style.display = 'none';
      }
    });
  }

  // Utility function to add a simple clear button to any input
  function addClearButton(inputElement) {
    if (!inputElement) return;
    
    const form = inputElement.closest('form');
    if (!form) return;

    // Check if this input already has been initialized
    if (inputElement.dataset.searchInitialized === 'true') {
      return;
    }
    inputElement.dataset.searchInitialized = 'true';

    // Prevent double initialization of the clear button in the form
    let clearBtn = form.querySelector('button[data-search-clear-btn="true"]');
    
    if (!clearBtn) {
      clearBtn = form.querySelector('button.search-clear-btn, button.btn-clear-search, button.btn-clear');
    }

    if (!clearBtn) {
      clearBtn = document.createElement('button');
      clearBtn.type = 'button';
      clearBtn.setAttribute('data-search-clear-btn', 'true');
      clearBtn.className = 'search-clear-btn';
      clearBtn.innerHTML = '&times;';
      clearBtn.setAttribute('aria-label', 'Clear');
      clearBtn.style.cssText = `
        position: absolute;
        right: 60px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        font-size: 1.5rem;
        line-height: 1;
        cursor: pointer;
        display: none;
        padding: 0.25rem 0.5rem;
        z-index: 10;
        color: #999;
        transition: color 0.2s;
      `;

      clearBtn.addEventListener('mouseenter', () => clearBtn.style.color = '#000');
      clearBtn.addEventListener('mouseleave', () => clearBtn.style.color = '#999');

      form.style.position = 'relative';
      form.appendChild(clearBtn);
    }

    clearBtn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      inputElement.value = '';
      clearBtn.style.display = 'none';
      inputElement.focus();
      inputElement.dispatchEvent(new Event('input', { bubbles: true }));
    });

    inputElement.addEventListener('input', function() {
      clearBtn.style.display = inputElement.value.trim().length > 0 ? 'block' : 'none';
    });

    form.style.position = 'relative';
    form.appendChild(clearBtn);
  }

  // Export functions to global scope for reuse
  window.initSearch = initSearch;
  window.addClearButton = addClearButton;

  // Initialize both search inputs
  initSearch(document.querySelector('#hero-search'));
  initSearch(document.querySelector('#shop-search'));
});
