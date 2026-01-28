document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.querySelector('[data-admin-sidebar]');
  const toggler = document.querySelector('[data-admin-toggle]');
  const overlay = document.querySelector('[data-admin-overlay]');
  const links = sidebar?.querySelectorAll('a') || [];
  const mql = window.matchMedia('(max-width: 991.98px)');

  if (!sidebar || !toggler) return;

  const openNav = () => {
    sidebar.classList.add('is-open');
    overlay?.classList.add('is-active');
    document.body.classList.add('admin-nav-open');
    document.addEventListener('keydown', handleKeydown);
  };

  const closeNav = () => {
    sidebar.classList.remove('is-open');
    overlay?.classList.remove('is-active');
    document.body.classList.remove('admin-nav-open');
    document.removeEventListener('keydown', handleKeydown);
  };

  const toggleNav = () => {
    if (sidebar.classList.contains('is-open')) closeNav();
    else openNav();
  };

  const handleKeydown = (event) => {
    if (event.key === 'Escape') closeNav();
  };

  toggler.addEventListener('click', toggleNav);
  overlay?.addEventListener('click', closeNav);

  links.forEach(link => {
    link.addEventListener('click', () => {
      if (mql.matches) closeNav();
    });
  });

  mql.addEventListener('change', () => {
    if (!mql.matches) closeNav();
  });

  // Admin login uses standard form POST to allow server-side flash + redirect.

  const catImage = document.getElementById('catImage');
  if (catImage) {
    const current = catImage.dataset.currentIcon || '';
    const normalizeIcon = (path) => {
      if (!path) return '';
      if (/^https?:\/\//i.test(path)) return path;
      if (path.startsWith('/')) return path;
      if (path.startsWith('uploads/')) return '/' + path;
      return '/uploads/' + path;
    };
    const iconUrl = normalizeIcon(current);
    if (iconUrl) {
      const form = catImage.closest('form');
      const ensureRemoveHidden = () => {
        if (!form) return null;
        let input = form.querySelector('input[type="hidden"][name="remove_icon"]');
        if (!input) {
          input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'remove_icon';
          input.value = '';
          form.appendChild(input);
        }
        return input;
      };
      const removeHidden = ensureRemoveHidden();

      const wrapper = document.createElement('div');
      wrapper.className = 'mt-2';
      wrapper.style.width = '72px';
      wrapper.style.height = '72px';
      wrapper.style.position = 'relative';
      wrapper.style.border = '1px solid #e3e7ed';
      wrapper.style.borderRadius = '8px';
      wrapper.style.overflow = 'hidden';
      wrapper.style.cursor = 'pointer';

      const img = document.createElement('img');
      img.src = iconUrl;
      img.alt = 'Current category icon';
      img.style.width = '100%';
      img.style.height = '100%';
      img.style.objectFit = 'contain';

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

      wrapper.addEventListener('click', (e) => {
        if (e.target === removeBtn) return;
        catImage.click();
      });

      removeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (removeHidden) removeHidden.value = '1';
        wrapper.remove();
      });

      catImage.addEventListener('change', () => {
        if (catImage.files && catImage.files.length) {
          if (removeHidden) removeHidden.value = '';
        }
      });

      wrapper.appendChild(img);
      wrapper.appendChild(removeBtn);
      catImage.insertAdjacentElement('afterend', wrapper);
    }
  }
});

// Active link highlighting is handled server-side in the PHP sidebar partial.
// Removed client-side duplication to keep a single source of truth.
