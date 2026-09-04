<?php
session_start();
if (empty($_SESSION['official_id'])) {
  header('Location: login.php');
  exit;
}
function e($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
$isOfficialAdmin = ($_SESSION['official_role'] ?? '') === 'admin';
$officialName = trim((string)($_SESSION['official_name'] ?? ''));
$officialRole = trim((string)($_SESSION['official_role'] ?? ''));
$officialPosition = trim((string)($_SESSION['official_position'] ?? ''));
$officialRoleLabel = $officialRole === 'admin' ? 'Administrator' : ($officialRole === 'staff' ? 'Staff' : ucwords(str_replace(['_', '-'], ' ', $officialRole)));
$officialSubtitle = $officialPosition !== '' ? $officialPosition : ($officialRoleLabel !== '' ? $officialRoleLabel : 'Official');
$officialInitials = 'O';
$officialNameParts = preg_split('/\s+/', $officialName, -1, PREG_SPLIT_NO_EMPTY);
if (count($officialNameParts) >= 2) {
  $officialInitials = strtoupper(substr($officialNameParts[0], 0, 1) . substr($officialNameParts[count($officialNameParts) - 1], 0, 1));
} elseif (count($officialNameParts) === 1) {
  $officialInitials = strtoupper(substr($officialNameParts[0], 0, 2));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BarangayLink — Official Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/official.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

  <!-- ═══ SIDEBAR ═══ -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="fa-solid fa-seedling"></i></div>
      <div class="sidebar-brand-text">
        <span class="brand-name">BarangayLink</span>
        <span class="brand-loc">Admin / Official Panel</span>
      </div>
      <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Hide sidebar" aria-expanded="true">
        <i class="fa-solid fa-angles-left"></i>
      </button>
    </div>
    <div class="sidebar-user">
      <div class="user-avatar"><?= e($officialInitials) ?></div>
      <div class="user-info">
        <span class="user-name"><?= e($officialName !== '' ? $officialName : 'Official') ?></span>
        <span class="user-role official"><i class="fa-solid fa-shield-halved"></i> <?= e($officialSubtitle) ?></span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-group-label">Main</div>
      <button class="snav-item active" data-tab="dashboard"><i class="fa-solid fa-gauge-high"></i> <span class="nav-text">Dashboard</span></button>
      <div class="nav-group-label">Management</div>
      <button class="snav-item" data-tab="residents"><i class="fa-solid fa-users"></i> <span class="nav-text">Residents</span></button>
      <button class="snav-item" data-tab="requests"><i class="fa-solid fa-file-lines"></i> <span class="nav-text">Document Requests</span></button>
      <button class="snav-item" data-tab="complaints"><i class="fa-solid fa-triangle-exclamation"></i> <span class="nav-text">Complaints</span></button>
      <div class="nav-group-label">Community</div>
      <button class="snav-item" data-tab="announcements"><i class="fa-solid fa-bullhorn"></i> <span class="nav-text">Announcements</span></button>
      <button class="snav-item" data-tab="services"><i class="fa-solid fa-store"></i> <span class="nav-text">Local Services</span></button>
      <div class="nav-group-label">System</div>
      <?php if ($isOfficialAdmin): ?>
      <button class="snav-item" data-tab="officials"><i class="fa-solid fa-user-shield"></i> <span class="nav-text">Manage Officials</span></button>
      <?php endif; ?>
      <button class="snav-item" data-tab="auditlog"><i class="fa-solid fa-scroll"></i> <span class="nav-text">Audit Log</span></button>
    </nav>
    <div class="sidebar-footer">
      <a href="../logout.php" class="snav-item logout-item"><i class="fa-solid fa-arrow-left-from-bracket"></i> <span class="nav-text">Back to Home</span></a>
    </div>
  </aside>

  <!-- ═══ MAIN ═══ -->
  <main class="main-area">
    <header class="topbar">
      <div class="topbar-left">
        <h1 class="page-title" id="pageTitle">Dashboard</h1>
      </div>
      <div class="topbar-right">
        <div class="official-chip"><i class="fa-solid fa-shield-halved"></i> Official Access</div>
        <div class="topbar-user">
          <div class="tu-avatar"><?= e($officialInitials) ?></div>
          <span><?= e($officialName !== '' ? $officialName : 'Official') ?></span>
          <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
      </div>
    </header>

    <div class="content-area">

      <!-- ─── DASHBOARD ─── -->
      <div class="tab-panel active" id="tab-dashboard">
        <div class="stat-cards" id="dashStats"></div>
        <div class="dash-row">
          <div class="card dash-card" id="pendingRequestsCard">
            <div class="dc-head">
              <h3><i class="fa-solid fa-file-clock"></i> Pending Document Requests</h3>
              <button class="dc-more-btn" onclick="switchTab('requests')">View all →</button>
            </div>
            <div id="dashPendingReqs"></div>
          </div>
          <div class="card dash-card">
            <div class="dc-head">
              <h3><i class="fa-solid fa-triangle-exclamation"></i> Open Complaints</h3>
              <button class="dc-more-btn" onclick="switchTab('complaints')">View all →</button>
            </div>
            <div id="dashOpenComplaints"></div>
          </div>
        </div>
        <div class="card recent-activity-card">
          <div class="dc-head"><h3><i class="fa-solid fa-clock-rotate-left"></i> Recent System Activity</h3></div>
          <div id="dashActivity"></div>
        </div>
      </div>

      <!-- ─── RESIDENTS ─── -->
      <div class="tab-panel" id="tab-residents">
        <div class="tab-header">
          <h2>Resident Management</h2>
          <button class="btn-primary-action" id="addResidentBtn"><i class="fa-solid fa-plus"></i> Add Resident</button>
        </div>
        <div class="card form-card" id="residentFormCard" style="display:none;">
          <div class="form-card-header">
            <h3 id="residentFormTitle"><i class="fa-solid fa-user-plus"></i> Add New Resident</h3>
            <button class="close-card-btn" id="closeResidentForm"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <form id="residentFormEl">
            <input type="hidden" name="resident_id" />
            <div class="form-2col">
              <div class="field"><label>First Name</label><input type="text" name="fname" class="form-input" required /></div>
              <div class="field"><label>Last Name</label><input type="text" name="lname" class="form-input" required /></div>
            </div>
            <div class="form-2col">
              <div class="field"><label>Contact</label><input type="text" name="contact" class="form-input" required /></div>
              <div class="field"><label>Username</label><input type="text" name="username" class="form-input" required /></div>
            </div>
            <div class="field"><label>Address</label><input type="text" name="address" class="form-input" required /></div>
            <div class="field" style="margin-top:12px;"><label id="residentPasswordLabel">Temporary Password</label><input type="password" name="password" class="form-input" required /></div>
            <div class="form-actions">
              <button type="submit" class="btn-submit-form" id="residentFormSubmitBtn">Save Resident</button>
              <button type="button" class="btn-cancel-form" id="cancelResidentForm">Cancel</button>
            </div>
          </form>
        </div>
        <div class="card">
          <div class="table-toolbar">
            <input type="text" class="table-search" id="residentSearch" placeholder="Search by name, address…" />
            <select class="table-filter" id="residentStatusFilter">
              <option value="all">All Status</option>
              <option value="verified">Verified</option>
              <option value="pending">Pending</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
          <div class="table-wrap">
            <table class="data-table" id="residentsTable">
              <thead><tr><th>Resident ID</th><th>Full Name</th><th>Address</th><th>Contact</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="residentsBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ─── DOCUMENT REQUESTS ─── -->
      <div class="tab-panel" id="tab-requests">
        <div class="tab-header"><h2>Document Requests</h2></div>
        <div class="card">
          <div class="table-toolbar">
            <input type="text" class="table-search" id="reqSearch" placeholder="Search by name, ref #…" />
            <select class="table-filter" id="reqStatusFilter">
              <option value="all">All Status</option>
              <option value="pending">Pending</option>
              <option value="processing">Processing</option>
              <option value="ready">Ready</option>
              <option value="completed">Completed</option>
            </select>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>Ref #</th><th>Resident</th><th>Document Type</th><th>Purpose</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
              <tbody id="reqsBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ─── COMPLAINTS ─── -->
      <div class="tab-panel" id="tab-complaints">
        <div class="tab-header"><h2>Complaints &amp; Concerns</h2></div>
        <div class="card">
          <div class="table-toolbar">
            <input type="text" class="table-search" id="compSearch" placeholder="Search complaints…" />
            <select class="table-filter" id="compStatusFilter">
              <option value="all">All</option>
              <option value="open">Open</option>
              <option value="investigating">Investigating</option>
              <option value="resolved">Resolved</option>
            </select>
          </div>
          <div id="complaintsAdminList"></div>
        </div>
      </div>

      <!-- ─── ANNOUNCEMENTS ─── -->
      <div class="tab-panel" id="tab-announcements">
        <div class="tab-header">
          <h2>Announcements</h2>
          <button class="btn-primary-action" id="newAnnBtn"><i class="fa-solid fa-plus"></i> Post Announcement</button>
        </div>
        <div class="card form-card" id="annForm" style="display:none;">
          <div class="form-card-header">
            <h3><i class="fa-solid fa-bullhorn"></i> New Announcement</h3>
            <button class="close-card-btn" id="closeAnnForm"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <form id="annFormEl">
            <div class="form-2col">
              <div class="field"><label>Title</label><input type="text" class="form-input" placeholder="Announcement title" required /></div>
              <div class="field"><label>Category</label>
                <select class="form-input">
                  <option>General</option><option>Health</option><option>Environment</option><option>Safety</option><option>Event</option>
                </select>
              </div>
            </div>
            <div class="field" style="margin-top:12px;"><label>Content</label><textarea class="form-input" rows="5" placeholder="Write the announcement content here…" required></textarea></div>
            <div class="field" style="margin-top:8px;">
              <label>Send SMS Notification to Residents? <span class="opt-label">(via httpSMS)</span></label>
              <label class="toggle-label"><input type="checkbox" id="smsToggle" /> <span class="toggle-switch"></span> <span>Yes, send SMS to all registered residents</span></label>
            </div>
            <div class="form-actions"><button type="submit" class="btn-submit-form">Post Announcement</button><button type="button" class="btn-cancel-form" id="cancelAnnForm">Cancel</button></div>
          </form>
        </div>
        <div class="card">
          <div id="announcementsAdminList"></div>
        </div>
      </div>

      

      <!-- ─── LOCAL SERVICES ─── -->
      <div class="tab-panel" id="tab-services">
        <div class="tab-header">
          <h2>Local Services Directory</h2>
          <button class="btn-primary-action" id="newServiceBtn"><i class="fa-solid fa-plus"></i> Add Service</button>
        </div>
        <div class="card form-card" id="serviceForm" style="display:none;">
          <div class="form-card-header">
            <h3><i class="fa-solid fa-store"></i> Local Service</h3>
            <button class="close-card-btn" id="closeServiceForm"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <form id="serviceFormEl">
            <input type="hidden" name="id" />
            <div class="form-2col">
              <div class="field"><label>Service Name</label><input name="service_name" type="text" class="form-input" required /></div>
              <div class="field"><label>Category</label><input name="category" type="text" class="form-input" placeholder="e.g. Food, Retail, Services" required /></div>
            </div>
            <div class="form-2col" style="margin-top:12px;">
              <div class="field"><label>Contact Number</label><input name="contact_number" type="text" class="form-input" /></div>
              <div class="field"><label>Address</label><input name="address" type="text" class="form-input" /></div>
            </div>
            <div class="field" style="margin-top:12px;"><label>Operating Hours</label><input name="operating_hours" type="text" class="form-input" /></div>
            <div class="field" style="margin-top:12px;"><label>Description</label><textarea name="description" class="form-input" rows="4"></textarea></div>
            <div class="form-actions"><button type="submit" class="btn-submit-form">Save Service</button><button type="button" class="btn-cancel-form" id="cancelServiceForm">Cancel</button></div>
          </form>
        </div>
        <div class="services-admin-grid" id="servicesAdminGrid"></div>
      </div>

      <?php if ($isOfficialAdmin): ?>
      <!-- ─── MANAGE OFFICIALS ─── -->
      <div class="tab-panel" id="tab-officials">
        <div class="tab-header">
          <h2>Manage Officials</h2>
          <button class="btn-primary-action" id="addOfficialBtn"><i class="fa-solid fa-plus"></i> Add Official</button>
        </div>
        <div class="card form-card" id="officialFormCard" style="display:none;">
          <div class="form-card-header">
            <h3 id="officialFormTitle"><i class="fa-solid fa-user-plus"></i> Add New Official</h3>
            <button class="close-card-btn" id="closeOfficialForm"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <form id="officialFormEl">
            <input type="hidden" name="official_id" />
            <div class="form-2col">
              <div class="field"><label>First Name</label><input type="text" name="fname" class="form-input" required /></div>
              <div class="field"><label>Last Name</label><input type="text" name="lname" class="form-input" required /></div>
            </div>
            <div class="form-2col">
              <div class="field"><label>Contact</label><input type="text" name="contact" class="form-input" required /></div>
              <div class="field"><label>Username</label><input type="text" name="username" class="form-input" required /></div>
            </div>
            <div class="field"><label>Address</label><input type="text" name="address" class="form-input" required /></div>
            <div class="form-2col" style="margin-top:12px;">
              <div class="field"><label>Position</label><input type="text" name="position" class="form-input" required /></div>
              <div class="field"><label>Role</label>
                <select name="role" class="form-input" required>
                  <option value="staff">Staff</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
            </div>
            <div class="field" style="margin-top:12px;"><label id="officialPasswordLabel">Temporary Password</label><input type="password" name="password" class="form-input" required /></div>
            <div class="form-actions">
              <button type="submit" class="btn-submit-form" id="officialFormSubmitBtn">Save Official</button>
              <button type="button" class="btn-cancel-form" id="cancelOfficialForm">Cancel</button>
            </div>
          </form>
        </div>
        <div class="card">
          <div class="table-toolbar">
            <input type="text" class="table-search" id="officialSearch" placeholder="Search by name, username, position…" />
            <select class="table-filter" id="officialStatusFilter">
              <option value="all">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="table-wrap">
            <table class="data-table" id="officialsTable">
              <thead><tr><th>ID</th><th>Full Name</th><th>Username</th><th>Contact</th><th>Position</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="officialsBody"></tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- ─── AUDIT LOG ─── -->
      <div class="tab-panel" id="tab-auditlog">
        <div class="tab-header"><h2>Audit Log</h2></div>
        <div class="card">
          <div class="table-toolbar">
            <input type="text" class="table-search" placeholder="Search by user or action…" />
            <select class="table-filter">
              <option value="all">All Actions</option>
              <option>Login</option><option>Request Update</option><option>Complaint Update</option><option>Announcement Post</option><option>Resident Verification</option>
            </select>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>Timestamp</th><th>User</th><th>Role</th><th>Action</th><th>Module</th><th>IP Address</th></tr></thead>
              <tbody id="auditBody"></tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- Action Modal -->
  <div class="modal-overlay" id="actionModal">
    <div class="modal-box" id="actionModalBox"></div>
  </div>

  <!-- Document Generation Modal -->
  <div class="modal-overlay" id="docGenModal">
    <div class="modal-box docgen-modal-box">
      <div class="docgen-modal-head">
        <h3 id="docGenTitle" class="docgen-modal-title">Generate Document</h3>
        <button type="button" class="close-card-btn" id="closeDocGenModal"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div id="docGenMeta" class="docgen-modal-meta"></div>
      <div class="docgen-modal-grid">
        <div class="docgen-preview-col">
          <h4 class="docgen-preview-title">PDF Preview</h4>
          <div id="docGenPreviewEmpty" class="docgen-preview-empty">Generate a document to see its PDF preview here.</div>
          <div id="docGenPreviewWrap" class="docgen-preview-wrap" style="display:none;">
            <iframe id="docGenPreviewFrame" title="Generated document preview" class="docgen-preview-frame"></iframe>
          </div>
        </div>
        <div class="docgen-form-col">
          <div class="form-2col" id="docGenFields"></div>
          <div class="docgen-actions">
            <button type="button" class="btn-cancel-form" id="cancelDocGenModal">Close</button>
            <button type="button" class="btn-submit-form" id="generateDocBtn">Generate &amp; Preview</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    window.IS_OFFICIAL_ADMIN = <?php echo $isOfficialAdmin ? 'true' : 'false'; ?>;
    window.OFFICIAL_ID = <?php echo (int)$_SESSION['official_id']; ?>;
  </script>
  <script src="../js/official.js"></script>
</body>
</html>
