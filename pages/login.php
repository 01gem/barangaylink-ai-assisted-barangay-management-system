<?php
require_once __DIR__ . '/../db.php';
session_start();

$errors = [];
$success = '';
$activeTab = 'resident-login';

function clean_input($value) {
  return trim((string)$value);
}

function verify_user_login($db, $table, $identifier, $password, &$error) {
  $selectSql = $table === 'barangay_officials'
    ? 'SELECT password FROM barangay_officials WHERE username = ? LIMIT 1'
    : 'SELECT password FROM residents WHERE username = ? LIMIT 1';

  $stmt = $db->prepare($selectSql);
  if (!$stmt) {
    $error = 'Unable to verify login at this time.';
    return false;
  }
  $stmt->bind_param('s', $identifier);
  $stmt->execute();
  $stored = null;
  $stmt->bind_result($stored);

  if ($stmt->fetch()) {
    $stmt->close();
    if ($stored && password_verify($password, $stored)) {
      return true;
    }
    if ($stored !== null && $stored !== '' && hash_equals($stored, $password)) {
      $newHash = password_hash($password, PASSWORD_DEFAULT);
      $updateSql = $table === 'barangay_officials'
        ? 'UPDATE barangay_officials SET password = ? WHERE username = ?'
        : 'UPDATE residents SET password = ? WHERE username = ?';
      $update = $db->prepare($updateSql);
      if (!$update) {
        $error = 'Password upgrade failed. Please reset your password.';
        return false;
      }
      $update->bind_param('ss', $newHash, $identifier);
      try {
        if (!$update->execute()) {
          $error = 'Password upgrade failed: ' . $update->error;
          $update->close();
          return false;
        }
      } catch (mysqli_sql_exception $ex) {
        $error = 'Password upgrade failed because the password column is too short. Change it to VARCHAR(255) and try again.';
        $update->close();
        return false;
      }
      $update->close();
      return true;
    }
  } else {
    $stmt->close();
  }

  return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $db = get_db();

  if ($action === 'resident-login') {
    $activeTab = 'resident-login';
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
      $errors[] = 'Please enter your resident username and password.';
    } else {
      $loginError = '';
      if (verify_user_login($db, 'residents', $username, $password, $loginError)) {
        session_regenerate_id(true);
        $_SESSION = [];
        // Load resident details and store in session
        $stmt = $db->prepare('SELECT id, fname, lname, username FROM residents WHERE username = ? LIMIT 1');
        if ($stmt) {
          $stmt->bind_param('s', $username);
          $stmt->execute();
          $stmt->bind_result($rid, $rfname, $rlname, $rusername);
          if ($stmt->fetch()) {
            $_SESSION['resident_id'] = (int)$rid;
            $_SESSION['resident_username'] = $rusername;
            $_SESSION['resident_name'] = trim($rfname . ' ' . $rlname);
          }
          $stmt->close();
        }
        header('Location: resident.php');
        exit;
      }
      $errors[] = $loginError !== '' ? $loginError : 'Invalid resident username or password.';
    }
  } elseif ($action === 'official-login') {
    $activeTab = 'official-login';
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
      $errors[] = 'Please enter your official username and password.';
    } else {
      $loginError = '';
      if (verify_user_login($db, 'barangay_officials', $username, $password, $loginError)) {
        $stmt = $db->prepare('SELECT id, fname, lname, role, position, status FROM barangay_officials WHERE username = ? LIMIT 1');
        if ($stmt) {
          $stmt->bind_param('s', $username);
          $stmt->execute();
          $stmt->bind_result($oid, $ofname, $olname, $orole, $oposition, $ostatus);
          $fetched = $stmt->fetch();
          $stmt->close();
          if ($fetched && (string)$ostatus === 'inactive') {
            $errors[] = 'This official account is inactive. Contact a barangay administrator.';
          } elseif ($fetched) {
            session_regenerate_id(true);
            $_SESSION = [];
            $_SESSION['official_id'] = (int)$oid;
            $_SESSION['official_name'] = trim($ofname . ' ' . $olname);
            $_SESSION['official_role'] = (string)$orole;
            $_SESSION['official_position'] = trim((string)$oposition);
            header('Location: official.php');
            exit;
          } else {
            $errors[] = 'Invalid official username or password.';
          }
        } else {
          $errors[] = 'Unable to complete official login at this time.';
        }
      } else {
        $errors[] = $loginError !== '' ? $loginError : 'Invalid official username or password.';
      }
    }
  } else {
    $errors[] = 'Invalid form submission.';
  }
}

