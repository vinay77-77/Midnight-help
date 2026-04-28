'use strict';
document.addEventListener('DOMContentLoaded', () => {
  initNav(); initScrollReveal(); initFontSizeControl();
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
      const inp = document.getElementById(btn.dataset.target);
      if (!inp) return;
      const hide = inp.type === 'password';
      inp.type = hide ? 'text' : 'password';
      btn.textContent = hide ? '🙈' : '👁️';
    });
  });
});

// Works for both /Midnight-help/... and /midnight-help/...
function basePath() {
  const seg = (window.location.pathname || '/').split('/').filter(Boolean)[0] || '';
  return '/' + seg + '/';
}

function initNav() {
  const ham = document.querySelector('.nav-hamburger');
  const drawer = document.querySelector('.nav-drawer');
  if (ham && drawer) {
    ham.addEventListener('click', () => {
      const open = drawer.classList.toggle('open');
      ham.classList.toggle('open', open);
      ham.setAttribute('aria-expanded', String(open));
      document.body.style.overflow = open ? 'hidden' : '';
    });
    drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
      drawer.classList.remove('open'); ham.classList.remove('open');
      ham.setAttribute('aria-expanded', 'false'); document.body.style.overflow = '';
    }));
    document.addEventListener('click', e => {
      if (!ham.contains(e.target) && !drawer.contains(e.target)) {
        drawer.classList.remove('open'); ham.classList.remove('open'); document.body.style.overflow = '';
      }
    });
  }
}

function initScrollReveal() {
  const els = document.querySelectorAll('.reveal');
  if (!els.length) return;
  if ('IntersectionObserver' in window) {
    const obs = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
    }, { threshold: 0.1 });
    els.forEach(el => obs.observe(el));
  } else {
    els.forEach(el => el.classList.add('visible'));
  }
}

function showToast(msg, dur = 3200) {
  let t = document.getElementById('site-toast');
  if (!t) { t = document.createElement('div'); t.id = 'site-toast'; t.className = 'toast'; document.body.appendChild(t); }
  t.textContent = msg; t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), dur);
}

function initFontSizeControl() {
  const BASE = 18, MIN = 15, MAX = 24, KEY = 'mh-font-size';
  const saved = parseInt(localStorage.getItem(KEY), 10);
  if (!isNaN(saved)) document.documentElement.style.fontSize = saved + 'px';
  document.querySelectorAll('.font-size-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const cur = parseInt(document.documentElement.style.fontSize, 10) || BASE;
      let next = cur;
      if (btn.dataset.action === 'increase') next = Math.min(cur + 2, MAX);
      if (btn.dataset.action === 'decrease') next = Math.max(cur - 2, MIN);
      if (btn.dataset.action === 'reset')    next = BASE;
      document.documentElement.style.fontSize = next + 'px';
      localStorage.setItem(KEY, next);
      showToast('Text size: ' + next + 'px');
    });
  });
}

function openSOSModal() {
  const o = document.getElementById('sos-modal-overlay');
  if (!o) return;
  o.classList.add('active'); o.removeAttribute('hidden');
  document.body.style.overflow = 'hidden';
  const c = document.getElementById('sos-confirm-box');
  const b = document.getElementById('sos-send-btn');
  if (c) c.style.display = 'none';
  if (b) { b.disabled = false; b.textContent = '🚨 Send Emergency Alert'; b.style.background = ''; b.style.borderColor = ''; }
  setTimeout(() => document.getElementById('sos-detail')?.focus(), 80);
}
function closeSOSModal(e) {
  if (e && e.target !== document.getElementById('sos-modal-overlay') && !e.target.closest?.('.modal-close-btn')) return;
  document.getElementById('sos-modal-overlay')?.classList.remove('active');
  document.getElementById('sos-modal-overlay')?.setAttribute('hidden','');
  document.body.style.overflow = '';
}
function sendSOS() {
  const detail = document.getElementById('sos-detail')?.value.trim();
  if (!detail) { showToast('Please describe your emergency.'); return; }
  const btn = document.getElementById('sos-send-btn');
  btn.disabled = true; btn.textContent = 'Sending…';
  setTimeout(() => {
    document.getElementById('sos-confirm-box').style.display = 'block';
    btn.textContent = '✓ Alert Sent'; btn.style.background = '#1a2235'; btn.style.borderColor = 'var(--border-lit)';
    setTimeout(() => { closeSOSModal(); }, 4500);
  }, 1300);
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeSOSModal(); }
});
document.addEventListener('click', e => {
  if (e.target.closest('a[href^="#"]')) {
    const t = document.querySelector(e.target.closest('a').getAttribute('href'));
    if (t) { e.preventDefault(); const h = document.querySelector('.site-nav')?.offsetHeight || 72; window.scrollTo({ top: t.getBoundingClientRect().top + window.scrollY - h - 10, behavior: 'smooth' }); }
  }
});
window.openSOSModal = openSOSModal;
window.closeSOSModal = closeSOSModal;
window.sendSOS = sendSOS;
window.showToast = showToast;
window.basePath = basePath;
