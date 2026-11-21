(() => {
  const sidebar = document.getElementById('adminSidebar');
  const toggle = document.querySelector('[data-sidebar-toggle]');
  const closeBtn = document.querySelector('[data-sidebar-close]');
  const backdrop = document.querySelector('[data-sidebar-backdrop]');
  if (!sidebar || !toggle || !backdrop) return;

  const close = () => {
    sidebar.classList.remove('is-open');
    backdrop.classList.remove('is-visible');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('sidebar-open');
  };

  const open = () => {
    sidebar.classList.add('is-open');
    backdrop.classList.add('is-visible');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('sidebar-open');
  };

  toggle.addEventListener('click', () => (sidebar.classList.contains('is-open') ? close() : open()));
  if (closeBtn) closeBtn.addEventListener('click', close);
  backdrop.addEventListener('click', close);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') close();
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) close();
  });
})();