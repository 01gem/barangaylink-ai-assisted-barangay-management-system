/* ─────────────────────────────────────────
   BARANGAYLINK — OFFICIAL.JS
───────────────────────────────────────── */

const API_BASE = '../api';
const SIDEBAR_STATE_KEY = 'barangalink.sidebarCollapsed.official';
const ACTIVE_SECTION_KEY = 'official_active_section';
let RESIDENTS = [];
let OFFICIALS = [];
let DOC_REQUESTS = [];
let COMPLAINTS_ADMIN = [];
let ANNOUNCEMENTS_DATA = [];
let SERVICES_DATA = [];
let AUDIT_LOG = [];
let ACTIVE_DOC_GEN_REQUEST = null;
const FIXED_OFFICIAL_NAME = 'Hon. Erwin Astronomo';
const FIXED_OFFICIAL_POSITION = 'Barangay Captain';

const DOC_TEMPLATE_FIELDS = {
  'Barangay Clearance': ['full_name', 'age', 'civil_status', 'address', 'purpose', 'date_issued'],
  'Certificate of Residency': ['full_name', 'address', 'years_of_residency', 'purpose', 'date_issued'],
  'Certificate of Indigency': ['full_name', 'address', 'purpose', 'date_issued'],
  'Certificate of Good Moral Character': ['full_name', 'address', 'purpose', 'date_issued'],
  'Business Permit Endorsement': ['owner_name', 'business_name', 'business_type', 'business_address', 'purpose', 'date_issued']
};

