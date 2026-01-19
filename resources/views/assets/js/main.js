document.addEventListener('DOMContentLoaded', () => {
  initCompareCards();
  initStickyHeader();
});

function initCompareCards() {
  const cards = document.querySelectorAll('[data-card]');
  if (!cards.length) return;

  cards.forEach(card => {
    const toggleBtn = card.querySelector('[data-toggle]');
    const details = card.querySelector('.compare-card__details');
    if (!toggleBtn || !details) return;

    toggleBtn.addEventListener('click', event => {
      event.stopPropagation();

      cards.forEach(otherCard => {
        if (otherCard !== card) {
          otherCard.classList.remove('is-expanded');
          const otherToggle = otherCard.querySelector('[data-toggle]');
          const otherDetails = otherCard.querySelector('.compare-card__details');
          if (otherToggle) otherToggle.setAttribute('aria-expanded', 'false');
          if (otherDetails) otherDetails.setAttribute('aria-hidden', 'true');
        }
      });

      const isExpanded = card.classList.toggle('is-expanded');
      toggleBtn.setAttribute('aria-expanded', isExpanded);
      details.setAttribute('aria-hidden', (!isExpanded).toString());
    });
  });
}

function initStickyHeader() {
  const header = document.querySelector('.site-header');
  if (!header) return;

  const toggleShadow = () => {
    if (window.scrollY > 10) header.classList.add('is-scrolled');
    else header.classList.remove('is-scrolled');
  };

  toggleShadow();
  window.addEventListener('scroll', toggleShadow, { passive: true });
}
