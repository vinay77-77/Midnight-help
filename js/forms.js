'use strict';
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('register-form')) initRegister();
  if (document.getElementById('signin-form'))   initSignin();
});
function getVal(id) { return (document.getElementById(id)?.value || '').trim(); }
function showErr(id, msg) { const e = document.getElementById('err-' + id); if (e) { e.textContent = msg; e.classList.add('visible'); } document.getElementById(id)?.style && (document.getElementById(id).style.borderColor = 'var(--sos)'); }
function clearErr(id) { const e = document.getElementById('err-' + id); if (e) e.classList.remove('visible'); if (document.getElementById(id)) document.getElementById(id).style.borderColor = ''; }
function isEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }
function isPhone(v) { return v.replace(/[\s\-()]/g,'').length >= 7; }
function strength(pw) { let s = 0; if (pw.length>=8) s++; if (pw.length>=12) s++; if (/[A-Z]/.test(pw)) s++; if (/[0-9]/.test(pw)) s++; if (/[^A-Za-z0-9]/.test(pw)) s++; return s; }
function basePath() {
  // Works for both /Midnight-help/... and /midnight-help/...
  const seg = (window.location.pathname || '/').split('/').filter(Boolean)[0] || '';
  return '/' + seg + '/';
}

function initRegister() {
  let step = 1;
  const passIn = document.getElementById('password');
  if (passIn) {
    passIn.addEventListener('input', () => {
      const s = strength(passIn.value);
      const segs = document.querySelectorAll('#strength-bar .strength-bar-segment');
      const cols = ['','#e74c3c','#e67e22','#f1c40f','#2ecc71','#10b981'];
      const labs = ['','Too weak','Weak','Fair','Good','Strong'];
      segs.forEach((seg,i) => seg.style.background = i < s ? cols[s] : 'var(--border)');
      const lbl = document.getElementById('strength-label');
      if (lbl) { lbl.textContent = labs[s] || ''; lbl.style.color = cols[s]; }
    });
  }
  document.getElementById('step-next-btn')?.addEventListener('click', () => {
    let ok = true;
    ['fname','lname'].forEach(id => { clearErr(id); if (!getVal(id) || getVal(id).length < 2) { showErr(id, id === 'fname' ? 'First name required' : 'Last name required'); ok = false; } });
    clearErr('email'); if (!isEmail(getVal('email'))) { showErr('email','Valid email required'); ok = false; }
    clearErr('phone'); if (!isPhone(getVal('phone'))) { showErr('phone','Valid phone required'); ok = false; }
    clearErr('dob'); if (!getVal('dob')) { showErr('dob','Date of birth required'); ok = false; }
    if (!ok) return;
    showStep(2);
  });
  document.getElementById('step-back-btn')?.addEventListener('click', () => showStep(1));
  document.getElementById('register-form')?.addEventListener('submit', e => {
    e.preventDefault();
    let ok = true;
    clearErr('password'); if (getVal('password').length < 8) { showErr('password','Min. 8 characters'); ok = false; }
    clearErr('cpassword'); if (getVal('password') !== getVal('cpassword')) { showErr('cpassword','Passwords do not match'); ok = false; }
    if (!document.getElementById('terms')?.checked) { showToast('Please accept the Terms of Service.'); ok = false; }
    if (!ok) return;
    const btn = document.getElementById('register-submit-btn');
    btn.disabled = true; btn.textContent = 'Creating account…';
    fetch(basePath() + 'api/register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        firstName: getVal('fname'),
        lastName: getVal('lname'),
        email: getVal('email'),
        phone: getVal('phone'),
        dob: getVal('dob'),
        password: getVal('password'),
      }),
    })
      .then(r => r.json().catch(() => ({})).then(j => ({ status: r.status, json: j })))
      .then(({ status, json }) => {
        if (status >= 200 && status < 300 && json.ok) {
          document.getElementById('register-form').style.display = 'none';
          document.querySelector('.form-steps-indicator').style.display = 'none';
          document.getElementById('register-success').classList.add('visible');
          return;
        }
        showToast(json.error || 'Registration failed');
        btn.disabled = false; btn.textContent = '✅ Create Account';
      })
      .catch(() => {
        showToast('Backend not reachable. Run via XAMPP (http://localhost/...)');
        btn.disabled = false; btn.textContent = '✅ Create Account';
      });
  });
  function showStep(n) {
    step = n;
    document.querySelectorAll('.form-step-panel').forEach(p => p.classList.toggle('active', parseInt(p.dataset.step) === n));
    document.querySelectorAll('.form-step-dot').forEach(d => { const s = parseInt(d.dataset.step); d.classList.toggle('active', s === n); d.classList.toggle('done', s < n); });
    document.getElementById('step-back-btn').style.display = n > 1 ? 'flex' : 'none';
    document.getElementById('step-next-btn').style.display = n < 2 ? 'flex' : 'none';
    document.getElementById('register-submit-btn').style.display = n === 2 ? 'flex' : 'none';
  }
}

function initSignin() {
  document.getElementById('signin-form').addEventListener('submit', e => {
    e.preventDefault();
    let ok = true;
    clearErr('si-email'); if (!isEmail(getVal('si-email'))) { showErr('si-email','Valid email required'); ok = false; }
    clearErr('si-password'); if (!getVal('si-password')) { showErr('si-password','Password required'); ok = false; }
    if (!ok) return;
    const btn = document.getElementById('signin-submit-btn');
    btn.disabled = true; btn.textContent = 'Signing in…';
    const payload = { email: getVal('si-email'), password: getVal('si-password') };

    // Auto-detect: try admin first, then fall back to user.
    fetch(basePath() + 'api/admin_login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(r => r.json().catch(() => ({})).then(j => ({ status: r.status, json: j })))
      .then(({ status, json }) => {
        if (status >= 200 && status < 300 && json.ok) {
          window.location.href = basePath() + 'admin-dashboard.html';
          return null;
        }
        // If admin login fails, try user login.
        return fetch(basePath() + 'api/login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        })
          .then(r => r.json().catch(() => ({})).then(j => ({ status: r.status, json: j })));
      })
      .then((userResult) => {
        if (!userResult) return; // already redirected as admin
        const { status, json } = userResult;
        if (status >= 200 && status < 300 && json.ok) {
          window.location.href = basePath() + 'user-dashboard.html';
          return;
        }
        showToast(json.error || 'Invalid email or password');
        btn.disabled = false; btn.textContent = '🔑 Sign In';
      })
      .catch(() => {
        showToast('Backend not reachable. Run via XAMPP (http://localhost/...)');
        btn.disabled = false; btn.textContent = '🔑 Sign In';
      });
  });
}