// ── TAB SWITCHING ─────────────────────────
function switchTab(name) {
  document.querySelectorAll('.snav-item[data-tab]').forEach(b => b.classList.toggle('active', b.dataset.tab === name));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('active', p.id === `tab-${name}`));
  const titles = {
    dashboard:'Dashboard', residents:'Resident Management', requests:'Document Requests',
    complaints:'Complaints & Concerns', announcements:'Announcements',
    services:'Local Services Directory', officials:'Manage Officials', auditlog:'Audit Log'
  };
  document.getElementById('pageTitle').textContent = titles[name] || name;
  try {
    sessionStorage.setItem(ACTIVE_SECTION_KEY, name);
  } catch (err) {
    /* ignore storage failures */
  }
  if (name === 'auditlog') {
    loadAuditLog().catch(err => showToastAdmin('Audit Log Load Failed', err.message));
  }
  if (name === 'officials' && window.IS_OFFICIAL_ADMIN) {
    loadOfficials().catch(err => showToastAdmin('Officials Load Failed', err.message));
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

// ── DASHBOARD ─────────────────────────────
function renderDashStats() {
  const el = document.getElementById('dashStats');
  if (!el) return;
  const totalResidents = RESIDENTS.length;
  const pendingRequests = DOC_REQUESTS.filter(r => r.status === 'pending').length;
  const openComplaints = COMPLAINTS_ADMIN.filter(c => c.status !== 'resolved').length;
  const verifiedServices = SERVICES_DATA.length;
  const stats = [
    { val:String(totalResidents),  lbl:'Total Residents',   trend:'0 this month', ico:'fa-users',      bg:'#DBEAFE', color:'#1976D2' },
    { val:String(pendingRequests), lbl:'Pending Requests',  trend:'0 pending',    ico:'fa-file-clock', bg:'#FEF9C3', color:'#D97706' },
    { val:String(openComplaints),  lbl:'Open Complaints',   trend:'0 open',       ico:'fa-flag',       bg:'#FEE2E2', color:'#EF4444' },
    { val:String(verifiedServices),lbl:'Verified Services', trend:'0 verified',   ico:'fa-store',      bg:'#CCFBF1', color:'#0D9488' },
  ];
  el.innerHTML = stats.map(s => `
    <div class="stat-card">
      <div class="sc-ico" style="background:${s.bg};color:${s.color}"><i class="fa-solid ${s.ico}"></i></div>
      <div class="sc-data">
        <div class="sc-val">${s.val}</div>
        <div class="sc-lbl">${s.lbl}</div>
        <div class="sc-trend"><i class="fa-solid fa-arrow-trend-up"></i> ${s.trend}</div>
      </div>
    </div>
  `).join('');
}

function renderDashPendingReqs() {
  const el = document.getElementById('dashPendingReqs');
  if (!el) return;
  const pending = DOC_REQUESTS.filter(r => r.status === 'pending').slice(0,3);
  el.innerHTML = pending.map(r => `
    <div class="mini-row">
      <div class="mr-icon" style="background:#DBEAFE;color:#1976D2"><i class="fa-solid fa-file-lines"></i></div>
      <div class="mr-text">
        <div class="mr-title">${r.resident}</div>
        <div class="mr-sub">${r.type} · ${r.date}</div>
      </div>
      <span class="status-badge status-pending">Pending</span>
    </div>
  `).join('');
}

function renderDashOpenComplaints() {
  const el = document.getElementById('dashOpenComplaints');
  if (!el) return;
  const open = COMPLAINTS_ADMIN.filter(c => c.status === 'open' || c.status === 'investigating').slice(0,3);
  el.innerHTML = open.map(c => `
    <div class="mini-row">
      <div class="mr-icon" style="background:#FEE2E2;color:#EF4444"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div class="mr-text">
        <div class="mr-title">${c.cat}</div>
        <div class="mr-sub">${c.filer} · ${c.loc}</div>
      </div>
      <span class="status-badge status-${c.status}">${c.status}</span>
    </div>
  `).join('');
}

function renderDashActivity() {
  const el = document.getElementById('dashActivity');
  if (!el) return;
  const COLORS = { 'Request Approved':'#059669', 'Request Submitted':'#1976D2', 'Announcement Posted':'#D97706', 'Complaint Filed':'#EF4444', 'Resident Verified':'#0D9488', 'Login':'#94A3B8', 'Status Updated':'#0EA5E9' };
  el.innerHTML = AUDIT_LOG.slice(0,6).map(a => `
    <div class="activity-row">
      <div class="act-dot" style="background:${COLORS[a.action] || '#94A3B8'}"></div>
      <div class="act-text"><strong>${a.user}</strong> — ${a.action} in <strong>${a.module}</strong></div>
      <div class="act-time">${a.ts.split(' ')[1]}</div>
    </div>
  `).join('');
}

// ── RESIDENTS TABLE ───────────────────────
function renderResidents(data) {
  const tbody = document.getElementById('residentsBody');
  if (!tbody) return;
  tbody.innerHTML = data.map(r => `
    <tr>
      <td><code style="font-size:12px;color:var(--blue-mid)">${r.id}</code></td>
      <td style="font-weight:600;color:var(--text)">${r.name}</td>
      <td>${r.addr}</td>
      <td>${r.contact}</td>
      <td><span class="status-badge status-${r.status}">${r.status}</span></td>
      <td>
        <div class="action-btns">
          <button class="btn-action view" onclick="showModal('Resident Profile','${r.name} — ${r.addr} — ${r.contact} — Status: ${r.status}')">View</button>
          <button class="btn-action process" onclick="openEditResidentForm(${r.dbId})">Edit</button>
          <button class="btn-action reject" onclick="deleteResident(${r.dbId})">Delete</button>
        </div>
      </td>
    </tr>
  `).join('');
}
function initResidentTable() {
  renderResidents(RESIDENTS);
  document.getElementById('residentSearch')?.addEventListener('input', filterResidents);
  document.getElementById('residentStatusFilter')?.addEventListener('change', filterResidents);
}
function filterResidents() {
  const q = document.getElementById('residentSearch').value.toLowerCase();
  const s = document.getElementById('residentStatusFilter').value;
  renderResidents(RESIDENTS.filter(r => {
    return (r.name.toLowerCase().includes(q) || r.addr.toLowerCase().includes(q)) &&
           (s === 'all' || r.status === s);
  }));
}

// ── DOC REQUESTS TABLE ────────────────────
function renderDocRequests(data) {
  const tbody = document.getElementById('reqsBody');
  if (!tbody) return;
  tbody.innerHTML = data.map(r => `
    <tr>
      <td><code style="font-size:12px;color:var(--blue-mid)">${r.ref}</code></td>
      <td style="font-weight:600">${r.resident}</td>
      <td>${r.type}</td>
      <td>${r.purpose}</td>
      <td>${r.date}</td>
      <td><span class="status-badge status-${r.status}">${r.status}</span></td>
        <td>
          <div class="action-btns">
            ${r.status === 'pending'    ? `<button class="btn-action process" onclick="openDocumentGenerationModal('${r.ref}')">Process</button>` : ''}
            ${r.status === 'processing' ? `<button class="btn-action ready"   onclick="updateReqStatus('${r.ref}','ready')">Mark Ready</button>` : ''}
            ${['pending', 'processing'].includes(r.status) ? `<button class="btn-action reject" onclick="cancelReq('${r.ref}')">Cancel Request</button>` : ''}
            ${r.status === 'ready'      ? `<button class="btn-action approve" onclick="updateReqStatus('${r.ref}','completed')">Complete</button>` : ''}
            ${r.status === 'ready'      ? `<button class="btn-action notify"  onclick="notifyPickupReady('${r.ref}')">Notify via SMS</button>` : ''}
            <button class="btn-action view" onclick="showModal('Request Details','${r.ref} — ${r.resident} — ${r.type}')">View</button>
          </div>
        </td>
    </tr>
  `).join('');
}
async function updateReqStatus(ref, newStatus, officialNote = '') {
  try {
    const payload = { reference_no: ref, status: newStatus };
    if (officialNote) payload.official_note = officialNote;
    const data = await fetchJson(`${API_BASE}/requests/update_status.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Portal-Source': 'official'
      },
      body: JSON.stringify(payload)
    });
    await loadRequests();
    await loadAuditLog();
    await refreshDashboard();
    showToastAdmin('Status Updated', data.message || `${ref} status set to ${newStatus}.`);
  } catch (err) {
    showToastAdmin('Update Failed', err.message);
  }
}
async function cancelReq(ref) {
  const reason = window.prompt('Brief reason for cancelling this request (required):');
  if (reason === null) return;
  const note = reason.trim();
  if (!note) {
    showToastAdmin('Cancellation Failed', 'A brief cancellation reason is required.');
    return;
  }
  await updateReqStatus(ref, 'cancelled', note);
}

function prettifyFieldName(fieldKey) {
  return fieldKey
    .split('_')
    .map(part => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

function escapeAttribute(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function getResidentDefaultsForRequest(req) {
  if (!req) return { full_name: '', address: '' };
  const resident = RESIDENTS.find(r => Number(r.dbId) === Number(req.residentId));
  return {
    full_name: resident?.name || req.resident || '',
    address: resident && resident.addr !== '-' ? resident.addr : ''
  };
}

function closeDocumentGenerationModal() {
  const modal = document.getElementById('docGenModal');
  const fieldsWrap = document.getElementById('docGenFields');
  const previewWrap = document.getElementById('docGenPreviewWrap');
  const previewFrame = document.getElementById('docGenPreviewFrame');
  const previewEmpty = document.getElementById('docGenPreviewEmpty');
  if (modal) modal.classList.remove('show');
  if (fieldsWrap) fieldsWrap.innerHTML = '';
  if (previewWrap) previewWrap.style.display = 'none';
  if (previewEmpty) previewEmpty.style.display = 'flex';
  if (previewFrame) previewFrame.src = 'about:blank';
  ACTIVE_DOC_GEN_REQUEST = null;
}

function renderDocumentGenerationFields(req) {
  const fieldsWrap = document.getElementById('docGenFields');
  if (!fieldsWrap) return;
  const keys = DOC_TEMPLATE_FIELDS[req.type] || [];
  const residentDefaults = getResidentDefaultsForRequest(req);
  const todayIso = new Date().toISOString().slice(0, 10);
  const purposeDefault = req.purpose || '';

  fieldsWrap.innerHTML = keys.map((key) => {
    let value = '';
    if (key === 'full_name' || key === 'owner_name') value = residentDefaults.full_name;
    if (key === 'address' || key === 'business_address') value = residentDefaults.address;
    if (key === 'purpose') value = purposeDefault;
    if (key === 'date_issued') value = todayIso;
    const isDate = key === 'date_issued';
    return `
      <div class="field" style="margin-top:8px;">
        <label>${prettifyFieldName(key)}</label>
        <input
          type="${isDate ? 'date' : 'text'}"
          class="form-input"
          data-doc-field="${key}"
          value="${escapeAttribute(value)}"
        />
      </div>
    `;
  }).join('');
}

function openDocumentGenerationModal(ref) {
  const req = DOC_REQUESTS.find(r => r.ref === ref);
  if (!req) return;
  if (!DOC_TEMPLATE_FIELDS[req.type]) {
    showToastAdmin('Template Missing', `No template configuration found for "${req.type}".`);
    return;
  }
  ACTIVE_DOC_GEN_REQUEST = req;
  const modal = document.getElementById('docGenModal');
  const title = document.getElementById('docGenTitle');
  const meta = document.getElementById('docGenMeta');
  const previewWrap = document.getElementById('docGenPreviewWrap');
  const previewEmpty = document.getElementById('docGenPreviewEmpty');
  if (title) title.textContent = `Generate ${req.type}`;
  if (meta) meta.textContent = `Reference: ${req.ref} · Resident: ${req.resident}`;
  if (previewWrap) previewWrap.style.display = 'none';
  if (previewEmpty) previewEmpty.style.display = 'flex';
  renderDocumentGenerationFields(req);
  if (modal) modal.classList.add('show');
}

async function generateAndPreviewDocument() {
  if (!ACTIVE_DOC_GEN_REQUEST) return;
  const fields = {};
  document.querySelectorAll('#docGenFields [data-doc-field]').forEach((input) => {
    const key = input.getAttribute('data-doc-field');
    if (!key) return;
    fields[key] = (input.value || '').trim();
  });
  fields.official_name = FIXED_OFFICIAL_NAME;
  fields.official_position = FIXED_OFFICIAL_POSITION;

  try {
    const data = await fetchJson(`${API_BASE}/requests/generate_document.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id: ACTIVE_DOC_GEN_REQUEST.id,
        fields
      })
    });

    const previewFrame = document.getElementById('docGenPreviewFrame');
    const previewWrap = document.getElementById('docGenPreviewWrap');
    const previewEmpty = document.getElementById('docGenPreviewEmpty');
    if (previewFrame && data.pdf_path) {
      const separator = data.pdf_path.includes('?') ? '&' : '?';
      previewFrame.src = `${data.pdf_path}${separator}t=${Date.now()}`;
    }
    if (previewWrap) previewWrap.style.display = 'block';
    if (previewEmpty) previewEmpty.style.display = 'none';

    await updateReqStatus(ACTIVE_DOC_GEN_REQUEST.ref, 'processing');
    showToastAdmin('Document Generated', `Generated PDF for ${ACTIVE_DOC_GEN_REQUEST.ref}.`);
  } catch (err) {
    showToastAdmin('Generation Failed', err.message);
  }
}

