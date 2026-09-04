/* ─────────────────────────────────────────
   BARANGAYLINK — RESIDENT.JS
───────────────────────────────────────── */

const API_BASE = '../api';
const SIDEBAR_STATE_KEY = 'barangalink.sidebarCollapsed.resident';
const ACTIVE_SECTION_KEY = 'resident_active_section';
let REQUESTS = [];
let COMPLAINTS = [];
let NOTIFICATIONS = [];

// Get all notifications
function getAllNotifications() {
  return NOTIFICATIONS;
}

// ── TAB SWITCHING ─────────────────────────
function switchTab(name) {
  document.querySelectorAll('.snav-item').forEach(b => {
    b.classList.toggle('active', b.dataset.tab === name);
  });
  document.querySelectorAll('.tab-panel').forEach(p => {
    p.classList.toggle('active', p.id === `tab-${name}`);
  });
  const titles = { dashboard:'Dashboard', requests:'Document Requests', complaints:'Complaints & Concerns', notifications:'Notifications', profile:'My Profile' };
  document.getElementById('pageTitle').textContent = titles[name] || name;
  try {
    sessionStorage.setItem(ACTIVE_SECTION_KEY, name);
  } catch (err) {
    /* ignore storage failures */
  }
}

function isValidSectionName(name) {
  if (!name) return false;
  return !!document.querySelector(`.snav-item[data-tab="${name}"]`) && !!document.getElementById(`tab-${name}`);
}

function restoreActiveSection() {
  let saved = '';
  try {
    saved = sessionStorage.getItem(ACTIVE_SECTION_KEY) || '';
  } catch (err) {
    saved = '';
  }
  if (isValidSectionName(saved)) {
    switchTab(saved);
  } else if (saved) {
    try {
      sessionStorage.removeItem(ACTIVE_SECTION_KEY);
    } catch (err) {
      /* ignore storage failures */
    }
  }
}

function initNav() {
  document.querySelectorAll('.snav-item[data-tab]').forEach(btn => {
    btn.addEventListener('click', () => switchTab(btn.dataset.tab));
  });
}

function applySidebarState(collapsed) {
  document.body.classList.toggle('sidebar-collapsed', collapsed);
  const toggle = document.getElementById('sidebarToggle');
  const icon = toggle?.querySelector('i');
  if (toggle) {
    toggle.setAttribute('aria-expanded', String(!collapsed));
    toggle.setAttribute('aria-label', collapsed ? 'Show sidebar' : 'Hide sidebar');
  }
  if (icon) {
    icon.className = collapsed ? 'fa-solid fa-angles-right' : 'fa-solid fa-angles-left';
  }
}

function initSidebarToggle() {
  const toggle = document.getElementById('sidebarToggle');
  if (!toggle) return;
  let collapsed = false;
  try {
    collapsed = localStorage.getItem(SIDEBAR_STATE_KEY) === '1';
  } catch (err) {
    collapsed = false;
  }
  applySidebarState(collapsed);
  toggle.addEventListener('click', () => {
    const next = !document.body.classList.contains('sidebar-collapsed');
    applySidebarState(next);
    try {
      localStorage.setItem(SIDEBAR_STATE_KEY, next ? '1' : '0');
    } catch (err) {
      /* ignore storage failures */
    }
  });
}

function initLogoutConfirmation() {
  document.querySelectorAll('.logout-item, .logout-btn').forEach(link => {
    link.addEventListener('click', (event) => {
      const ok = window.confirm('Do you want to log out?');
      if (!ok) {
        event.preventDefault();
      } else {
        try {
          sessionStorage.removeItem(ACTIVE_SECTION_KEY);
        } catch (err) {
          /* ignore storage failures */
        }
      }
    });
  });
}

// ── DASHBOARD STATS ───────────────────────
function renderDashStats() {
  const el = document.getElementById('dashStats');
  if (!el) return;
  const totalRequests = REQUESTS.length;
  const pendingPickup = REQUESTS.filter(r => r.status === 'ready').length;
  const activeComplaints = COMPLAINTS.filter(c => c.status !== 'resolved').length;
  const unreadNotifications = getAllNotifications().filter(n => n.unread).length;
  const stats = [
    { val: String(totalRequests), lbl: 'Total Requests', ico: 'fa-file-lines', bg: '#DBEAFE', color: '#1976D2' },
    { val: String(pendingPickup), lbl: 'Pending Pickup', ico: 'fa-bell', bg: '#FEF9C3', color: '#D97706' },
    { val: String(activeComplaints), lbl: 'Active Complaints', ico: 'fa-triangle-exclamation', bg: '#FFF7ED', color: '#EA580C' },
    { val: String(unreadNotifications), lbl: 'Unread Notifications', ico: 'fa-envelope', bg: '#DCFCE7', color: '#059669' },
  ];
  el.innerHTML = stats.map(s => `
    <div class="stat-card">
      <div class="sc-ico" style="background:${s.bg}; color:${s.color}">
        <i class="fa-solid ${s.ico}"></i>
      </div>
      <div class="sc-data">
        <div class="sc-val">${s.val}</div>
        <div class="sc-lbl">${s.lbl}</div>
      </div>
    </div>
  `).join('');
}

