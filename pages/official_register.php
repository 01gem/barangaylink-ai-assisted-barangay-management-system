<?php
require_once __DIR__ . '/../db.php';

$errors = [];
$success = '';
$formData = [];

function clean_input($value) {
  return trim((string)$value);
}

function validate_email($email) {
  return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_password($password) {
  if (strlen($password) < 8) {
    return 'Password must be at least 8 characters long.';
  }
  if (!preg_match('/[a-z]/', $password)) {
    return 'Password must contain at least one lowercase letter.';
  }
  if (!preg_match('/[A-Z]/', $password)) {
    return 'Password must contain at least one uppercase letter.';
  }
  if (!preg_match('/[0-9]/', $password)) {
    return 'Password must contain at least one number.';
  }
  return '';
}

function email_exists($db, $email) {
  $stmt = $db->prepare('SELECT id FROM barangay_officials WHERE email = ? LIMIT 1');
  if (!$stmt) {
    return null;
  }
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $result = $stmt->get_result();
  $exists = $result->num_rows > 0;
  $stmt->close();
  return $exists;
}

function register_official($db, $fname, $lname, $address, $contact, $email, $password, $position) {
  $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
  
  $stmt = $db->prepare(
    'INSERT INTO barangay_officials (fname, lname, address, contact, email, password, position) 
     VALUES (?, ?, ?, ?, ?, ?, ?)'
  );
  
  if (!$stmt) {
    return 'Database error: ' . $db->error;
  }
  
  $stmt->bind_param('sssssss', $fname, $lname, $address, $contact, $email, $hashedPassword, $position);
  
  if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();
    return 'Registration failed: ' . $error;
  }
  
  $stmt->close();
  return true;
}

