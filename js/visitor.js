/* ─────────────────────────────────────────
   BARANGAYLINK — VISITOR.JS
───────────────────────────────────────── */

// ── ANNOUNCEMENTS ─────────────────────────
let ANNOUNCEMENTS = [];

// Get announcements
function getAllAnnouncements() {
  return ANNOUNCEMENTS;
}

let SERVICES = [];

async function loadLandingStats() {
  try {
    const response = await fetch('api/stats/landing.php');
    const data = await response.json();
    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Unable to load landing statistics.');
    }
    const values = {
      registered_residents: `${data.registered_residents}`,
      verified_local_services: `${data.verified_local_services}`,
      request_fulfillment_rate: `${data.request_fulfillment_rate}%`,
      avg_response_time_minutes: `${data.avg_response_time_minutes} min`
    };
    document.querySelectorAll('[data-landing-stat]').forEach((element) => {
      const key = element.dataset.landingStat;
      if (Object.prototype.hasOwnProperty.call(values, key)) {
        element.textContent = values[key];
      }
    });
  } catch (err) {
    // Keep the existing static placeholders if the public stats request fails.
  }
}

// ── RENDER ANNOUNCEMENTS ──────────────────
function renderAnnouncements() {
  const grid = document.getElementById('announceGrid');
  if (!grid) return;
  const allAnnouncements = getAllAnnouncements();
  if (!allAnnouncements.length) {
    grid.innerHTML = '<p style="color:#64748B">No announcements have been posted yet.</p>';
    return;
  }
  grid.innerHTML = allAnnouncements.slice(0, 6).map(a => `
    <div class="announce-card" onclick="">
      <div class="ac-meta">
        ${Number(a.is_pinned) === 1 ? '<span class="ac-cat info">Pinned</span>' : ''}
        <span class="ac-date"><i class="fa-regular fa-calendar"></i> ${formatAnnouncementDate(a.created_at)}</span>
      </div>
      <div class="ac-title">${a.title}</div>
      <div class="ac-excerpt">${a.content}</div>
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
  try {
    const response = await fetch('api/announcements/list.php');
    const data = await response.json();
    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Unable to load announcements.');
    }
    ANNOUNCEMENTS = data.announcements || [];
  } catch (err) {
    ANNOUNCEMENTS = [];
  }
  renderAnnouncements();
}

// ── RENDER SERVICES ───────────────────────
function renderServices(filter = 'all') {
  const grid = document.getElementById('servicesGrid');
  if (!grid) return;
  const filtered = filter === 'all' ? SERVICES : SERVICES.filter(s => s.category.toLowerCase() === filter);
  if (!filtered.length) {
    grid.innerHTML = '<p style="color:#64748B">No local services have been added yet.</p>';
    return;
  }
  grid.innerHTML = filtered.map(s => `
    <div class="service-card">
      <div class="sc-head">
        <div class="sc-icon" style="background:#DBEAFE; font-size:22px;">🏪</div>
        <div class="sc-info">
          <div class="sc-name">${s.service_name}</div>
          <div class="sc-cat">${s.category}</div>
        </div>
      </div>
      <div class="sc-verified"><i class="fa-solid fa-circle-check"></i> Barangay Verified</div>
      <div class="sc-desc">${s.description || 'No description provided.'}</div>
      <div class="sc-footer">
        <span class="sc-addr"><i class="fa-solid fa-location-dot"></i> ${s.address || 'Address not provided'}</span>
        <span class="sc-rating"><i class="fa-solid fa-phone"></i> ${s.contact_number || 'Contact not provided'}</span>
      </div>
      ${s.operating_hours ? `<div class="sc-footer"><span class="sc-addr"><i class="fa-regular fa-clock"></i> ${s.operating_hours}</span></div>` : ''}
    </div>
  `).join('');
}

async function loadServices() {
  try {
    const response = await fetch('api/services/list.php');
    const data = await response.json();
    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Unable to load local services.');
    }
    SERVICES = data.services || [];
  } catch (err) {
    SERVICES = [];
  }
  initFilters();
  renderServices('all');
}

// ── FILTER BUTTONS ─────────────────────────
function initFilters() {
  const row = document.getElementById('filterRow');
  if (!row) return;
  const filters = ['All', ...new Set(SERVICES.map(s => s.category).filter(Boolean))];
  row.innerHTML = filters.map((f, i) => `
    <button class="filter-btn ${i === 0 ? 'active' : ''}" data-filter="${f.toLowerCase()}">${f}</button>
  `).join('');
  row.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      row.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      renderServices(btn.dataset.filter);
    });
  });
}

// ── NAVBAR SCROLL ──────────────────────────
function initNavbar() {
  const nav = document.getElementById('navbar');
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 40);
  });
}

// ── SMOOTH NAV LINKS ───────────────────────
function initNavLinks() {
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', e => {
      const target = link.getAttribute('href');
      if (target && target.startsWith('#')) {
        e.preventDefault();
        document.querySelector(target)?.scrollIntoView({ behavior: 'smooth' });
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
      }
    });
  });
}

// ── INIT ───────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadLandingStats();
  loadAnnouncements();
  loadServices();
  initNavbar();
  initNavLinks();
});