// ── RECENT ITEMS (DASHBOARD) ──────────────
function renderRecentRequests() {
  const el = document.getElementById('recentRequestsList');
  if (!el) return;
  el.innerHTML = REQUESTS.slice(0,3).map(r => `
    <div class="mini-row">
      <div class="mr-icon" style="background:#DBEAFE; color:#1976D2"><i class="fa-solid fa-file-lines"></i></div>
      <div class="mr-text">
        <div class="mr-title">${r.type}</div>
        <div class="mr-sub">${r.ref} · ${r.date}</div>
      </div>
      <span class="status-badge status-${r.status}">${r.status}</span>
    </div>
  `).join('');
}

function renderRecentComplaints() {
  const el = document.getElementById('recentComplaintsList');
  if (!el) return;
  el.innerHTML = COMPLAINTS.slice(0,2).map(c => `
    <div class="mini-row">
      <div class="mr-icon" style="background:#FFF7ED; color:#EA580C"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div class="mr-text">
        <div class="mr-title">${c.cat}</div>
        <div class="mr-sub">${c.ref} · ${c.date}</div>
      </div>
      <span class="status-badge status-${c.status}">${c.status}</span>
    </div>
  `).join('');
}

// ── REQUESTS TABLE ────────────────────────
function renderRequestsTable(data) {
  const tbody = document.getElementById('reqTableBody');
  if (!tbody) return;
  if (!data.length) {
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--text-3);padding:32px;">No requests found.</td></tr>`;
    return;
  }
  tbody.innerHTML = data.map(r => `
    <tr>
      <td><code style="font-size:12px;color:var(--blue-mid)">${r.ref}</code></td>
      <td style="font-weight:600;color:var(--text)">${r.type}</td>
      <td>${r.purpose}</td>
      <td>${r.date}</td>
      <td><span class="status-badge status-${r.status}">${r.status}</span></td>
      <td><button onclick="viewRequest('${r.ref}')" style="font-size:12px;padding:5px 12px;background:var(--surface);border:1.5px solid var(--border);border-radius:6px;cursor:pointer;font-family:'Jost',sans-serif;font-weight:600;color:var(--text-2);">View</button></td>
    </tr>
  `).join('');
}

function viewRequest(ref) {
  alert(`Request details: ${ref}\n\n[In full system: this opens a detail modal with status timeline, official notes, and pickup instructions.]`);
}

function initRequestsTable() {
  renderRequestsTable(REQUESTS);
  document.getElementById('reqSearch')?.addEventListener('input', filterRequests);
  document.getElementById('reqFilter')?.addEventListener('change', filterRequests);
}

function filterRequests() {
  const q = document.getElementById('reqSearch').value.toLowerCase();
  const status = document.getElementById('reqFilter').value;
  const filtered = REQUESTS.filter(r => {
    const matchQ = r.type.toLowerCase().includes(q) || r.ref.toLowerCase().includes(q) || r.purpose.toLowerCase().includes(q);
    const matchS = status === 'all' || r.status === status;
    return matchQ && matchS;
  });
  renderRequestsTable(filtered);
}

// ── REQUEST FORM TOGGLE ───────────────────
function initReqFormToggle() {
  const openBtn  = document.getElementById('newReqBtn');
  const closeBtn = document.getElementById('closeReqForm');
  const cancelBtn= document.getElementById('cancelReqForm');
  const form     = document.getElementById('newReqForm');
  const formEl   = document.getElementById('reqFormEl');

  openBtn?.addEventListener('click',   () => { form.style.display = 'block'; form.scrollIntoView({ behavior:'smooth', block:'start' }); });
  closeBtn?.addEventListener('click',  () => { form.style.display = 'none'; });
  cancelBtn?.addEventListener('click', () => { form.style.display = 'none'; });
  formEl?.addEventListener('submit', e => {
    e.preventDefault();
    submitRequest(formEl, form);
  });
}

