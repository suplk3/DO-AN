// Attach a click handler to elements with class 'open-login-modal'
(function(){
  function openLoginModal(url){
    fetch(url)
      .then(function(res){ return res.text(); })
      .then(function(html){
        const overlay = document.createElement('div');
        overlay.className = 'login-overlay';
        overlay.innerHTML = html;

        // Prevent background scroll
        document.body.style.overflow = 'hidden';

        document.body.appendChild(overlay);

        // Close helpers
        function close(){
          document.body.removeChild(overlay);
          document.body.style.overflow = '';
          document.removeEventListener('keydown', onKey);
        }

        function onKey(e){ if (e.key === 'Escape') close(); }

        document.addEventListener('keydown', onKey);

        // Close when clicking the close button
        const closeBtn = overlay.querySelector('.login-close');
        if (closeBtn) closeBtn.addEventListener('click', close);

        // Close when clicking outside modal content
        overlay.addEventListener('click', function(e){
          const modal = overlay.querySelector('.login-modal');
          if (!modal) return;
          if (!modal.contains(e.target)) close();
        });

        // Prevent overlay click from closing when clicking inside the modal
        const modal = overlay.querySelector('.login-modal');
        if (modal) modal.addEventListener('click', function(e){ e.stopPropagation(); });

        // No top tabs: both Login and Register are visible by default.
        // The "Quên mật khẩu?" link will scroll to the Reset form below.
        overlay.querySelectorAll('.forgot-link').forEach(a=> a.addEventListener('click', function(e){
          e.preventDefault();
          const fv = overlay.querySelector('.auth-view[data-view="forgot"]');
          if (fv) fv.scrollIntoView({behavior:'smooth', block:'center'});
        }));

          // Handle switching between login and register from inside the modal
          overlay.querySelectorAll('.switch-to-register').forEach(btn => btn.addEventListener('click', function(){
            const loginV = overlay.querySelector('.auth-view[data-view="login"]');
            const regV = overlay.querySelector('.auth-view[data-view="register"]');
            if (loginV) loginV.style.display = 'none';
            if (regV) regV.style.display = '';
            // focus first input of register
            const input = regV && regV.querySelector('input'); if (input) input.focus();
          }));

          overlay.querySelectorAll('.switch-to-login').forEach(btn => btn.addEventListener('click', function(){
            const loginV = overlay.querySelector('.auth-view[data-view="login"]');
            const regV = overlay.querySelector('.auth-view[data-view="register"]');
            if (regV) regV.style.display = 'none';
            if (loginV) loginV.style.display = '';
            const input = loginV && loginV.querySelector('input'); if (input) input.focus();
          }));
        // Password show/hide toggle
        overlay.querySelectorAll('.pw-toggle').forEach(function(btn){
          btn.addEventListener('click', function(){
            const input = this.closest('.input-group').querySelector('input');
            if (!input) return;
            if (input.type === 'password'){
              input.type = 'text';
              this.textContent = '🙈';
            } else {
              input.type = 'password';
              this.textContent = '👁';
            }
          });
        });
        // Handle register form via AJAX so modal does not navigate away
        const registerForm = overlay.querySelector('.register-form');
        if (registerForm){
          registerForm.addEventListener('submit', function(ev){
            ev.preventDefault();
            const url = registerForm.getAttribute('action') || '/testdoan/auth/register.php';
            const fm = new FormData(registerForm);
            fetch(url, { method: 'POST', body: fm, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then(r=> r.json())
              .then(function(json){
                const msg = registerForm.querySelector('.register-msg') || document.createElement('p');
                msg.className = 'register-msg message';
                msg.textContent = json.message || 'Đã gửi.';
                if (!registerForm.querySelector('.register-msg')) registerForm.appendChild(msg);
                if (json.success){
                  // auto-switch back to login and optionally prefill email
                  const loginV = overlay.querySelector('.auth-view[data-view="login"]');
                  const regV = overlay.querySelector('.auth-view[data-view="register"]');
                  if (regV) regV.style.display = 'none';
                  if (loginV) { loginV.style.display = ''; const em = loginV.querySelector('input[type="email"]'); if (em) em.value = fm.get('email') || ''; }
                }
              }).catch(function(err){ console.error(err); });
          });
        }

        // Handle forgot-password via AJAX as well
        const forgotForm = overlay.querySelector('.forgot-form');
        if (forgotForm){
          forgotForm.addEventListener('submit', function(ev){
            ev.preventDefault();
            const url = forgotForm.getAttribute('action') || '/testdoan/auth/forgot_password.php';
            const fm = new FormData(forgotForm);
            fetch(url, { method: 'POST', body: fm, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then(r=> r.json())
              .then(function(json){
                const msg = overlay.querySelector('.forgot-msg') || document.createElement('p');
                msg.className = 'forgot-msg message';
                msg.textContent = json.message || '';
                if (!overlay.querySelector('.forgot-msg')) {
                  const fv = overlay.querySelector('.auth-view[data-view="forgot"]');
                  if (fv) fv.appendChild(msg);
                }
              }).catch(function(err){ console.error(err); });
          });
        }
      })
      .catch(function(err){ console.error('Lỗi tải modal đăng nhập:', err); });
  }

  document.addEventListener('click', function(e){
    const target = e.target.closest && e.target.closest('.open-login-modal');
    if (!target) return;
    e.preventDefault();
    // Resolve URL from href or default to /auth/login.php?modal=1
    let url = target.getAttribute('data-modal-url') || target.getAttribute('href') || '/auth/login.php?modal=1';
    if (url.indexOf('?') === -1) url += '?modal=1';
    else if (url.indexOf('modal=') === -1) url += '&modal=1';
    openLoginModal(url);
  });
})();