function initDocumentGenerationModal() {
  document.getElementById('closeDocGenModal')?.addEventListener('click', closeDocumentGenerationModal);
  document.getElementById('cancelDocGenModal')?.addEventListener('click', closeDocumentGenerationModal);
  document.getElementById('generateDocBtn')?.addEventListener('click', generateAndPreviewDocument);
  document.getElementById('docGenModal')?.addEventListener('click', (event) => {
    if (event.target === event.currentTarget) closeDocumentGenerationModal();
  });
}

function findResidentContactForRequest(req) {
  if (!req) return '';
  if (req.residentId) {
    const byId = RESIDENTS.find(r => Number(r.dbId) === Number(req.residentId));
    if (byId && byId.contact && byId.contact !== '-') {
      return byId.contact;
    }
  }
  const residentName = (req.resident || '').trim().toLowerCase();
  const byName = RESIDENTS.find(r => (r.name || '').trim().toLowerCase() === residentName);
  return byName && byName.contact && byName.contact !== '' && byName.contact !== '-' ? byName.contact : '';
}

async function notifyPickupReady(ref) {
  const req = DOC_REQUESTS.find(r => r.ref === ref);
  if (!req) return;
  try {
    const payload = { reference_no: ref };
    let contact = findResidentContactForRequest(req);
    if (!contact) {
      const contactOverride = window.prompt(
        `Notify ${req.resident} via SMS that ${ref} is ready for pickup.\nEnter contact number, or leave blank to look up from records.`,
        ''
      );
      if (contactOverride && contactOverride.trim() !== '') {
        payload.contact = contactOverride.trim();
      }
    } else {
      payload.contact = contact;
    }
    const data = await fetchJson(`${API_BASE}/requests/notify_ready.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Portal-Source': 'official'
      },
      body: JSON.stringify(payload)
    });
    await loadRequests();
    await loadAuditLog();
    await refreshDashboard();
    showToastAdmin('Resident Notified', data.message || `${ref} SMS pickup notification sent.`);
  } catch (err) {
    showToastAdmin('Notify Failed', err.message);
  }
}
function initDocRequestsTable() {
  renderDocRequests(DOC_REQUESTS);
  document.getElementById('reqSearch')?.addEventListener('input', () => {
    const q = document.getElementById('reqSearch').value.toLowerCase();
    const s = document.getElementById('reqStatusFilter').value;
    renderDocRequests(DOC_REQUESTS.filter(r => (r.resident.toLowerCase().includes(q) || r.ref.toLowerCase().includes(q)) && (s === 'all' || r.status === s)));
  });
  document.getElementById('reqStatusFilter')?.addEventListener('change', () => {
    document.getElementById('reqSearch').dispatchEvent(new Event('input'));
  });
}

// ── COMPLAINTS ADMIN ──────────────────────
function renderComplaintsAdmin(data) {
  const el = document.getElementById('complaintsAdminList');
  if (!el) return;
  el.innerHTML = data.map(c => `
    <div class="complaint-admin-row" id="comp-${c.ref.replace('#','')}">
      <div class="ca-top">
        <div>
          <div class="ca-ref">${c.ref} · ${c.filer}</div>
          <div class="ca-title">${c.cat} — ${c.loc}</div>
        </div>
        <span class="status-badge status-${c.status}">${c.status}</span>
      </div>
      <div class="ca-meta">
        <span><i class="fa-regular fa-calendar"></i> Filed: ${c.date}</span>
        ${c.note ? `<span><i class="fa-solid fa-comment"></i> ${c.note}</span>` : ''}
      </div>
      <div class="ca-actions">
        <label>Update:</label>
        <select class="ca-status-select" id="compStatus-${c.id}">
          <option value="open"          ${c.status==='open'          ?'selected':''}>Open</option>
          <option value="investigating" ${c.status==='investigating' ?'selected':''}>Investigating</option>
          <option value="resolved"      ${c.status==='resolved'      ?'selected':''}>Resolved</option>
        </select>
        <input type="text" class="ca-note-input" id="compNote-${c.id}" placeholder="Add official note…" value="${c.note}" />
        <button class="btn-action resolve" onclick="updateComplaint(${c.id})">Save</button>
        <button class="btn-action view" onclick="showModal('Complaint Detail','${c.ref} — ${c.cat} — Filed by ${c.filer}')">View</button>
      </div>
    </div>
  `).join('');
}
async function updateComplaint(id) {
  const c = COMPLAINTS_ADMIN.find(x => Number(x.id) === Number(id));
  if (!c) return;
  const status = document.getElementById(`compStatus-${id}`).value;
  const officialNote = document.getElementById(`compNote-${id}`).value;
  try {
    const resolving = status === 'resolved';
    const data = await fetchJson(`${API_BASE}/complaints/${resolving ? 'notify_resolved' : 'update_status'}.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, status, official_note: officialNote })
    });
    await loadComplaints();
    await loadAuditLog();
    await refreshDashboard();
    showToastAdmin(resolving ? 'Complaint Resolved' : 'Complaint Updated', data.message || `${c.ref} status updated to "${status}".`);
  } catch (err) {
    showToastAdmin('Update Failed', err.message);
  }
}