// ── COMPLAINTS LIST ───────────────────────
function renderComplaintsList() {
  const el = document.getElementById('complaintsList');
  if (!el) return;
  el.innerHTML = COMPLAINTS.map(c => `
    <div class="complaint-item">
      <div class="ci-head">
        <div>
          <div class="ci-ref">${c.ref} · ${c.cat}</div>
          <div class="ci-title">${c.loc}</div>
        </div>
        <span class="status-badge status-${c.status}">${c.status}</span>
      </div>
      <div class="ci-meta">
        <span><i class="fa-regular fa-calendar"></i> Filed: ${c.date}</span>
        <span><i class="fa-solid fa-comment-dots"></i> ${c.note}</span>
      </div>
      <div class="ci-progress">
        <div class="progress-track"><div class="progress-fill" style="width:${c.progress}%"></div></div>
        <span class="progress-label">${c.progress}% resolved</span>
      </div>
    </div>
  `).join('');
}

function initComplaintFormToggle() {
  const openBtn  = document.getElementById('newComplaintBtn');
  const closeBtn = document.getElementById('closeComplaintForm');
  const cancelBtn= document.getElementById('cancelComplaintForm');
  const form     = document.getElementById('newComplaintForm');
  const formEl   = document.getElementById('complaintFormEl');

  openBtn?.addEventListener('click',   () => { form.style.display = 'block'; form.scrollIntoView({ behavior:'smooth' }); });
  closeBtn?.addEventListener('click',  () => { form.style.display = 'none'; });
  cancelBtn?.addEventListener('click', () => { form.style.display = 'none'; });
  formEl?.addEventListener('submit', e => {
    e.preventDefault();
    submitComplaint(formEl, form);
  });
}

// ── NOTIFICATIONS ─────────────────────────
function renderNotifications() {
  const list = document.getElementById('notifList');
  const drawerList = document.getElementById('notifDrawerList');
  const allNotifs = getAllNotifications();  // get notifications
  const html = allNotifs.map(n => `
    <div class="notif-item ${n.unread ? 'unread' : ''}" onclick="markRead(${n.id})">
      <div class="ni-icon" style="background:${n.iconBg}; color:${n.iconColor}">
        <i class="fa-solid ${n.icon}"></i>
      </div>
      <div>
        <div class="ni-title">${n.title}</div>
        <div class="ni-body">${n.body}</div>
        <div class="ni-time"><i class="fa-regular fa-clock"></i> ${n.time}</div>
      </div>
    </div>
  `).join('');
  if (list) list.innerHTML = html;
  if (drawerList) drawerList.innerHTML = html;
}

function markRead(id) {
  const allNotifs = getAllNotifications();
  const n = allNotifs.find(x => x.id === id);
  if (n) n.unread = false;
  renderNotifications();
}

// Notification badge/count logic removed — notifications render without numeric badge.

// ── NOTIFICATION DRAWER ───────────────────
function initNotifDrawer() {
  const drawer  = document.getElementById('notifDrawer');
  const overlay = document.getElementById('drawerOverlay');
  const toggle  = document.getElementById('notifToggle');
  const close   = document.getElementById('closeNotifDrawer');

  toggle?.addEventListener('click', () => { drawer.classList.toggle('open'); overlay.classList.toggle('show'); });
  close?.addEventListener('click',  () => { drawer.classList.remove('open'); overlay.classList.remove('show'); });
  overlay?.addEventListener('click',() => { drawer.classList.remove('open'); overlay.classList.remove('show'); });
}

function mapRequestRow(row) {
  return {
    ref: row.reference_no,
    type: row.document_type,
    purpose: row.purpose,
    date: row.date_requested,
    status: row.status
  };
}

function mapComplaintRow(row) {
  const statusToProgress = { open: 0, investigating: 50, resolved: 100 };
  return {
    ref: row.reference_no,
    cat: row.category,
    loc: row.location_text,
    date: row.date_filed,
    status: row.status,
    progress: statusToProgress[row.status] ?? 0,
    note: row.official_note || 'Received. Awaiting assignment.'
  };
}

function mapNotificationRow(row) {
  return {
    id: Number(row.id),
    unread: Number(row.is_read) === 0,
    iconBg: '#DBEAFE',
    iconColor: '#1976D2',
    icon: 'fa-bell',
    title: row.title,
    body: row.body,
    time: row.created_at
  };
}

