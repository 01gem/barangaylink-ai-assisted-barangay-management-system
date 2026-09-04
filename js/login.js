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

// Init
document.addEventListener('DOMContentLoaded', () => {
  initTabs();
  initSwitchBtns();
  checkHash();
});