function mapResidentRow(row) {
  return {
    dbId: Number(row.id),
    id: `RS-${String(row.id).padStart(4, '0')}`,
    name: `${row.fname} ${row.lname}`.trim(),
    fname: row.fname || '',
    lname: row.lname || '',
    addr: row.address || '-',
    contact: row.contact || '-',
    username: row.username || '',
    status: 'verified'
  };
}

function mapAuditRow(row) {
  return {
    ts: row.created_at || '',
    user: row.official_name || 'Unknown Official',
    role: 'Official',
    action: row.action || 'Action',
    module: row.target_type || '-',
    ip: row.target_reference || '-'
  };
}

function mapRequestRow(row) {
  return {
    id: Number(row.id),
    ref: row.reference_no,
    residentId: row.resident_id ? Number(row.resident_id) : 0,
    resident: row.resident_name || 'Resident',
    type: row.document_type,
    purpose: row.purpose,
    date: row.date_requested,
    status: row.status
  };
}

function mapComplaintRow(row) {
  return {
    id: Number(row.id),
    ref: row.reference_no,
    filer: row.resident_name,
    cat: row.category,
    loc: row.location_text,
    date: row.date_filed,
    status: row.status,
    note: row.official_note || ''
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

async function loadResidents() {
  const data = await fetchJson(`${API_BASE}/residents/list.php`);
  RESIDENTS = (data.residents || []).map(mapResidentRow);
  renderResidents(RESIDENTS);
}

async function loadRequests() {
  const data = await fetchJson(`${API_BASE}/requests/list.php`, {
    headers: { 'X-Portal-Source': 'official' }
  });
  DOC_REQUESTS = (data.requests || []).map(mapRequestRow);
  renderDocRequests(DOC_REQUESTS);
}

async function loadComplaints() {
  const data = await fetchJson(`${API_BASE}/complaints/list.php`);
  COMPLAINTS_ADMIN = (data.complaints || []).map(mapComplaintRow);
  renderComplaintsAdmin(COMPLAINTS_ADMIN);
}

async function refreshDashboard() {
  renderDashStats();
  renderDashPendingReqs();
  renderDashOpenComplaints();
  renderDashActivity();
}

function initResidentCreateForm() {
  const openBtn = document.getElementById('addResidentBtn');
  const closeBtn = document.getElementById('closeResidentForm');
  const cancelBtn = document.getElementById('cancelResidentForm');
  const card = document.getElementById('residentFormCard');
  const form = document.getElementById('residentFormEl');
  const title = document.getElementById('residentFormTitle');
  const submitBtn = document.getElementById('residentFormSubmitBtn');
  const passwordLabel = document.getElementById('residentPasswordLabel');
  const passwordInput = form?.querySelector('input[name="password"]');
  if (!card || !form) return;

  const resetToCreateMode = () => {
    form.reset();
    form.resident_id.value = '';
    title.innerHTML = '<i class="fa-solid fa-user-plus"></i> Add New Resident';
    submitBtn.textContent = 'Save Resident';
    passwordLabel.textContent = 'Temporary Password';
    passwordInput.required = true;
    passwordInput.placeholder = '';
  };
  const closeForm = () => {
    card.style.display = 'none';
    resetToCreateMode();
  };

  openBtn?.addEventListener('click', () => {
    resetToCreateMode();
    card.style.display = 'block';
  });
  closeBtn?.addEventListener('click', closeForm);
  cancelBtn?.addEventListener('click', closeForm);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = Object.fromEntries(new FormData(form).entries());
    const isEdit = payload.resident_id && String(payload.resident_id).trim() !== '';
    const url = isEdit ? `${API_BASE}/residents/update.php` : `${API_BASE}/residents/create.php`;
    try {
      await fetchJson(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Portal-Source': 'official'
        },
        body: JSON.stringify(payload)
      });
      closeForm();
      await loadResidents();
      await loadAuditLog();
      await refreshDashboard();
      showToastAdmin(isEdit ? 'Resident Updated' : 'Resident Added', isEdit ? 'Resident record was updated successfully.' : 'New resident account was created successfully.');
    } catch (err) {
      showToastAdmin(isEdit ? 'Update Failed' : 'Create Failed', err.message);
    }
  });
}

