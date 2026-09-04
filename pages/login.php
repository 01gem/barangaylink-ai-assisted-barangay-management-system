<?php
require_once __DIR__ . '/../db.php';
session_start();

$errors = [];
$success = '';
$activeTab = 'resident-login';

function clean_input($value) {
  return trim((string)$value);
}

function verify_user_login($db, $table, $email, $password, &$error) {
  $selectSql = $table === 'barangay_officials'
    ? 'SELECT password FROM barangay_officials WHERE email = ? LIMIT 1'
    : 'SELECT password FROM residents WHERE email = ? LIMIT 1';

  $stmt = $db->prepare($selectSql);
  if (!$stmt) {
    $error = 'Unable to verify login at this time.';
    return false;
  }
  $stmt->bind_param('s', $email);
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
        ? 'UPDATE barangay_officials SET password = ? WHERE email = ?'
        : 'UPDATE residents SET password = ? WHERE email = ?';
      $update = $db->prepare($updateSql);
      if (!$update) {
        $error = 'Password upgrade failed. Please reset your password.';
        return false;
      }
      $update->bind_param('ss', $newHash, $email);
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
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
      $errors[] = 'Please enter your resident email and password.';
    } else {
      $loginError = '';
      if (verify_user_login($db, 'residents', $email, $password, $loginError)) {
        session_regenerate_id(true);
        $_SESSION = [];
        // Load resident details and store in session
        $stmt = $db->prepare('SELECT id, fname, lname, email FROM residents WHERE email = ? LIMIT 1');
        if ($stmt) {
          $stmt->bind_param('s', $email);
          $stmt->execute();
          $stmt->bind_result($rid, $rfname, $rlname, $remail);
          if ($stmt->fetch()) {
            $_SESSION['resident_id'] = (int)$rid;
            $_SESSION['resident_email'] = $remail;
            $_SESSION['resident_name'] = trim($rfname . ' ' . $rlname);
          }
          $stmt->close();
        }
        header('Location: resident.php');
        exit;
      }
      $errors[] = $loginError !== '' ? $loginError : 'Invalid resident email or password.';
    }
  } elseif ($action === 'official-login') {
    $activeTab = 'official-login';
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
      $errors[] = 'Please enter your official email and password.';
    } else {
      $loginError = '';
      if (verify_user_login($db, 'barangay_officials', $email, $password, $loginError)) {
        session_regenerate_id(true);
        $_SESSION = [];
        // Store official session info
        $stmt = $db->prepare('SELECT id, fname, lname, email FROM barangay_officials WHERE email = ? LIMIT 1');
        if ($stmt) {
          $stmt->bind_param('s', $email);
          $stmt->execute();
          $stmt->bind_result($oid, $ofname, $olname, $oemail);
          if ($stmt->fetch()) {
            $_SESSION['official_id'] = (int)$oid;
            $_SESSION['official_email'] = $oemail;
            $_SESSION['official_name'] = trim($ofname . ' ' . $olname);
          }
          $stmt->close();
        }
        header('Location: official.php');
        exit;
      }
      $errors[] = $loginError !== '' ? $loginError : 'Invalid official email or password.';
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
  <link rel="stylesheet" href="../css/login.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet" />
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
              <label>Email Address</label>
              <div class="input-wrap">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="your@email.com" required />
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
              <a href="#" class="forgot-link">Forgot password?</a>
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
              <label>Official Email</label>
              <div class="input-wrap">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="official@barangay.gov.ph" required />
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
              <a href="#" class="forgot-link">Forgot password?</a>
            </div>
            <button type="submit" class="btn-submit">Log In as Official</button>
            <div class="notice-box">
              <i class="fa-solid fa-circle-info"></i>
              This portal is restricted to authorized barangay officials.
            </div>
            <div class="form-footer">
              <p>New official? <a href="official_register.php">Register here</a></p>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>

  <script src="../js/login.js"></script>
</body>
</html>
