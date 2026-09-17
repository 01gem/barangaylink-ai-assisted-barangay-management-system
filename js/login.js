/* ─────────────────────────────────────────
   BARANGAYLINK — LOGIN.JS
───────────────────────────────────────── */

const TAB_FORMS = {
  'resident-login': 'residentLoginForm',
  'official-login': 'officialLoginForm',
};

function activateTab(target) {
  const targetForm = TAB_FORMS[target];
  document.querySelectorAll('.auth-tab').forEach(t => {
    t.classList.toggle('active', t.dataset.tab === target);
  });
  document.querySelectorAll('.auth-form-wrap').forEach(f => f.classList.remove('active'));
  if (targetForm) {
    document.getElementById(targetForm)?.classList.add('active');
  }
}

// Toggle between login/register tabs
function initTabs() {
  const tabs = document.querySelectorAll('.auth-tab');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      activateTab(tab.dataset.tab);
    });
  });
}

// Switch form via button links inside form
function initSwitchBtns() {
  document.querySelectorAll('.switch-form-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      activateTab(btn.dataset.to);
    });
  });
}

// Toggle password visibility
function togglePass(id, btn) {
  const input = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}

// Check hash for direct tab open
function checkHash() {
  if (window.location.hash === '#official') {
    activateTab('official-login');
  }
}

async function loginApiRequest(path, payload) {
  const response = await fetch(`../api/auth/${path}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  let data;
  try {
    data = await response.json();
  } catch (error) {
    throw new Error('The server returned an invalid response.');
  }
  if (!response.ok || !data.success) {
    throw new Error(data.message || 'The request could not be completed.');
  }
  return data;
}

function initPasswordReset() {
  const overlay = document.getElementById('resetOverlay');
  const requestForm = document.getElementById('resetRequestForm');
  const codeForm = document.getElementById('resetCodeForm');
  const passwordForm = document.getElementById('resetPasswordForm');
  const message = document.getElementById('resetMessage');
  const accountLabel = document.getElementById('resetAccountLabel');
  let accountType = '';
  let verifiedCode = '';

  const showMessage = (text, type) => {
    message.textContent = text;
    message.className = `reset-message show ${type}`;
  };
  const open = (type) => {
    accountType = type;
    accountLabel.textContent = `${type === 'resident' ? 'Resident' : 'Official'} password recovery`;
    requestForm.reset();
    codeForm.reset();
    passwordForm.reset();
    requestForm.style.display = 'flex';
    codeForm.style.display = 'none';
    passwordForm.style.display = 'none';
    verifiedCode = '';
    message.className = 'reset-message';
    overlay.classList.add('show');
    overlay.setAttribute('aria-hidden', 'false');
    document.getElementById('resetUsername').focus();
  };
  const close = () => {
    verifiedCode = '';
    requestForm.reset();
    codeForm.reset();
    passwordForm.reset();
    overlay.classList.remove('show');
    overlay.setAttribute('aria-hidden', 'true');
  };

  document.querySelectorAll('.forgot-link').forEach(link => {
    link.addEventListener('click', event => {
      event.preventDefault();
      open(link.dataset.accountType);
    });
  });
  document.getElementById('resetClose')?.addEventListener('click', close);
  requestForm?.addEventListener('submit', async event => {
    event.preventDefault();
    const button = document.getElementById('sendResetCodeBtn');
    button.disabled = true;
    button.textContent = 'Sending...';
    try {
      const username = document.getElementById('resetUsername').value.trim();
      const data = await loginApiRequest('request_otp.php', { account_type: accountType, username });
      showMessage(data.message, 'success');
      requestForm.style.display = 'none';
      codeForm.style.display = 'flex';
      passwordForm.style.display = 'none';
      document.getElementById('resetCode').focus();
    } catch (error) {
      showMessage(error.message, 'error');
    } finally {
      button.disabled = false;
      button.textContent = 'Send Code';
    }
  });
  codeForm?.addEventListener('submit', async event => {
    event.preventDefault();
    const button = document.getElementById('verifyResetCodeBtn');
    button.disabled = true;
    button.textContent = 'Verifying...';
    try {
      const code = document.getElementById('resetCode').value.trim();
      await loginApiRequest('verify_otp.php', {
        account_type: accountType,
        username: document.getElementById('resetUsername').value.trim(),
        code
      });
      verifiedCode = code;
      showMessage('Code verified. Set your new password.', 'success');
      codeForm.style.display = 'none';
      passwordForm.style.display = 'flex';
      document.getElementById('resetNewPassword').focus();
    } catch (error) {
      showMessage(error.message, 'error');
    } finally {
      button.disabled = false;
      button.textContent = 'Verify Code';
    }
  });
  document.getElementById('resendResetCodeBtn')?.addEventListener('click', async () => {
    const button = document.getElementById('resendResetCodeBtn');
    button.disabled = true;
    try {
      const data = await loginApiRequest('request_otp.php', {
        account_type: accountType,
        username: document.getElementById('resetUsername').value.trim()
      });
      showMessage(data.message, 'success');
    } catch (error) {
      showMessage(error.message, 'error');
    } finally {
      button.disabled = false;
    }
  });
  passwordForm?.addEventListener('submit', async event => {
    event.preventDefault();
    const newPassword = document.getElementById('resetNewPassword').value;
    const confirmPassword = document.getElementById('resetConfirmPassword').value;
    if (newPassword !== confirmPassword) {
      showMessage('Passwords do not match.', 'error');
      return;
    }
    const button = document.getElementById('resetPasswordBtn');
    button.disabled = true;
    button.textContent = 'Resetting...';
    try {
      const data = await loginApiRequest('reset_password.php', {
        account_type: accountType,
        username: document.getElementById('resetUsername').value.trim(),
        code: verifiedCode,
        new_password: newPassword
      });
      showMessage(data.message, 'success');
      passwordForm.style.display = 'none';
      requestForm.style.display = 'flex';
      requestForm.reset();
      codeForm.reset();
      verifiedCode = '';
    } catch (error) {
      showMessage(error.message, 'error');
    } finally {
      button.disabled = false;
      button.textContent = 'Reset Password';
    }
  });
}

// Init
document.addEventListener('DOMContentLoaded', () => {
  initTabs();
  initSwitchBtns();
  checkHash();
  initPasswordReset();
});