function openEditResidentForm(dbId) {
  const resident = RESIDENTS.find(r => r.dbId === Number(dbId));
  const card = document.getElementById('residentFormCard');
  const form = document.getElementById('residentFormEl');
  const title = document.getElementById('residentFormTitle');
  const submitBtn = document.getElementById('residentFormSubmitBtn');
  const passwordLabel = document.getElementById('residentPasswordLabel');
  const passwordInput = form?.querySelector('input[name="password"]');
  if (!resident || !card || !form || !title || !submitBtn || !passwordLabel || !passwordInput) return;

  form.resident_id.value = String(resident.dbId);
  form.fname.value = resident.fname;
  form.lname.value = resident.lname;
  form.contact.value = resident.contact === '-' ? '' : resident.contact;
  form.username.value = resident.username;
  form.address.value = resident.addr === '-' ? '' : resident.addr;
  form.password.value = '';
  passwordInput.required = false;
  passwordInput.placeholder = 'Leave blank to keep current password';
  title.innerHTML = '<i class="fa-solid fa-pen"></i> Edit Resident';
  submitBtn.textContent = 'Update Resident';
  passwordLabel.textContent = 'New Password (optional)';
  card.style.display = 'block';
}

async function deleteResident(dbId) {
  const resident = RESIDENTS.find(r => r.dbId === Number(dbId));
  if (!resident) return;
  const ok = window.confirm(`Delete resident record for ${resident.name}?`);
  if (!ok) return;
  try {
    await fetchJson(`${API_BASE}/residents/delete.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Portal-Source': 'official'
      },
      body: JSON.stringify({ resident_id: resident.dbId })
    });
    await loadResidents();
    await loadAuditLog();
    await refreshDashboard();
    showToastAdmin('Resident Deleted', `${resident.name} was removed from resident records.`);
  } catch (err) {
    showToastAdmin('Delete Failed', err.message);
  }
}

function mapOfficialRow(row) {
  return {
    dbId: Number(row.id),
    fname: row.fname || '',
    lname: row.lname || '',
    name: `${row.fname || ''} ${row.lname || ''}`.trim(),
    username: row.username || '',
    contact: row.contact || '-',
    address: row.address || '',
    position: row.position || '-',
    role: row.role || 'staff',
    status: row.status || 'active'
  };
}

function renderOfficials(data) {
  const tbody = document.getElementById('officialsBody');
  if (!tbody) return;
  tbody.innerHTML = data.map(o => {
    const isSelf = Number(o.dbId) === Number(window.OFFICIAL_ID);
    const toggleLabel = o.status === 'active' ? 'Deactivate' : 'Activate';
    return `
    <tr>
      <td><code style="font-size:12px;color:var(--blue-mid)">${o.dbId}</code></td>
      <td style="font-weight:600;color:var(--text)">${o.name}</td>
      <td>${o.username}</td>
      <td>${o.contact}</td>
      <td>${o.position}</td>
      <td><span class="status-badge" style="background:var(--surface-2);color:var(--text-2)">${o.role}</span></td>
      <td><span class="status-badge status-${o.status === 'active' ? 'verified' : 'suspended'}">${o.status}</span></td>
      <td>
        <div class="action-btns">
          <button class="btn-action process" onclick="openEditOfficialForm(${o.dbId})">Edit</button>
          ${isSelf ? '' : `<button class="btn-action ${o.status === 'active' ? 'reject' : 'approve'}" onclick="toggleOfficialStatus(${o.dbId})">${toggleLabel}</button>`}
        </div>
      </td>
    </tr>
  `;
  }).join('');
}

function filterOfficials() {
  const searchEl = document.getElementById('officialSearch');
  const statusEl = document.getElementById('officialStatusFilter');
  if (!searchEl || !statusEl) return;
  const q = searchEl.value.toLowerCase();
  const s = statusEl.value;
  renderOfficials(OFFICIALS.filter(o => {
    const hay = `${o.name} ${o.username} ${o.position}`.toLowerCase();
    return hay.includes(q) && (s === 'all' || o.status === s);
  }));
}

async function loadOfficials() {
  if (!window.IS_OFFICIAL_ADMIN) return;
  const data = await fetchJson(`${API_BASE}/officials/list.php`);
  OFFICIALS = (data.officials || []).map(mapOfficialRow);
  renderOfficials(OFFICIALS);
}

function initOfficialsSection() {
  if (!window.IS_OFFICIAL_ADMIN) return;
  const openBtn = document.getElementById('addOfficialBtn');
  const closeBtn = document.getElementById('closeOfficialForm');
  const cancelBtn = document.getElementById('cancelOfficialForm');
  const card = document.getElementById('officialFormCard');
  const form = document.getElementById('officialFormEl');
  const title = document.getElementById('officialFormTitle');
  const submitBtn = document.getElementById('officialFormSubmitBtn');
  const passwordLabel = document.getElementById('officialPasswordLabel');
  const passwordInput = form?.querySelector('input[name="password"]');
  if (!card || !form) return;

  const resetToCreateMode = () => {
    form.reset();
    form.official_id.value = '';
    title.innerHTML = '<i class="fa-solid fa-user-plus"></i> Add New Official';
    submitBtn.textContent = 'Save Official';
    passwordLabel.textContent = 'Temporary Password';
    passwordInput.required = true;
    passwordInput.placeholder = '';
  };
  const closeForm = () => {
    card.style.display = 'none';
    resetToCreateMode();
  };

  openBtn?.addEventListener('click', () => {
    resetToCreateMode();
    card.style.display = 'block';
  });
  closeBtn?.addEventListener('click', closeForm);
  cancelBtn?.addEventListener('click', closeForm);
  document.getElementById('officialSearch')?.addEventListener('input', filterOfficials);
  document.getElementById('officialStatusFilter')?.addEventListener('change', filterOfficials);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = Object.fromEntries(new FormData(form).entries());
    const isEdit = payload.official_id && String(payload.official_id).trim() !== '';
    const url = isEdit ? `${API_BASE}/officials/update.php` : `${API_BASE}/officials/create.php`;
    try {
      await fetchJson(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      closeForm();
      await loadOfficials();
      await loadAuditLog();
      showToastAdmin(isEdit ? 'Official Updated' : 'Official Added', isEdit ? 'Official account was updated successfully.' : 'New official account was created successfully.');
    } catch (err) {
      showToastAdmin(isEdit ? 'Update Failed' : 'Create Failed', err.message);
    }
  });
}

function openEditOfficialForm(dbId) {
  const official = OFFICIALS.find(o => o.dbId === Number(dbId));
  const card = document.getElementById('officialFormCard');
  const form = document.getElementById('officialFormEl');
  const title = document.getElementById('officialFormTitle');
  const submitBtn = document.getElementById('officialFormSubmitBtn');
  const passwordLabel = document.getElementById('officialPasswordLabel');
  const passwordInput = form?.querySelector('input[name="password"]');
  if (!official || !card || !form || !title || !submitBtn || !passwordLabel || !passwordInput) return;

  form.official_id.value = String(official.dbId);
  form.fname.value = official.fname;
  form.lname.value = official.lname;
  form.contact.value = official.contact === '-' ? '' : official.contact;
  form.username.value = official.username;
  form.address.value = official.address;
  form.position.value = official.position === '-' ? '' : official.position;
  form.role.value = official.role;
  form.password.value = '';
  passwordInput.required = false;
  passwordInput.placeholder = 'Leave blank to keep current password';
  title.innerHTML = '<i class="fa-solid fa-pen"></i> Edit Official';
  submitBtn.textContent = 'Update Official';
  passwordLabel.textContent = 'New Password (optional)';
  card.style.display = 'block';
}

async function toggleOfficialStatus(dbId) {
  const official = OFFICIALS.find(o => o.dbId === Number(dbId));
  if (!official) return;
  const next = official.status === 'active' ? 'deactivate' : 'activate';
  const ok = window.confirm(`${next === 'deactivate' ? 'Deactivate' : 'Activate'} official account for ${official.name}?`);
  if (!ok) return;
  try {
    await fetchJson(`${API_BASE}/officials/deactivate.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ official_id: official.dbId })
    });
    await loadOfficials();
    await loadAuditLog();
    showToastAdmin(next === 'deactivate' ? 'Official Deactivated' : 'Official Activated', `${official.name} is now ${next === 'deactivate' ? 'inactive' : 'active'}.`);
  } catch (err) {
    showToastAdmin('Update Failed', err.message);
  }
}

