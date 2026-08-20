const revealCards = document.querySelectorAll('.reveal-card');

if ('IntersectionObserver' in window) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px' });

  revealCards.forEach((card, index) => {
    card.style.transitionDelay = `${Math.min(index % 3, 2) * 90}ms`;
    observer.observe(card);
  });
} else {
  revealCards.forEach((card) => card.classList.add('is-visible'));
}
