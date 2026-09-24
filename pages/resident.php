<?php
/**
 * BarangayLink - Web-based barangay management system for Brgy. Sampaguita.
 * Copyright (C) 2026 Jun Gem Riege M. Dulduco
 * Licensed under the GNU General Public License v3.0 (or later).
 * See the LICENSE file in the project root for the full text.
 */

require_once __DIR__ . '/../db.php';
session_start();
function e($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
if (empty($_SESSION['resident_id'])) {
  header('Location: login.php');
  exit;
}
$residentId = (int)$_SESSION['resident_id'];
$residentName = $_SESSION['resident_name'] ?? 'Resident User';
$residentUsername = $_SESSION['resident_username'] ?? '';
$residentProfile = [
  'fname' => '', 'lname' => '', 'address' => '', 'contact' => '', 'username' => $residentUsername,
  'profile_photo' => null, 'created_at' => null, 'birthdate' => null, 'civil_status' => null,
  'purok_zone' => null, 'household_size' => null, 'number_of_dependents' => null,
  'is_household_head' => null, 'is_solo_parent' => null, 'is_pwd' => null, 'is_4ps_member' => null,
  'years_of_residency' => null, 'educational_attainment' => null, 'employment_status' => null,
  'occupation' => null, 'monthly_income_bracket' => null, 'skills' => null,
  'work_experience_years' => null, 'training_certifications' => null, 'work_availability' => null,
  'has_drivers_license' => null
];
$db = get_db();
$profileStmt = $db->prepare('SELECT fname, lname, address, contact, username, profile_photo, created_at, birthdate, civil_status, purok_zone, household_size, number_of_dependents, is_household_head, is_solo_parent, is_pwd, is_4ps_member, years_of_residency, educational_attainment, employment_status, occupation, monthly_income_bracket, skills, work_experience_years, training_certifications, work_availability, has_drivers_license FROM residents WHERE id = ? LIMIT 1');
if ($profileStmt) {
  $profileStmt->bind_param('i', $residentId);
  $profileStmt->execute();
  $profileResult = $profileStmt->get_result();
  if ($profileResult && ($profileRow = $profileResult->fetch_assoc())) {
    $residentProfile = array_merge($residentProfile, $profileRow);
    $residentName = trim($profileRow['fname'] . ' ' . $profileRow['lname']);
    $residentUsername = $profileRow['username'];
  }
  $profileStmt->close();
}
$residentParts = preg_split('/\s+/', trim($residentName), -1, PREG_SPLIT_NO_EMPTY);
$residentInitials = count($residentParts) >= 2
  ? strtoupper(substr($residentParts[0], 0, 1) . substr($residentParts[count($residentParts) - 1], 0, 1))
  : strtoupper(substr($residentParts[0] ?? 'R', 0, 2));
$residentPhotoUrl = !empty($residentProfile['profile_photo']) ? '../' . ltrim($residentProfile['profile_photo'], '/') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BarangayLink — Resident Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/resident.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

  <!-- ═══ SIDEBAR ═══ -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="fa-solid fa-seedling"></i></div>
      <div class="sidebar-brand-text">
        <span class="brand-name">BarangayLink</span>
        <span class="brand-loc">Brgy. Sampaguita</span>
      </div>
      <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Hide sidebar" aria-expanded="true">
        <i class="fa-solid fa-angles-left"></i>
      </button>
    </div>
    <div class="sidebar-user">
      <?php if ($residentPhotoUrl !== ''): ?>
        <img class="user-avatar resident-header-photo" src="<?= htmlspecialchars($residentPhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile photo" />
      <?php else: ?>
        <div class="user-avatar"><?= htmlspecialchars($residentInitials, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <div class="user-info">
        <span class="user-name"><?php echo htmlspecialchars($residentName, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="user-role"><i class="fa-solid fa-circle-check"></i> Verified Resident</span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <button class="snav-item" data-tab="announcements">
        <i class="fa-solid fa-bullhorn"></i> <span class="nav-text">Announcements</span>
      </button>
      <button class="snav-item" data-tab="services">
        <i class="fa-solid fa-store"></i> <span class="nav-text">Local Services</span>
      </button>
      <button class="snav-item active" data-tab="dashboard">
        <i class="fa-solid fa-gauge-high"></i> <span class="nav-text">Dashboard</span>
      </button>
      <button class="snav-item" data-tab="requests">
        <i class="fa-solid fa-file-lines"></i> <span class="nav-text">Document Requests</span>
      </button>
      <button class="snav-item" data-tab="complaints">
        <i class="fa-solid fa-triangle-exclamation"></i> <span class="nav-text">Complaints</span>
      </button>
      <button class="snav-item" data-tab="notifications">
        <i class="fa-solid fa-bell"></i> <span class="nav-text">Notifications</span>
      </button>
      <button class="snav-item" data-tab="profile">
        <i class="fa-solid fa-user"></i> <span class="nav-text">My Profile</span>
      </button>
    </nav>
    <div class="sidebar-footer">
      <a href="../logout.php" class="snav-item logout-item">
        <i class="fa-solid fa-arrow-left-from-bracket"></i> <span class="nav-text">Back to Home</span>
      </a>
    </div>
  </aside>

  <!-- ═══ MAIN AREA ═══ -->
  <main class="main-area">
    <header class="topbar">
      <div class="topbar-left">
        <div class="door-plaque door-plaque-dashboard" aria-live="polite">
          <i class="fa-solid fa-house" id="sectionPlaqueIcon" aria-hidden="true"></i>
          <span id="sectionPlaque">Resident Dashboard</span>
        </div>
      </div>
      <div class="topbar-right">
        <button class="topbar-icon-btn" id="notifToggle">
          <i class="fa-solid fa-bell"></i>
          <span class="notif-dot" id="notifDot"></span>
        </button>
        <div class="topbar-user">
          <?php if ($residentPhotoUrl !== ''): ?>
            <img class="tu-avatar resident-header-photo" src="<?= htmlspecialchars($residentPhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile photo" />
          <?php else: ?>
            <div class="tu-avatar"><?= htmlspecialchars($residentInitials, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
          <span><?php echo htmlspecialchars($residentName, ENT_QUOTES, 'UTF-8'); ?></span>
          <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
      </div>
    </header>

    <div class="content-area">

      <!-- ─── ANNOUNCEMENTS TAB ─── -->
      <div class="tab-panel" id="tab-announcements">
        <div class="tab-header"><h2>Announcements</h2></div>
        <div class="community-grid" id="residentAnnouncementsGrid"></div>
      </div>

      <!-- ─── LOCAL SERVICES TAB ─── -->
      <div class="tab-panel" id="tab-services">
        <div class="tab-header"><h2>Local Services</h2></div>
        <div class="filter-row" id="residentServiceFilters"></div>
        <div class="community-grid" id="residentServicesGrid"></div>
      </div>

      <!-- ─── DASHBOARD TAB ─── -->
      <div class="tab-panel active" id="tab-dashboard">
        <div class="welcome-banner">
          <div class="wb-text">
            <h2>Good morning, <em>Resident</em>! 👋</h2>
            <p>You have <strong>0 pending document requests</strong> and <strong>0 active complaints</strong>.</p>
          </div>
          <div class="wb-actions">
            <button class="btn-quick" onclick="switchTab('requests')"><i class="fa-solid fa-file-circle-plus"></i> New Request</button>
            <button class="btn-quick-ghost" onclick="switchTab('complaints')"><i class="fa-solid fa-flag"></i> Submit Complaint</button>
          </div>
        </div>

        <div class="stat-cards" id="dashStats"></div>

        <div class="dash-grid">
          <div class="dash-card" id="recentRequestsCard">
            <div class="dc-head">
              <h3>Recent Requests</h3>
              <button class="dc-see-all" onclick="switchTab('requests')">See all →</button>
            </div>
            <div id="recentRequestsList"></div>
          </div>
          <div class="dash-card" id="recentComplaintsCard">
            <div class="dc-head">
              <h3>Recent Complaints</h3>
              <button class="dc-see-all" onclick="switchTab('complaints')">See all →</button>
            </div>
            <div id="recentComplaintsList"></div>
          </div>
        </div>
      </div>

      <!-- ─── REQUESTS TAB ─── -->
      <div class="tab-panel" id="tab-requests">
        <div class="tab-header">
          <h2>Document Requests</h2>
          <button class="btn-primary-action" id="newReqBtn"><i class="fa-solid fa-plus"></i> New Request</button>
        </div>

        <!-- New Request Form -->
        <div class="card form-card" id="newReqForm" style="display:none;">
          <div class="form-card-header">
            <h3><i class="fa-solid fa-file-circle-plus"></i> New Document Request</h3>
            <button class="close-card-btn" id="closeReqForm"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <form id="reqFormEl">
            <div class="form-3col">
              <div class="field">
                <label>Document Type</label>
                <select class="form-input" required>
                  <option value="" disabled selected>Select document</option>
                  <option>Barangay Clearance</option>
                  <option>Certificate of Residency</option>
                  <option>Certificate of Indigency</option>
                  <option>Certificate of Good Moral Character</option>
                  <option>Business Permit Endorsement</option>
                </select>
              </div>
              <div class="field">
                <label>Purpose</label>
                <select class="form-input" required>
                  <option value="" disabled selected>Select purpose</option>
                  <option>Employment</option>
                  <option>School Enrollment</option>
                  <option>Loan Application</option>
                  <option>Government Requirement</option>
                  <option>Personal Record</option>
                </select>
              </div>
              <div class="field">
                <label>Number of Copies</label>
                <input type="number" class="form-input" min="1" max="5" value="1" />
              </div>
            </div>
            <div class="field" style="margin-top:12px;">
              <label>Additional Notes <span class="opt-label">(optional)</span></label>
              <textarea class="form-input" rows="3" placeholder="Any special instructions for this request..."></textarea>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-submit-form">Submit Request</button>
              <button type="button" class="btn-cancel-form" id="cancelReqForm">Cancel</button>
            </div>
          </form>
        </div>

        <!-- Requests Table -->
        <div class="card">
          <div class="table-toolbar">
            <input type="text" class="table-search" placeholder="Search requests..." id="reqSearch" />
            <select class="table-filter" id="reqFilter">
              <option value="all">All Status</option>
              <option value="pending">Pending</option>
              <option value="processing">Processing</option>
              <option value="ready">Ready for Pickup</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="table-wrap">
            <table class="data-table" id="reqTable">
              <thead>
                <tr>
                  <th>Reference #</th>
                  <th>Document Type</th>
                  <th>Purpose</th>
                  <th>Date Requested</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="reqTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ─── COMPLAINTS TAB ─── -->
      <div class="tab-panel" id="tab-complaints">
        <div class="tab-header">
          <h2>Complaints &amp; Concerns</h2>
          <button class="btn-primary-action" id="newComplaintBtn"><i class="fa-solid fa-plus"></i> New Complaint</button>
        </div>

        <div class="card form-card" id="newComplaintForm" style="display:none;">
          <div class="form-card-header">
            <h3><i class="fa-solid fa-flag"></i> Submit a Complaint or Concern</h3>
            <button class="close-card-btn" id="closeComplaintForm"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <form id="complaintFormEl">
            <div class="form-2col">
              <div class="field">
                <label>Type of Concern</label>
                <select class="form-input" required>
                  <option value="" disabled selected>Select category</option>
                  <option>Road / Infrastructure</option>
                  <option>Noise / Public Disturbance</option>
                  <option>Flooding / Drainage</option>
                  <option>Public Safety</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="field">
                <label>Specific Location</label>
                <input type="text" class="form-input" placeholder="e.g. Corner Mabini & Rizal" required />
              </div>
            </div>
            <div class="field" style="margin-top:12px;">
              <label>Description</label>
              <textarea class="form-input" rows="4" placeholder="Describe the issue in detail..." required></textarea>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-submit-form">Submit Complaint</button>
              <button type="button" class="btn-cancel-form" id="cancelComplaintForm">Cancel</button>
            </div>
          </form>
        </div>

        <div class="card">
          <div class="complaints-list" id="complaintsList"></div>
        </div>
      </div>

      <!-- ─── NOTIFICATIONS TAB ─── -->
      <div class="tab-panel" id="tab-notifications">
        <div class="tab-header">
          <h2>Notifications</h2>
          <button type="button" class="btn-primary-action" id="markAllReadBtn"><i class="fa-solid fa-check-double"></i> Mark all as read</button>
        </div>
        <div class="card">
          <div class="notif-list" id="notifList"></div>
        </div>
      </div>

      <!-- ─── PROFILE TAB ─── -->
      <div class="tab-panel" id="tab-profile">
        <div class="tab-header"><h2>My Profile</h2></div>
        <div class="profile-grid">
          <div class="card profile-card">
            <?php if ($residentPhotoUrl !== ''): ?>
              <img class="profile-avatar-big profile-photo" src="<?= htmlspecialchars($residentPhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile photo" />
            <?php else: ?>
              <div class="profile-avatar-big"><?= htmlspecialchars($residentInitials, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <div class="profile-name-big"><?= htmlspecialchars($residentName, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="profile-role-badge"><i class="fa-solid fa-circle-check"></i> Verified Resident</div>
            <div class="profile-since">Member since — <?= $residentProfile['created_at'] ? e(date('F Y', strtotime($residentProfile['created_at']))) : 'Not provided' ?></div>
            <form id="profilePhotoForm" class="profile-photo-form" enctype="multipart/form-data">
              <label for="profilePhotoInput" class="btn-submit-form">Change Photo</label>
              <input id="profilePhotoInput" type="file" name="photo" accept="image/jpeg,image/png" hidden />
              <small>JPEG or PNG, maximum 2MB</small>
            </form>
          </div>
          <div class="card">
            <h3 class="card-section-title">Personal Information</h3>
            <div class="profile-form">
              <div class="form-2col">
                <div class="field"><label>First Name</label><div class="profile-readonly"><?= htmlspecialchars($residentProfile['fname'], ENT_QUOTES, 'UTF-8') ?></div></div>
                <div class="field"><label>Last Name</label><div class="profile-readonly"><?= htmlspecialchars($residentProfile['lname'], ENT_QUOTES, 'UTF-8') ?></div></div>
              </div>
              <div class="form-2col">
                <div class="field"><label>Username</label><div class="profile-readonly"><?= htmlspecialchars($residentProfile['username'], ENT_QUOTES, 'UTF-8') ?></div></div>
                <div class="field"><label>Mobile</label><div class="profile-readonly"><?= htmlspecialchars($residentProfile['contact'], ENT_QUOTES, 'UTF-8') ?></div></div>
              </div>
              <div class="field"><label>Home Address</label><div class="profile-readonly"><?= htmlspecialchars($residentProfile['address'], ENT_QUOTES, 'UTF-8') ?></div></div>
            </div>
            <h3 class="card-section-title profile-additional-title">Additional Information</h3>
            <div class="profile-form">
              <?php
                $profileFields = [
                  'birthdate' => 'Birthdate', 'civil_status' => 'Civil Status', 'purok_zone' => 'Purok / Zone',
                  'household_size' => 'Household Size', 'number_of_dependents' => 'Dependents',
                  'is_household_head' => 'Household Head', 'is_solo_parent' => 'Solo Parent',
                  'is_pwd' => 'PWD', 'is_4ps_member' => '4Ps Member', 'years_of_residency' => 'Years of Residency',
                  'educational_attainment' => 'Educational Attainment', 'employment_status' => 'Employment Status',
                  'occupation' => 'Occupation', 'monthly_income_bracket' => 'Income Bracket', 'skills' => 'Skills',
                  'work_experience_years' => 'Work Experience (years)', 'training_certifications' => 'Certifications',
                  'work_availability' => 'Work Availability', 'has_drivers_license' => "Driver's License"
                ];
                foreach ($profileFields as $field => $label):
                  $value = $residentProfile[$field] ?? null;
                  if (in_array($field, ['is_household_head', 'is_solo_parent', 'is_pwd', 'is_4ps_member', 'has_drivers_license'], true)) {
                    $value = $value === null ? null : ((int)$value === 1 ? 'Yes' : 'No');
                  }
                  if ($field === 'birthdate' && $value) $value = date('F j, Y', strtotime($value));
                  if ($value === null || $value === '') $value = 'Not provided';
              ?>
                <div class="field"><label><?= e($label) ?></label><div class="profile-readonly"><?= e($value) ?></div></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /content-area -->
  </main>

  <!-- Notification Drawer -->
  <div class="notif-drawer" id="notifDrawer">
    <div class="nd-head">
      <h3>Notifications</h3>
      <button id="closeNotifDrawer"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="notifDrawerList"></div>
  </div>
  <div class="drawer-overlay" id="drawerOverlay"></div>

  <script>
    window.RESIDENT_ID = <?php echo json_encode($residentId); ?>;
    window.RESIDENT_USERNAME = <?php echo json_encode($residentUsername); ?>;
    window.RESIDENT_NAME = <?php echo json_encode($residentName); ?>;
  </script>
  <script src="../js/resident.js"></script>
</body>
</html>
