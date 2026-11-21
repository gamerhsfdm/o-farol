    </main>
  </div> <!-- /.main -->

  <div class="toasts" id="toasts" aria-live="polite"></div>

  <script>
  (function () {
    'use strict';
    var doc = document, body = doc.body;
    function $(s, r) { return (r || doc).querySelector(s); }
    function $$(s, r) { return Array.prototype.slice.call((r || doc).querySelectorAll(s)); }

    /* ---- Menu lateral (drawer em telas menores) ---- */
    function setMenu(open) { body.classList.toggle('menu-open', open); }
    doc.addEventListener('click', function (e) {
      if (e.target.closest('[data-menu-open]')) { setMenu(true); return; }
      if (e.target.closest('[data-menu-close]')) { setMenu(false); return; }
      if (e.target.closest('.sidebar .nav-item') && window.innerWidth < 1024) { setMenu(false); }
    });

    /* ---- Dropdowns ---- */
    function closeDropdowns(except) {
      $$('[data-dropdown].is-open').forEach(function (d) {
        if (d !== except) {
          d.classList.remove('is-open');
          var t = $('[data-dropdown-toggle]', d);
          if (t) { t.setAttribute('aria-expanded', 'false'); }
        }
      });
    }
    doc.addEventListener('click', function (e) {
      var toggle = e.target.closest('[data-dropdown-toggle]');
      if (toggle) {
        var dd = toggle.closest('[data-dropdown]');
        var willOpen = !dd.classList.contains('is-open');
        closeDropdowns(dd);
        dd.classList.toggle('is-open', willOpen);
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        return;
      }
      if (!e.target.closest('[data-dropdown]')) { closeDropdowns(null); }
    });

    /* ---- Avisos dispensáveis ---- */
    doc.addEventListener('click', function (e) {
      var b = e.target.closest('[data-dismiss]');
      if (b) {
        var a = b.closest('.alert');
        if (a) { a.remove(); }
      }
    });

    /* ---- Modais ---- */
    var lastFocus = null;
    function openModal(id) {
      var m = doc.getElementById(id);
      if (!m) { return; }
      lastFocus = doc.activeElement;
      m.classList.add('is-open');
      body.classList.add('is-locked');
      var f = $('input:not([type=hidden]):not([type=file]), select, textarea, button[data-modal-primary]', m);
      if (f) { setTimeout(function () { f.focus(); }, 30); }
    }
    function closeModal(m) {
      if (!m) { return; }
      m.classList.remove('is-open');
      if (!$('.modal.is-open')) { body.classList.remove('is-locked'); }
      if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
      m.dispatchEvent(new CustomEvent('modal:close'));
    }
    window.farolModal = { open: openModal, close: function (id) { closeModal(doc.getElementById(id)); } };
    doc.addEventListener('click', function (e) {
      var o = e.target.closest('[data-modal-open]');
      if (o) { e.preventDefault(); openModal(o.getAttribute('data-modal-open')); return; }
      if (e.target.closest('[data-modal-close]')) { closeModal(e.target.closest('.modal')); return; }
      if (e.target.classList && e.target.classList.contains('modal')) { closeModal(e.target); }
    });

    /* ---- Toasts ---- */
    window.farolToast = function (msg) {
      var box = doc.getElementById('toasts');
      var t = doc.createElement('div');
      t.className = 'toast';
      t.setAttribute('role', 'status');
      t.textContent = msg;
      box.appendChild(t);
      setTimeout(function () { t.remove(); }, 3500);
    };

    /* ---- Copiar para a área de transferência ---- */
    doc.addEventListener('click', function (e) {
      var b = e.target.closest('[data-copy]');
      if (!b) { return; }
      var text = b.getAttribute('data-copy');
      var done = function () { window.farolToast('Link copiado para a área de transferência'); };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done, function () { window.prompt('Copie o link:', text); });
      } else { window.prompt('Copie o link:', text); }
    });

    /* ---- Diálogo de confirmação (links e formulários com data-confirm) ---- */
    var confirmEl = null, confirmAction = null;
    function buildConfirm() {
      confirmEl = doc.createElement('div');
      confirmEl.className = 'modal';
      confirmEl.id = 'modal-confirmacao';
      confirmEl.setAttribute('role', 'alertdialog');
      confirmEl.setAttribute('aria-modal', 'true');
      confirmEl.innerHTML =
        '<div class="modal__panel modal__panel--sm">' +
          '<div class="modal__body" style="padding-top:24px">' +
            '<h3 class="modal__title" data-c-title></h3>' +
            '<p class="muted" data-c-text></p>' +
          '</div>' +
          '<div class="modal__foot">' +
            '<button type="button" class="btn" data-modal-close>Cancelar</button>' +
            '<button type="button" class="btn btn--danger" data-c-ok data-modal-primary></button>' +
          '</div>' +
        '</div>';
      body.appendChild(confirmEl);
      $('[data-c-ok]', confirmEl).addEventListener('click', function () {
        var fn = confirmAction; confirmAction = null;
        closeModal(confirmEl);
        if (fn) { fn(); }
      });
    }
    function askConfirm(el, action) {
      if (!confirmEl) { buildConfirm(); }
      $('[data-c-title]', confirmEl).textContent = el.getAttribute('data-confirm-title') || 'Confirmar ação';
      $('[data-c-text]', confirmEl).textContent = el.getAttribute('data-confirm');
      var ok = $('[data-c-ok]', confirmEl);
      ok.textContent = el.getAttribute('data-confirm-ok') || 'Confirmar';
      ok.className = 'btn ' + (el.getAttribute('data-confirm-tone') === 'primary' ? 'btn--primary' : 'btn--danger');
      confirmAction = action;
      openModal('modal-confirmacao');
    }
    doc.addEventListener('click', function (e) {
      var a = e.target.closest('a[data-confirm]');
      if (!a) { return; }
      e.preventDefault();
      askConfirm(a, function () { window.location.href = a.href; });
    });
    doc.addEventListener('submit', function (e) {
      var f = e.target;
      if (f.hasAttribute('data-confirm') && !f.__confirmed) {
        e.preventDefault();
        e.stopImmediatePropagation();
        askConfirm(f, function () { f.__confirmed = true; if (f.requestSubmit) { f.requestSubmit(); } else { f.submit(); } f.__confirmed = false; });
      }
    }, true);

    /* ---- Estado de carregamento nos envios + proteção contra clique duplo ---- */
    doc.addEventListener('submit', function (e) {
      var f = e.target;
      if (e.defaultPrevented || f.getAttribute('data-no-loading') !== null) { return; }
      if (f.__sending) { e.preventDefault(); return; }
      f.__sending = true;
      var btn = e.submitter || $('button[type=submit]', f);
      setTimeout(function () { if (btn) { btn.classList.add('is-loading'); } }, 0);
      setTimeout(function () { f.__sending = false; if (btn) { btn.classList.remove('is-loading'); } }, 15000);
    });
    window.addEventListener('pageshow', function (e) {
      if (e.persisted) { $$('.is-loading').forEach(function (b) { b.classList.remove('is-loading'); }); $$('form').forEach(function (f) { f.__sending = false; }); }
    });

    /* ---- Atalhos: "/" ou Ctrl/⌘+K focam a busca; Esc fecha camadas ---- */
    doc.addEventListener('keydown', function (e) {
      var tag = (e.target.tagName || '').toLowerCase();
      var typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable;
      if (((e.key === '/' && !typing) || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k'))) {
        var s = $('[data-global-search]');
        if (s && s.offsetParent !== null) { e.preventDefault(); s.focus(); s.select(); }
      }
      if (e.key === 'Escape') {
        setMenu(false); closeDropdowns(null);
        var open = $$('.modal.is-open'); if (open.length) { closeModal(open[open.length - 1]); }
      }
    });

    /* ---- Campos que crescem com o texto ---- */
    function grow(t) { t.style.height = 'auto'; t.style.height = t.scrollHeight + 'px'; }
    $$('textarea[data-autogrow]').forEach(function (t) { grow(t); t.addEventListener('input', function () { grow(t); }); });
    window.addEventListener('load', function () { $$('textarea[data-autogrow]').forEach(grow); });

    /* ---- Aviso de alterações não salvas ---- */
    $$('form[data-dirty-guard]').forEach(function (f) {
      var dirty = false, flag = $('[data-dirty-flag]');
      f.addEventListener('input', function () {
        if (!dirty) { dirty = true; if (flag) { flag.textContent = 'Alterações não salvas'; flag.className = 'badge badge--warn'; } }
      });
      f.addEventListener('submit', function () { dirty = false; });
      window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    });

    /* ---- Pré-visualização do site em moldura reduzida ---- */
    function fitDevices() {
      $$('[data-device]').forEach(function (v) {
        var fr = $('iframe', v);
        if (fr) { fr.style.transform = 'scale(' + (v.clientWidth / 1440) + ')'; }
      });
    }
    window.addEventListener('resize', fitDevices);
    window.addEventListener('load', fitDevices);
    fitDevices();
  })();
  </script>

</body>
</html>