$positions = [
  'Barangay Captain',
  'Barangay Vice Captain',
  'Kagawad (Councilor)',
  'Barangay Secretary',
  'Barangay Treasurer',
  'Health Officer',
  'Peace Officer (Barangay Tanod)',
  'SK (Sangguniang Kabataan) Chairperson'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $db = get_db();
  
  $fname = clean_input($_POST['fname'] ?? '');
  $lname = clean_input($_POST['lname'] ?? '');
  $address = clean_input($_POST['address'] ?? '');
  $contact = clean_input($_POST['contact'] ?? '');
  $email = clean_input($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirm_password'] ?? '';
  $position = clean_input($_POST['position'] ?? '');
  
  $formData = compact('fname', 'lname', 'address', 'contact', 'email', 'position');
  
  // Validation
  if ($fname === '' || strlen($fname) < 2 || strlen($fname) > 50) {
    $errors[] = 'First name must be between 2 and 50 characters.';
  }
  
  if ($lname === '' || strlen($lname) < 2 || strlen($lname) > 50) {
    $errors[] = 'Last name must be between 2 and 50 characters.';
  }
  
  if ($address === '' || strlen($address) < 10 || strlen($address) > 100) {
    $errors[] = 'Address must be between 10 and 100 characters.';
  }
  
  if ($contact === '' || strlen($contact) < 10) {
    $errors[] = 'Contact number must be at least 10 digits.';
  }
  
  if ($email === '' || !validate_email($email)) {
    $errors[] = 'Please enter a valid email address.';
  } elseif (email_exists($db, $email)) {
    $errors[] = 'This email is already registered.';
  }
  
  if ($position === '' || !in_array($position, $positions)) {
    $errors[] = 'Please select a valid position.';
  }
  
  if ($password === '') {
    $errors[] = 'Password is required.';
  } else {
    $passValidation = validate_password($password);
    if ($passValidation !== '') {
      $errors[] = $passValidation;
    }
  }
  
  if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
  }
  
  // Register if no errors
  if (empty($errors)) {
    $result = register_official($db, $fname, $lname, $address, $contact, $email, $password, $position);
    if ($result === true) {
      $success = 'Registration successful! Redirecting to login...';
      header('Refresh: 3; url=login.php');
    } else {
      $errors[] = $result;
    }
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
  <title>BarangayLink — Official Registration</title>
  <link rel="stylesheet" href="../css/official_register.css" />
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
          <div class="al-badge">Official Registration</div>
          <h1>Join the Barangay Leadership.</h1>
          <p>Register as a barangay official to access the admin dashboard, manage documents, respond to complaints, and serve your community more effectively.</p>

          <div class="al-check-sections" aria-label="Official benefits">
            <section class="al-check-section">
              <h2 class="al-check-heading">Official Access</h2>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Dedicated barangay official dashboard.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Manage resident profiles and verify accounts.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Process document requests efficiently.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Review and respond to community complaints.</span></label>
            </section>
            <section class="al-check-section">
              <h2 class="al-check-heading">Administrative Features</h2>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Post announcements to the community.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>Manage verified local services directory.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>View audit logs and system activity.</span></label>
              <label class="al-check-row"><input type="checkbox" checked disabled /><span>SMS notification integration for urgent updates.</span></label>
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

        <div class="form-header">
          <h2>Official Registration</h2>
          <p>Create your barangay official account</p>
        </div>

        <form class="auth-form" id="officialRegisterForm" method="post" action="official_register.php">
          
          <div class="form-2col">
            <div class="field">
              <label>First Name *</label>
              <div class="input-wrap">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="fname" placeholder="Juan" value="<?php echo e($formData['fname'] ?? ''); ?>" required />
              </div>
            </div>
            <div class="field">
              <label>Last Name *</label>
              <div class="input-wrap">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="lname" placeholder="De la Cruz" value="<?php echo e($formData['lname'] ?? ''); ?>" required />
              </div>
            </div>
          </div>

          <div class="field">
            <label>Position *</label>
            <div class="input-wrap select-wrap">
              <i class="fa-solid fa-briefcase"></i>
              <select name="position" required>
                <option value="">-- Select Position --</option>
                <?php foreach ($positions as $pos): ?>
                  <option value="<?php echo e($pos); ?>" <?php echo ($formData['position'] ?? '') === $pos ? 'selected' : ''; ?>>
                    <?php echo e($pos); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="field">
            <label>Address *</label>
            <div class="input-wrap">
              <i class="fa-solid fa-location-dot"></i>
              <input type="text" name="address" placeholder="123 Barangay Street, Brgy. Sampaguita" value="<?php echo e($formData['address'] ?? ''); ?>" required />
            </div>
          </div>

          <div class="form-2col">
            <div class="field">
              <label>Contact Number *</label>
              <div class="input-wrap">
                <i class="fa-solid fa-phone"></i>
                <input type="tel" name="contact" placeholder="+63 9xx xxx xxxx" value="<?php echo e($formData['contact'] ?? ''); ?>" required />
              </div>
            </div>
            <div class="field">
              <label>Email Address *</label>
              <div class="input-wrap">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="official@barangay.gov.ph" value="<?php echo e($formData['email'] ?? ''); ?>" required />
              </div>
            </div>
          </div>

          <div class="field">
            <label>Password *</label>
            <div class="input-wrap">
              <i class="fa-solid fa-lock"></i>
              <input type="password" name="password" id="passwordField" placeholder="Min. 8 chars (uppercase, lowercase, number)" required />
              <button type="button" class="pass-toggle" onclick="togglePass('passwordField', this)"><i class="fa-solid fa-eye"></i></button>
            </div>
            <div class="pass-strength" id="passStrength"></div>
          </div>

          <div class="field">
            <label>Confirm Password *</label>
            <div class="input-wrap">
              <i class="fa-solid fa-lock"></i>
              <input type="password" name="confirm_password" id="confirmPasswordField" placeholder="Re-enter your password" required />
              <button type="button" class="pass-toggle" onclick="togglePass('confirmPasswordField', this)"><i class="fa-solid fa-eye"></i></button>
            </div>
          </div>

          <div class="notice-box">
            <i class="fa-solid fa-shield-halved"></i>
            Your information will be securely stored. Authorized barangay access only.
          </div>

          <button type="submit" class="btn-submit">Register as Official</button>

          <div class="form-footer">
            <p>Already have an account? <a href="login.php">Log in here</a></p>
          </div>
        </form>

      </div>
    </div>
  </div>

  <script src="../js/official_register.js"></script>
</body>
</html>