async function fetchJson(url, options) {
  const res = await fetch(url, options);
  const raw = await res.text();
  let data = null;

  if (raw.trim() !== '') {
    try {
      data = JSON.parse(raw);
    } catch (err) {
      data = null;
    }
  }

  if (!res.ok) {
    if (data && data.message) {
      throw new Error(data.message);
    }
    throw new Error(`Server request failed (HTTP ${res.status}).`);
  }

  if (!data || typeof data !== 'object') {
    throw new Error('Server returned an invalid response format.');
  }

  if (!data.success) {
    throw new Error(data.message || 'Request failed.');
  }

  return data;
}

async function loadRequests() {
  // Resident identity is provided by the server-side PHP session.
  const data = await fetchJson(`${API_BASE}/requests/list.php`);
  REQUESTS = (data.requests || []).map(mapRequestRow);
}

async function loadComplaints() {
  const data = await fetchJson(`${API_BASE}/complaints/list.php`);
  COMPLAINTS = (data.complaints || []).map(mapComplaintRow);
}

async function loadNotifications() {
  const data = await fetchJson(`${API_BASE}/notifications/list.php`);
  NOTIFICATIONS = (data.notifications || []).map(mapNotificationRow);
}

async function refreshResidentView() {
  renderDashStats();
  renderRecentRequests();
  renderRecentComplaints();
  renderRequestsTable(REQUESTS);
  renderComplaintsList();
  renderNotifications();
}

async function submitRequest(formEl, form) {
  const selects = formEl.querySelectorAll('select');
  const type = selects[0]?.value || '';
  const purpose = selects[1]?.value || '';
  if (!type || !purpose) {
    showToast('Missing Fields', 'Please select a document type and purpose.');
    return;
  }
  try {
    await fetchJson(`${API_BASE}/requests/create.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ document_type: type, purpose })
    });
    form.style.display = 'none';
    formEl.reset();
    await loadRequests();
    await refreshResidentView();
    showToast('Request Submitted!', 'Your document request has been submitted.');
  } catch (err) {
    showToast('Submit Failed', err.message);
  }
}

async function submitComplaint(formEl, form) {
  const category = formEl.querySelector('select')?.value || '';
  const location = formEl.querySelector('input[type=text]')?.value || '';
  const description = formEl.querySelector('textarea')?.value || '';
  if (!category || !location || !description) {
    showToast('Missing Fields', 'Please complete all complaint details.');
    return;
  }
  try {
    await fetchJson(`${API_BASE}/complaints/create.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ category, location_text: location, description })
    });
    form.style.display = 'none';
    formEl.reset();
    await loadComplaints();
    await refreshResidentView();
    showToast('Complaint Submitted!', 'Your complaint has been submitted for review.');
  } catch (err) {
    showToast('Submit Failed', err.message);
  }
}

// ── TOAST ─────────────────────────────────
let toastTimeout;
function showToast(title, body) {
  let toast = document.getElementById('residentToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'residentToast';
    toast.style.cssText = `
      position:fixed; bottom:24px; right:24px; background:white;
      border-radius:12px; padding:16px 20px; box-shadow:0 8px 32px rgba(11,30,69,0.18);
      border-left:4px solid #059669; display:flex; align-items:flex-start; gap:12px;
      max-width:380px; z-index:9999; transform:translateY(120px); opacity:0;
      transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1);
    `;
    document.body.appendChild(toast);
  }
  toast.innerHTML = `
    <i class="fa-solid fa-circle-check" style="color:#059669; font-size:20px; margin-top:2px; flex-shrink:0;"></i>
    <div><div style="font-size:14px;font-weight:700;color:#0B1E45;margin-bottom:3px">${title}</div><div style="font-size:13px;color:#475569">${body}</div></div>
  `;
  clearTimeout(toastTimeout);
  requestAnimationFrame(() => {
    toast.style.transform = 'translateY(0)';
    toast.style.opacity = '1';
  });
  toastTimeout = setTimeout(() => {
    toast.style.transform = 'translateY(120px)';
    toast.style.opacity = '0';
  }, 5000);
}

// ── INIT ──────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initNav();
  restoreActiveSection();
  initSidebarToggle();
  initLogoutConfirmation();
  initRequestsTable();
  initReqFormToggle();
  initComplaintFormToggle();
  initNotifDrawer();
  (async () => {
    try {
      await Promise.all([loadRequests(), loadComplaints(), loadNotifications()]);
      await refreshResidentView();
    } catch (err) {
      showToast('Data Load Failed', err.message);
    }
  })();
});
