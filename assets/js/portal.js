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

  // 3. Menu móvel (atributo hidden + aria-expanded) e "Mais editorias"
  const btnMenuToggle = document.getElementById('btn-mobile-menu-toggle');
  const mobileMenu = document.getElementById('mobile-menu');
  const setMenu = (open) => {
    if (!btnMenuToggle || !mobileMenu) return;
    mobileMenu.hidden = !open;
    btnMenuToggle.setAttribute('aria-expanded', String(open));
    btnMenuToggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
  };
  if (btnMenuToggle && mobileMenu) {
    btnMenuToggle.addEventListener('click', () => setMenu(mobileMenu.hidden));
    window.matchMedia('(min-width: 1024px)').addEventListener('change', (ev) => { if (ev.matches) setMenu(false); });
  }
  const btnMobileSearch = document.getElementById('btn-mobile-search');
  if (btnMobileSearch && mobileMenu) {
    btnMobileSearch.addEventListener('click', () => {
      setMenu(true);
      document.getElementById('drawer-busca')?.focus();
    });
  }
  const moreMenu = document.querySelector('.nav__more');
  document.addEventListener('click', (e) => {
    if (moreMenu && moreMenu.open && !moreMenu.contains(e.target)) moreMenu.open = false;
  });
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (moreMenu && moreMenu.open) { moreMenu.open = false; moreMenu.querySelector('summary').focus(); }
    if (mobileMenu && !mobileMenu.hidden) { setMenu(false); btnMenuToggle.focus(); }
  });

  // 3b. Fotos da capa: aparecem com um fade quando terminam de carregar (sem JS, já ficam visíveis)
  document.querySelectorAll('.m__img').forEach((img) => {
    const show = () => img.classList.add('is-loaded');
    if (img.complete) { show(); return; }
    img.addEventListener('load', show, { once: true });
    img.addEventListener('error', show, { once: true });
  });

  // 3c. Enquete "Voz do aluno": voto sem recarregar a página
  const pollForm = document.getElementById('form-enquete');
  if (pollForm) {
    const btn = document.getElementById('btn-votar');
    const feedback = document.getElementById('enquete-feedback');
    const options = document.getElementById('enquete-opcoes-container');
    const btnLabel = btn ? btn.textContent : '';

    const say = (text, ok) => {
      feedback.hidden = false;
      feedback.className = 'poll__feedback ' + (ok ? 'is-ok' : 'is-error');
      feedback.textContent = text;
    };

    const showResults = (results) => {
      options.replaceChildren();
      results.forEach((r) => {
        const pct = Math.max(0, Math.min(100, Number(r.percentual) || 0));
        const color = /^#[0-9a-f]{3,8}$/i.test(String(r.cor_destaque || '')) ? r.cor_destaque : '';
        const row = document.createElement('div');
        row.className = 'poll__result';
        const head = document.createElement('div');
        const name = document.createElement('span');
        name.textContent = r.opcao;
        const value = document.createElement('span');
        value.textContent = pct + '%';
        head.append(name, value);
        const bar = document.createElement('div');
        bar.className = 'poll__bar';
        const fill = document.createElement('span');
        fill.style.width = '0%';
        if (color) fill.style.background = color;
        bar.append(fill);
        row.append(head, bar);
        options.append(row);
        requestAnimationFrame(() => requestAnimationFrame(() => { fill.style.width = pct + '%'; }));
      });
    };

    pollForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const max = parseInt(pollForm.dataset.maxEscolhas || '1', 10);
      const boxes = pollForm.querySelectorAll('input[name="opcoes_ids[]"]');
      const checked = pollForm.querySelectorAll('input[name="opcoes_ids[]"]:checked');
      if (boxes.length && checked.length === 0) { say('Selecione ao menos uma opção para votar.', false); return; }
      if (checked.length > max) { say('Você pode selecionar no máximo ' + max + ' opções.', false); return; }

      btn.disabled = true;
      btn.textContent = 'Registrando voto...';
      try {
        const resp = await fetch(pollForm.action, { method: 'POST', body: new FormData(pollForm) });
        const data = await resp.json();
        if (data.sucesso) {
          say(data.mensagem || 'Voto registrado.', true);
          btn.textContent = 'Voto registrado';
          if (data.exibir_resultados && Array.isArray(data.resultados)) {
            showResults(data.resultados);
          } else if (!data.exibir_resultados) {
            const note = document.createElement('p');
            note.className = 'poll__desc';
            note.textContent = 'Os resultados serão divulgados após o encerramento da enquete.';
            options.replaceChildren(note);
          }
        } else {
          say(data.mensagem || 'Não foi possível registrar o voto.', false);
          btn.disabled = false;
          btn.textContent = btnLabel;
        }
      } catch (err) {
        say('Erro de comunicação com o servidor. Tente novamente.', false);
        btn.disabled = false;
        btn.textContent = btnLabel;
      }
    });
  }

  // 4. Modal / Lightbox de Imagens
  const imageModal = document.getElementById('image-lightbox');
  const modalImg = document.getElementById('lightbox-img');
  const modalCaption = document.getElementById('lightbox-caption');
  const modalClose = document.getElementById('lightbox-close');

  if (imageModal && modalImg) {
    document.querySelectorAll('.zoomable-image, .article-prose img, .editorial-body-figure img').forEach(img => {
      img.style.cursor = 'zoom-in';
      img.addEventListener('click', () => {
        modalImg.src = img.src;
        modalImg.alt = img.alt || '';
        if (modalCaption) {
          const figcaption = img.closest('figure')?.querySelector('figcaption');
          modalCaption.textContent = figcaption ? figcaption.textContent : (img.getAttribute('data-caption') || img.alt || '');
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
