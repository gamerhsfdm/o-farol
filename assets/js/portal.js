/**
 * O FAROL - Interações Editoriais Modernas
 * Features: Menu Mobile, Barra de Progresso, Voltar ao Topo, Lightbox de Imagens e Busca Rápida
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Barra de Progresso de Leitura
  const progressBar = document.getElementById('reading-progress');
  if (progressBar) {
    window.addEventListener('scroll', () => {
      const totalHeight = document.documentElement.scrollHeight - window.innerHeight;
      if (totalHeight > 0) {
        const progress = (window.scrollY / totalHeight) * 100;
        progressBar.style.width = `${Math.min(100, Math.max(0, progress))}%`;
      }
    }, { passive: true });
  }

  // 2. Botão Voltar ao Topo
  const btnTop = document.getElementById('btn-back-to-top');
  if (btnTop) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 400) {
        btnTop.classList.add('visible');
      } else {
        btnTop.classList.remove('visible');
      }
    }, { passive: true });

    btnTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // 3. Menu Mobile Toggle
  const btnMenuToggle = document.getElementById('btn-mobile-menu-toggle');
  const mobileMenu = document.getElementById('mobile-menu');
  if (btnMenuToggle && mobileMenu) {
    btnMenuToggle.addEventListener('click', () => {
      const isHidden = mobileMenu.classList.contains('hidden');
      if (isHidden) {
        mobileMenu.classList.remove('hidden');
        btnMenuToggle.setAttribute('aria-expanded', 'true');
      } else {
        mobileMenu.classList.add('hidden');
        btnMenuToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // 4. Modal / Lightbox de Imagens
  const imageModal = document.getElementById('image-lightbox');
  const modalImg = document.getElementById('lightbox-img');
  const modalCaption = document.getElementById('lightbox-caption');
  const modalClose = document.getElementById('lightbox-close');

  if (imageModal && modalImg) {
    document.querySelectorAll('.zoomable-image').forEach(img => {
      img.style.cursor = 'zoom-in';
      img.addEventListener('click', () => {
        modalImg.src = img.src;
        modalImg.alt = img.alt || '';
        if (modalCaption) {
          modalCaption.textContent = img.getAttribute('data-caption') || img.alt || '';
        }
        imageModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
      });
    });

    const closeModal = () => {
      imageModal.classList.add('hidden');
      document.body.style.overflow = '';
    };

    if (modalClose) modalClose.addEventListener('click', closeModal);
    imageModal.addEventListener('click', (e) => {
      if (e.target === imageModal) closeModal();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !imageModal.classList.contains('hidden')) {
        closeModal();
      }
    });
  }
});
