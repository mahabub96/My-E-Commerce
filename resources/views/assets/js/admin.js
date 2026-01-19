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
});

// Active link highlighting is handled server-side in the PHP sidebar partial.
// Removed client-side duplication to keep a single source of truth.
