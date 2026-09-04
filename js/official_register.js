/**
 * Official Registration Form Validation & UX
 */

function togglePass(fieldId, btn) {
  const field = document.getElementById(fieldId);
  const icon = btn.querySelector('i');
  
  if (field.type === 'password') {
    field.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    field.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

/**
 * Password strength indicator
 */
function updatePasswordStrength() {
  const passwordField = document.getElementById('passwordField');
  const strengthIndicator = document.getElementById('passStrength');
  const password = passwordField.value;

  if (!password) {
    strengthIndicator.classList.remove('active', 'weak', 'medium', 'strong');
    return;
  }

  let strength = 0;
  const hasLower = /[a-z]/.test(password);
  const hasUpper = /[A-Z]/.test(password);
  const hasNumber = /[0-9]/.test(password);
  const hasSpecial = /[!@#$%^&*]/.test(password);
  const length = password.length;

  if (hasLower) strength++;
  if (hasUpper) strength++;
  if (hasNumber) strength++;
  if (hasSpecial) strength++;
  if (length >= 12) strength++;

  strengthIndicator.classList.add('active');

  if (strength <= 2) {
    strengthIndicator.textContent = '⚠ Weak — Add uppercase, numbers, and length';
    strengthIndicator.classList.remove('medium', 'strong');
    strengthIndicator.classList.add('weak');
  } else if (strength <= 3) {
    strengthIndicator.textContent = '◐ Fair — Almost there, add special characters';
    strengthIndicator.classList.remove('weak', 'strong');
    strengthIndicator.classList.add('medium');
  } else {
    strengthIndicator.textContent = '✓ Strong — Good password!';
    strengthIndicator.classList.remove('weak', 'medium');
    strengthIndicator.classList.add('strong');
  }
}

/**
 * Form submission validation (client-side)
 */
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('officialRegisterForm');
  const passwordField = document.getElementById('passwordField');

  // Password strength on input
  if (passwordField) {
    passwordField.addEventListener('input', updatePasswordStrength);
  }

  // Form validation
  if (form) {
    form.addEventListener('submit', function(e) {
      const fname = form.querySelector('input[name="fname"]').value.trim();
      const lname = form.querySelector('input[name="lname"]').value.trim();
      const address = form.querySelector('input[name="address"]').value.trim();
      const contact = form.querySelector('input[name="contact"]').value.trim();
      const email = form.querySelector('input[name="email"]').value.trim();
      const position = form.querySelector('select[name="position"]').value;
      const password = form.querySelector('input[name="password"]').value;
      const confirmPassword = form.querySelector('input[name="confirm_password"]').value;

      // Client-side validation
      if (!fname || fname.length < 2) {
        alert('First name must be at least 2 characters.');
        e.preventDefault();
        return false;
      }

      if (!lname || lname.length < 2) {
        alert('Last name must be at least 2 characters.');
        e.preventDefault();
        return false;
      }

      if (!address || address.length < 10) {
        alert('Address must be at least 10 characters.');
        e.preventDefault();
        return false;
      }

      if (!contact || contact.length < 10) {
        alert('Contact number must be at least 10 digits.');
        e.preventDefault();
        return false;
      }

      if (!email || !validateEmail(email)) {
        alert('Please enter a valid email address.');
        e.preventDefault();
        return false;
      }

      if (!position) {
        alert('Please select a position.');
        e.preventDefault();
        return false;
      }

      if (!password || password.length < 8) {
        alert('Password must be at least 8 characters.');
        e.preventDefault();
        return false;
      }

      if (!/[a-z]/.test(password) || !/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
        alert('Password must contain uppercase, lowercase, and numbers.');
        e.preventDefault();
        return false;
      }

      if (password !== confirmPassword) {
        alert('Passwords do not match.');
        e.preventDefault();
        return false;
      }

      // All validation passed
      return true;
    });
  }
});

/**
 * Email validation helper
 */
function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}