function e($value) {
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BarangayLink — Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/login.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

  <div class="auth-layout">

    <!-- ═══ LEFT PANEL ═══ -->
    <div class="auth-left">
      <div class="auth-left-inner">
        <a href="../index.php" class="auth-brand">
          <div class="brand-icon"><i class="fa-solid fa-seedling"></i></div>
          <div>
            <span class="brand-name">BarangayLink</span>
            <span class="brand-loc">Brgy. Sampaguita, Tagana-an, SDN</span>
          </div>
        </a>
        <div class="auth-left-content">
          <div class="al-badge">Official Barangay Portal</div>
          <h1>Welcome to your digital barangay.</h1>
          <p>One platform for certificates, concerns, and community updates — built for residents and barangay staff.</p>

          <div class="al-check-sections" aria-label="What BarangayLink offers">
            <section class="al-check-section">
              <h2 class="al-check-heading">Welcome</h2>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Good day — your barangay services are one secure login away.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Stay connected to Brgy. Sampaguita, Tagana-an, whenever you need us.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>We're glad you're here — residents and partners use the same trusted portal.</span></label>
            </section>
            <section class="al-check-section">
              <h2 class="al-check-heading">Features</h2>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Request barangay certificates and clearances online with clear steps.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>File concerns and follow their status as staff updates your ticket.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Optional SMS and email heads-up for approvals, pickups, and deadlines.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Browse verified local services, programs, and barangay announcements.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Official dashboards for staff to review queues and resident submissions.</span></label>
            </section>
            <section class="al-check-section">
              <h2 class="al-check-heading">What we offer</h2>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Digital intake so fewer trips to the hall for simple transactions.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>A single place to see your request history and uploaded requirements.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Transparent processing aligned with barangay rules and cutoffs.</span></label>
            </section>
            <section class="al-check-section">
              <h2 class="al-check-heading">What we assure</h2>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Careful handling of your information within barangay operations.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Accountability — submissions are logged and traceable by authorized staff.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Fair access: residents use resident login; officials use official login only.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>We keep improving the portal based on community and barangay feedback.</span></label>
            </section>
          </div>
        </div>
        <div class="auth-left-deco" aria-hidden="true">
          <div class="deco-circle c1"></div>
          <div class="deco-circle c2"></div>
          <div class="deco-circle c3"></div>
        </div>
      </div>
    </div>

    <!-- ═══ RIGHT PANEL ═══ -->
    <div class="auth-right">
      <div class="auth-right-inner">

        <?php if ($success): ?>
          <div class="form-alert success">
            <i class="fa-solid fa-circle-check"></i>
            <div><?php echo e($success); ?></div>
          </div>
        <?php endif; ?>
        <?php if ($errors): ?>
          <div class="form-alert error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
              <?php foreach ($errors as $msg): ?>
                <div><?php echo e($msg); ?></div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="auth-tabs">
          <button class="auth-tab <?php echo $activeTab === 'resident-login' ? 'active' : ''; ?>" data-tab="resident-login">Resident Login</button>
          <button class="auth-tab <?php echo $activeTab === 'official-login' ? 'active' : ''; ?>" data-tab="official-login">Official Login</button>
        </div>

        <!-- Resident Login Form -->
        <div class="auth-form-wrap <?php echo $activeTab === 'resident-login' ? 'active' : ''; ?>" id="residentLoginForm">
          <div class="form-header">
            <h2>Resident login</h2>
            <p>Access your resident dashboard and requests</p>
          </div>
          <form class="auth-form" id="residentLoginFormEl" method="post" action="login.php">
            <input type="hidden" name="action" value="resident-login" />
            <div class="field">
              <label>Username</label>
              <div class="input-wrap">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="username" placeholder="Enter your username" required />
              </div>
            </div>
            <div class="field">
              <label>Password</label>
              <div class="input-wrap">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" id="residentLoginPass" placeholder="Enter your password" required />
                <button type="button" class="pass-toggle" onclick="togglePass('residentLoginPass', this)"><i class="fa-solid fa-eye"></i></button>
              </div>
            </div>
            <div class="form-row">
              <label class="checkbox-label">
                <input type="checkbox" /> Remember me
              </label>
              <a href="#" class="forgot-link" data-account-type="resident">Forgot password?</a>
            </div>
            <button type="submit" class="btn-submit">Log In as Resident</button>
            <div class="form-footer">
            </div>
          </form>
        </div>

        <!-- Official Login Form -->
        <div class="auth-form-wrap <?php echo $activeTab === 'official-login' ? 'active' : ''; ?>" id="officialLoginForm">
          <div class="form-header">
            <h2>Official login</h2>
            <p>Barangay officials only</p>
          </div>
          <form class="auth-form" id="officialLoginFormEl" method="post" action="login.php">
            <input type="hidden" name="action" value="official-login" />
            <div class="field">
              <label>Username</label>
              <div class="input-wrap">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="username" placeholder="Enter your username" required />
              </div>
            </div>
            <div class="field">
              <label>Password</label>
              <div class="input-wrap">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" id="officialLoginPass" placeholder="Enter your password" required />
                <button type="button" class="pass-toggle" onclick="togglePass('officialLoginPass', this)"><i class="fa-solid fa-eye"></i></button>
              </div>
            </div>
            <div class="form-row">
              <label class="checkbox-label">
                <input type="checkbox" /> Remember me
              </label>
              <a href="#" class="forgot-link" data-account-type="official">Forgot password?</a>
            </div>
            <button type="submit" class="btn-submit">Log In as Official</button>
            <div class="notice-box">
              <i class="fa-solid fa-circle-info"></i>
              This portal is restricted to authorized barangay officials.
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>

  <div class="reset-overlay" id="resetOverlay" aria-hidden="true">
    <div class="reset-card" role="dialog" aria-modal="true" aria-labelledby="resetTitle">
      <button type="button" class="reset-close" id="resetClose" aria-label="Close">&times;</button>
      <div class="form-header">
        <h2 id="resetTitle">Reset your password</h2>
        <p id="resetAccountLabel">Password recovery</p>
      </div>
      <div id="resetMessage" class="reset-message" role="status"></div>
      <form id="resetRequestForm" class="auth-form">
        <div class="field">
          <label for="resetUsername">Username</label>
          <div class="input-wrap">
            <i class="fa-solid fa-user"></i>
            <input id="resetUsername" type="text" required autocomplete="username" />
          </div>
        </div>
        <button type="submit" class="btn-submit" id="sendResetCodeBtn">Send Code</button>
      </form>
      <form id="resetCodeForm" class="auth-form" style="display:none;">
        <div class="notice-box"><i class="fa-solid fa-clock"></i><span>The code expires in 10 minutes.</span></div>
        <div class="field">
          <label for="resetCode">6-digit code</label>
          <div class="input-wrap"><i class="fa-solid fa-key"></i><input id="resetCode" type="text" inputmode="numeric" maxlength="6" required /></div>
        </div>
        <button type="submit" class="btn-submit" id="verifyResetCodeBtn">Verify Code</button>
        <button type="button" class="reset-resend" id="resendResetCodeBtn">Resend code</button>
      </form>
      <form id="resetPasswordForm" class="auth-form" style="display:none;">
        <div class="field">
          <label for="resetNewPassword">New password</label>
          <div class="input-wrap"><i class="fa-solid fa-lock"></i><input id="resetNewPassword" type="password" required /></div>
        </div>
        <div class="field">
          <label for="resetConfirmPassword">Confirm new password</label>
          <div class="input-wrap"><i class="fa-solid fa-lock"></i><input id="resetConfirmPassword" type="password" required /></div>
        </div>
        <button type="submit" class="btn-submit" id="resetPasswordBtn">Reset Password</button>
      </form>
    </div>
  </div>

  <script src="../js/login.js"></script>
</body>
</html>