function initComplaintsAdmin() {
  renderComplaintsAdmin(COMPLAINTS_ADMIN);
  document.getElementById('compSearch')?.addEventListener('input', filterComplaintsAdmin);
  document.getElementById('compStatusFilter')?.addEventListener('change', filterComplaintsAdmin);
}
function filterComplaintsAdmin() {
  const q = document.getElementById('compSearch').value.toLowerCase();
  const s = document.getElementById('compStatusFilter').value;
  renderComplaintsAdmin(COMPLAINTS_ADMIN.filter(c => {
    return (c.cat.toLowerCase().includes(q) || c.filer.toLowerCase().includes(q) || c.ref.toLowerCase().includes(q)) &&
           (s === 'all' || c.status === s);
  }));
}

// ── ANNOUNCEMENTS ─────────────────────────
function renderAnnouncementsAdmin() {
  const el = document.getElementById('announcementsAdminList');
  if (!el) return;
  if (!ANNOUNCEMENTS_DATA.length) {
    el.innerHTML = '<p style="padding:20px;color:var(--text-3)">No announcements have been posted yet.</p>';
    return;
  }
  el.innerHTML = ANNOUNCEMENTS_DATA.map(a => `
    <div class="ann-admin-item">
      <div class="aai-cat" style="background:#DBEAFE">${Number(a.is_pinned) === 1 ? '📌' : '📢'}</div>
      <div class="aai-body">
        <div class="aai-title">${a.title}</div>
        <div class="aai-meta">
          <span><i class="fa-regular fa-calendar"></i> ${formatAnnouncementDate(a.created_at)}</span>
          ${Number(a.is_pinned) === 1 ? '<span style="color:#1976D2"><i class="fa-solid fa-thumbtack"></i> Pinned</span>' : ''}
        </div>
      </div>
      <div class="aai-actions">
        <button class="btn-action view" onclick="viewAnnouncement(${a.id})">View</button>
        <button class="btn-action process" onclick="editAnnouncement(${a.id})">Edit</button>
        <button class="btn-action approve" onclick="toggleAnnouncementPin(${a.id})">${Number(a.is_pinned) === 1 ? 'Unpin' : 'Pin'}</button>
        <button class="btn-action reject" onclick="deleteAnnouncement(${a.id})">Delete</button>
      </div>
    </div>
  `).join('');
}
function formatAnnouncementDate(value) {
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString(undefined, {
    month: 'short', day: 'numeric', year: 'numeric'
  });
}
async function loadAnnouncements() {
  const data = await fetchJson(`${API_BASE}/announcements/list.php`);
  ANNOUNCEMENTS_DATA = data.announcements || [];
  renderAnnouncementsAdmin();
}
function initAnnouncementForm() {
  document.getElementById('newAnnBtn')?.addEventListener('click', () => { document.getElementById('annForm').style.display='block'; });
  document.getElementById('closeAnnForm')?.addEventListener('click', () => { document.getElementById('annForm').style.display='none'; });
  document.getElementById('cancelAnnForm')?.addEventListener('click', () => { document.getElementById('annForm').style.display='none'; });
  document.getElementById('annFormEl')?.addEventListener('submit', async e => {
    e.preventDefault();
    const title = e.target.querySelector('input[type=text]').value.trim();
    const content = e.target.querySelector('textarea').value.trim();
    try {
      await fetchJson(`${API_BASE}/announcements/create.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title, content, is_pinned: 0 })
      });
      await loadAnnouncements();
      await loadAuditLog();
      document.getElementById('annForm').style.display='none';
      e.target.reset();
      showToastAdmin('Announcement Posted', 'Announcement posted successfully.');
    } catch (err) {
      showToastAdmin('Post Failed', err.message);
    }
  });
}
async function editAnnouncement(id) {
  const announcement = ANNOUNCEMENTS_DATA.find(a => Number(a.id) === Number(id));
  if (!announcement) return;
  const title = window.prompt('Announcement title:', announcement.title);
  if (title === null) return;
  const content = window.prompt('Announcement content:', announcement.content);
  if (content === null) return;
  try {
    await fetchJson(`${API_BASE}/announcements/update.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, title, content, is_pinned: Number(announcement.is_pinned) === 1 })
    });
    await loadAnnouncements();
    await loadAuditLog();
    showToastAdmin('Announcement Updated', 'Announcement updated successfully.');
  } catch (err) {
    showToastAdmin('Update Failed', err.message);
  }
}
function viewAnnouncement(id) {
  const announcement = ANNOUNCEMENTS_DATA.find(a => Number(a.id) === Number(id));
  if (announcement) showModal(announcement.title, announcement.content);
}
async function toggleAnnouncementPin(id) {
  const announcement = ANNOUNCEMENTS_DATA.find(a => Number(a.id) === Number(id));
  if (!announcement) return;
  try {
    await fetchJson(`${API_BASE}/announcements/update.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id,
        title: announcement.title,
        content: announcement.content,
        is_pinned: Number(announcement.is_pinned) !== 1
      })
    });
    await loadAnnouncements();
    await loadAuditLog();
    showToastAdmin('Announcement Updated', Number(announcement.is_pinned) === 1 ? 'Announcement unpinned.' : 'Announcement pinned.');
  } catch (err) {
    showToastAdmin('Update Failed', err.message);
  }
}
async function deleteAnnouncement(id) {
  if (!window.confirm('Delete this announcement?')) return;
  try {
    await fetchJson(`${API_BASE}/announcements/delete.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    await loadAnnouncements();
    await loadAuditLog();
    showToastAdmin('Announcement Deleted', 'Announcement deleted successfully.');
  } catch (err) {
    showToastAdmin('Delete Failed', err.message);
  }
}

// ── SERVICES ──────────────────────────────
function renderServicesAdmin() {
  const el = document.getElementById('servicesAdminGrid');
  if (!el) return;
  if (!SERVICES_DATA.length) {
    el.innerHTML = '<p style="color:var(--text-3)">No local services have been added yet.</p>';
    return;
  }
  el.innerHTML = SERVICES_DATA.map(s => `
    <div class="svc-admin-card">
      <div class="sac-head">
        <span class="sac-emoji">🏪</span>
        <div>
          <div class="sac-title">${s.service_name}</div>
          <div class="sac-cat">${s.category}</div>
        </div>
      </div>
      <div style="font-size:12px;color:var(--text-3)"><i class="fa-solid fa-location-dot"></i> ${s.address || 'Address not provided'}</div>
      <div style="font-size:12px;color:var(--text-3);margin-top:5px;"><i class="fa-solid fa-phone"></i> ${s.contact_number || 'Contact not provided'}</div>
      <div class="sac-actions">
        <button class="btn-action view" onclick="viewService(${s.id})">View</button>
        <button class="btn-action process" onclick="editService(${s.id})">Edit</button>
        <button class="btn-action reject" onclick="deleteService(${s.id})">Remove</button>
      </div>
    </div>
  `).join('');
}
async function loadServices() {
  const data = await fetchJson(`${API_BASE}/services/list.php`);
  SERVICES_DATA = data.services || [];
  renderServicesAdmin();
}

async function loadAuditLog() {
  const data = await fetchJson(`${API_BASE}/audit/list.php`);
  AUDIT_LOG = (data.logs || []).map(mapAuditRow);
  renderAuditLog();
}
function resetServiceForm() {
  const form = document.getElementById('serviceFormEl');
  form?.reset();
  if (form) form.elements.id.value = '';
}
function showServiceForm(service = null) {
  const form = document.getElementById('serviceFormEl');
  if (!form) return;
  resetServiceForm();
  if (service) {
    ['id', 'service_name', 'category', 'contact_number', 'address', 'operating_hours', 'description'].forEach(field => {
      form.elements[field].value = service[field] || '';
    });
  }
  document.getElementById('serviceForm').style.display = 'block';
}
function initServiceForm() {
  document.getElementById('newServiceBtn')?.addEventListener('click', () => showServiceForm());
  document.getElementById('closeServiceForm')?.addEventListener('click', () => { document.getElementById('serviceForm').style.display = 'none'; });
  document.getElementById('cancelServiceForm')?.addEventListener('click', () => { document.getElementById('serviceForm').style.display = 'none'; });
  document.getElementById('serviceFormEl')?.addEventListener('submit', async event => {
    event.preventDefault();
    const payload = Object.fromEntries(new FormData(event.target).entries());
    const isEdit = payload.id && String(payload.id).trim() !== '';
    try {
      await fetchJson(`${API_BASE}/services/${isEdit ? 'update' : 'create'}.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      await loadServices();
      await loadAuditLog();
      document.getElementById('serviceForm').style.display = 'none';
      resetServiceForm();
      showToastAdmin(isEdit ? 'Service Updated' : 'Service Added', 'Local service saved successfully.');
    } catch (err) {
      showToastAdmin(isEdit ? 'Update Failed' : 'Create Failed', err.message);
    }
  });
}
function viewService(id) {
  const service = SERVICES_DATA.find(s => Number(s.id) === Number(id));
  if (!service) return;
  const details = [service.category, service.description, service.address, service.contact_number, service.operating_hours]
    .filter(Boolean).join('<br>');
  showModal(service.service_name, details || 'No additional details provided.');
}
function editService(id) {
  const service = SERVICES_DATA.find(s => Number(s.id) === Number(id));
  if (service) showServiceForm(service);
}
async function deleteService(id) {
  if (!window.confirm('Remove this local service?')) return;
  try {
    await fetchJson(`${API_BASE}/services/delete.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    await loadServices();
    await loadAuditLog();
    await refreshDashboard();
    showToastAdmin('Service Removed', 'Local service removed successfully.');
  } catch (err) {
    showToastAdmin('Delete Failed', err.message);
  }
}

// ── AUDIT LOG ─────────────────────────────
const AUDIT_TAG_COLORS = {
  'Request Approved':    '#059669', 'Request Submitted': '#1976D2',
  'Announcement Posted': '#D97706', 'Complaint Filed':   '#EF4444',
  'Resident Verified':   '#0D9488', 'Login':             '#94A3B8',
  'Status Updated':      '#0EA5E9',
  'Created resident': '#0D9488',
  'Updated resident': '#0284C7',
  'Deleted resident': '#DC2626',
  'Updated document request status': '#0EA5E9',
  'Sent pickup notification': '#7C3AED',
  'Updated complaint status': '#D97706',
  'Resolved complaint': '#16A34A',
  'Created announcement': '#D97706',
  'Updated announcement': '#2563EB',
  'Deleted announcement': '#DC2626',
  'Created service': '#0D9488',
  'Updated service': '#0284C7',
  'Created official': '#0D9488',
  'Updated official': '#0284C7',
  'Deactivated official': '#DC2626',
  'Activated official': '#16A34A'
};
function renderAuditLog() {
  const tbody = document.getElementById('auditBody');
  if (!tbody) return;
  tbody.innerHTML = AUDIT_LOG.map(a => `
    <tr>
      <td style="font-size:12px;white-space:nowrap">${a.ts}</td>
      <td style="font-weight:600">${a.user}</td>
      <td><span class="status-badge" style="background:var(--surface-2);color:var(--text-2)">${a.role}</span></td>
      <td><span class="audit-action-tag" style="background:${AUDIT_TAG_COLORS[a.action]||'#94A3B8'}20;color:${AUDIT_TAG_COLORS[a.action]||'#94A3B8'}">${a.action}</span></td>
      <td>${a.module}</td>
      <td style="font-size:12px;color:var(--text-3)">${a.ip}</td>
    </tr>
  `).join('');
}

 

// ── MODAL ─────────────────────────────────
function showModal(title, body) {
  document.getElementById('actionModalBox').innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border)">
      <h3 style="font-size:1.3rem;font-weight:600">${title}</h3>
      <button onclick="closeModal()" style="background:none;border:none;font-size:18px;color:var(--text-3);cursor:pointer"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <p style="font-size:14px;color:var(--text-2);line-height:1.7;margin-bottom:20px">${body}<br><br><em style="color:var(--text-3);font-size:12px">[In the full system, this shows a complete detail view with full history and action controls.]</em></p>
    <button onclick="closeModal()" style="padding:9px 24px;background:var(--blue-mid);color:white;border:none;border-radius:7px;font-family:'Jost',sans-serif;font-size:13px;font-weight:700;cursor:pointer">Close</button>
  `;
  document.getElementById('actionModal').classList.add('show');
}
function closeModal() { document.getElementById('actionModal').classList.remove('show'); }
document.getElementById('actionModal')?.addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

// ── TOAST ─────────────────────────────────
let toastTimeout;
function showToastAdmin(title, body) {
  let toast = document.getElementById('officialToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'officialToast';
    toast.style.cssText = `position:fixed;bottom:24px;right:24px;background:white;border-radius:12px;padding:16px 20px;box-shadow:0 8px 32px rgba(11,30,69,0.18);border-left:4px solid #059669;display:flex;align-items:flex-start;gap:12px;max-width:380px;z-index:9999;transform:translateY(120px);opacity:0;transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1);`;
    document.body.appendChild(toast);
  }
  toast.innerHTML = `<i class="fa-solid fa-circle-check" style="color:#059669;font-size:20px;margin-top:2px;flex-shrink:0"></i><div><div style="font-size:14px;font-weight:700;color:#0B1E45;margin-bottom:3px">${title}</div><div style="font-size:13px;color:#475569">${body}</div></div>`;
  clearTimeout(toastTimeout);
  requestAnimationFrame(() => { toast.style.transform='translateY(0)'; toast.style.opacity='1'; });
  toastTimeout = setTimeout(() => { toast.style.transform='translateY(120px)'; toast.style.opacity='0'; }, 5000);
}

// ── INIT ──────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initNav();
  restoreActiveSection();
  initSidebarToggle();
  initLogoutConfirmation();
  initResidentTable();
  initDocRequestsTable();
  initComplaintsAdmin();
  initResidentCreateForm();
  initOfficialsSection();
  initAnnouncementForm();
  initServiceForm();
  initDocumentGenerationModal();
  renderAuditLog();
  (async () => {
    try {
      await Promise.all([loadResidents(), loadRequests(), loadComplaints(), loadAnnouncements(), loadServices(), loadAuditLog(), ...(window.IS_OFFICIAL_ADMIN ? [loadOfficials()] : [])]);
      await refreshDashboard();
    } catch (err) {
      showToastAdmin('Data Load Failed', err.message);
    }
  })();
});
