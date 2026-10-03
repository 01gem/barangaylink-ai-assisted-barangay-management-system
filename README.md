<div align="center">

# 🌱 BarangayLink

### Web-Based Barangay Management System

<img src="https://readme-typing-svg.demolab.com?font=Inter&weight=600&size=20&duration=3200&pause=900&color=2563EB&center=true&vCenter=true&width=760&lines=Digital+services+for+Brgy.+Sampaguita;Resident+requests%2C+complaints%2C+and+community+updates;Secure+official+workflows+with+real+document+generation;AI-assisted+vulnerability+registry+and+calamity+triage" alt="Animated BarangayLink description" />

<p>
  <img src="https://img.shields.io/badge/status-localhost%20%2F%20LAN-2563EB?style=for-the-badge" alt="Localhost and LAN deployment" />
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.x" />
  <img src="https://img.shields.io/badge/MySQL-database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL database" />
  <img src="https://img.shields.io/badge/Vanilla-JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=111827" alt="Vanilla JavaScript" />
</p>

<p><strong>📍 Brgy. Sampaguita, Tagana-an, SDN</strong></p>

</div>

A capstone project: a web-based information system for barangay operations, covering
document requests (with real Word/PDF generation), complaint management, resident
records, public announcements, a local services directory, SMS notifications, an
AI-assisted analyst suite, and role-based staff/admin access.

> **Status:** Localhost/LAN deployment only. Not configured for production/public
> hosting as-is (see [Security Notes](#security-notes) below).

<div align="center">

| 👥 Residents | 📄 Documents | 📣 Announcements | 📱 SMS | 🤖 AI Gateway |
|:---:|:---:|:---:|:---:|:---:|
| Portal access | Word/PDF generation | Public updates | OTP & alerts | Gemini AI |

</div>

---

## Features

| Area | What it provides |
|---|---|
| 🌐 **Public landing page** | Announcements, local services directory, live stats, no login required |
| 🏠 **Resident portal** | Document requests, complaints, notifications, profile management, photo uploads, browsable announcements and local services |
| 🏛️ **Official portal** | Request processing, resident management, complaints, announcements, local services, official profile photo |
| 👑 **Admin tools** | Official account management, last-admin protection, full audit-log access |
| 📲 **SMS notifications** | Document-ready pickup notices, complaint resolution, announcement broadcasts, OTP password recovery via [httpSMS](https://httpsms.com) |
| 🤖 **AI Analyst** | Vulnerability Registry (formula + Gemini assessment), Calamity Triage with auto Purok detection, Labor Matcher, Workforce Insights, Connectivity Test |
| 📄 **Document generation** | Word-template filling and PDF conversion through LibreOffice, with in-browser preview |
| 📤 **CSV export** | Vulnerability Registry and Calamity Triage priority lists exportable as CSV |
| 🧾 **Audit logging** | Official write actions are recorded for traceability |
| 🔐 **Authentication** | Username + password only; no email-based accounts; login lockout and OTP recovery |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Server | Apache (via [Laragon](https://laragon.org/)) |
| Language | PHP 8.x (MySQLi, no framework) |
| Database | MySQL / MariaDB |
| Frontend | Vanilla HTML/CSS/JavaScript |
| SMS | [httpSMS](https://httpsms.com) API |
| AI gateway | Google Gemini API |
| Document generation | PHP `ZipArchive` (docx templating) + [LibreOffice](https://www.libreoffice.org/) headless (PDF conversion) |

No Composer or npm dependencies — the project runs as-is once the prerequisites below are installed.

## ✨ Quick Start

```text
1. Start Laragon (Apache + MySQL)
2. Import database.sql (default password for captain/admin: gemgem)
3. Create the ignored .env file
4. Create the first admin official account manually
5. Open index.php in your browser
```

> 💡 **Demo tip:** Use the official dashboard to process a request, generate a PDF,
> and verify the resident receives the corresponding notification.

---

## Prerequisites

You need **all** of the following installed and working before this project will run correctly:

### 1. Laragon (or equivalent Apache + MySQL + PHP stack)
- Download: https://laragon.org/download/
- Must include **PHP 8.x** with the following extensions **enabled** in `php.ini`:
  - `extension=mysqli`
  - `extension=zip` — required for document generation (template placeholder filling). The app will return a clear error if this is missing.
  - `extension=fileinfo` — required for profile photo upload validation
  - `extension=gd` — used for image validation on photo upload
  - `extension=curl` — required for httpSMS and Gemini API requests
- Restart Apache after enabling any extension.

### 2. LibreOffice (required — document generation will not work without it)
- Download: https://www.libreoffice.org/download/download/
- Used **headless** (no UI interaction) to convert generated `.docx` documents to PDF for in-browser preview.
- Microsoft Word does **not** substitute for this — Word has no reliable scriptable CLI conversion path. Both can be installed side by side; LibreOffice is only used silently by the backend.
- **Default expected path** (Windows): `C:\Program Files\LibreOffice\program\soffice.exe`
  If your install path differs, update the `$sofficePath` variable in `api/requests/generate_document.php`.

### 3. httpSMS account (required for all SMS features)
- Sign up: https://httpsms.com
- You need:
  - An API key
  - A registered "from" number (the phone acting as the SMS gateway, connected via the httpSMS Android app)
  - A SIM slot identifier (`SIM1` or `SIM2`) if the sending device is dual-SIM
- Without this, document-ready notices, complaint resolution SMS, announcement broadcasts, and OTP password recovery will silently fail to send (the rest of the app still works).

### 4. Gemini API key (required for AI Analyst features)
- Get a key from Google AI Studio: https://aistudio.google.com/app/apikey
- Without this, only the rule-based formula scoring remains available; the AI Vulnerability assessment, Calamity Triage AI ranking, Labor Matcher, and Workforce Classifier will fall back or return configuration errors.

---

## Setup

1. **Clone/copy this project** into your Laragon `www/` directory (e.g. `C:\laragon\www\BarangayLink`).

2. **Create the database.**
   Open phpMyAdmin (or the MySQL CLI) and import `database.sql`. This creates the
   `barangay_system` database and all tables.

   > `db.php` connects with `host=localhost`, `user=root`, `password=''` (Laragon's
   > defaults). Edit `db.php` directly if your MySQL setup differs.

3. **Configure environment variables.**
   Create a `.env` file in the project root (this file is git-ignored and must be
   created manually on each machine — it is never committed):
   ```env
   HTTPSMS_API_KEY=your_httpsms_api_key
   HTTPSMS_FROM_NUMBER=+63XXXXXXXXXX
   HTTPSMS_SIM_SLOT=SIM1
   GEMINI_API_KEY=your_gemini_api_key
   GEMINI_MODEL=gemini-3.5-flash-lite
   ```
   (`.env.sms` is also supported as an alternate/legacy filename — `db.php` loads both if present.)
   Gemini settings are required for the AI diagnostic endpoint. The API key is read
   server-side only and must never be placed in browser JavaScript or committed.
   `GEMINI_MODEL` is optional; when unset, `ai_chat()` falls back to `gemini-3.5-flash-lite`.

4. **Harden PHP session cookies.**
   `db.php` sets these at runtime with `ini_set()`, but set them in `php.ini` too so
   every entry point gets the same protection:
   ```ini
   session.cookie_httponly = 1
   session.cookie_samesite = Lax
   session.use_strict_mode = 1
   ```
   Restart Apache after editing `php.ini`.

5. **Confirm the LibreOffice path.**
   Open `api/requests/generate_document.php` and confirm `$sofficePath` matches your
   actual LibreOffice install location.

6. **Create your first official (admin) account manually.**
   Official self-registration is intentionally disabled (see
   [Security Notes](#security-notes)). There is **no default account** shipped in
   `database.sql` — you must insert your first admin directly via phpMyAdmin/MySQL.
   Passwords must be hashed with PHP's `password_hash()` — do not insert a plain-text
   password. The easiest way to generate a hash:

   ```php
   <?php echo password_hash('your-strong-password', PASSWORD_DEFAULT); ?>
   ```

   Then run an `INSERT` similar to:

   ```sql
   INSERT INTO barangay_officials
     (fname, lname, username, role, address, contact, password, position)
   VALUES
     ('Juan', 'Dela Cruz', 'admin', 'admin',
      'Purok 1, Barangay Sampaguita', '09000000000',
      '<paste-hash-here>', 'Barangay Captain');
   ```

   After first login, use the in-app **Manage Officials** panel to create any other
   staff/admin accounts.

7. **Start Laragon** (Apache + MySQL) and visit:
   ```
   http://localhost/BarangayLink/index.php
   ```
   (adjust the folder name to whatever you cloned the project as)

---

## Project Structure

```
├─ api/                  All backend endpoints, grouped by feature
│  ├─ ai/                Gemini-backed endpoints (assess, classify, match, extract, test, echo)
│  ├─ announcements/     Create, list, update, delete, SMS broadcast
│  ├─ audit/             Audit log list + triage logging
│  ├─ auth/              OTP request, verify, password reset
│  ├─ complaints/        Resident complaint create/list + official resolve/update
│  ├─ notifications/     Resident notifications list + mark-all-read
│  ├─ officials/         Official CRUD + profile photo upload
│  ├─ requests/          Document requests + generate_document + notify_ready
│  ├─ residents/         Resident CRUD + recalculate_all_scores + upload_photo
│  ├─ services/          Local services CRUD
│  └─ stats/             Public landing-page stats
├─ pages/                Login + resident/official dashboards
├─ js/  css/             Frontend logic and styling per page
├─ document_templates/  Local Word (.docx) templates (files are git-ignored)
├─ generated_documents/  Local generated PDFs/DOCX (files are git-ignored)
├─ profile_img/
│  ├─ residents/         Resident-uploaded profile photos (git-ignored)
│  └─ b_official/        Official-uploaded profile photos (git-ignored)
├─ database.sql          Full schema (no default admin seeded)
├─ db.php                DB connection + .env loader + session hardening
└─ index.php             Public landing page
```

---

## 📄 Supported Document Types

Document generation is driven by matching `.docx` templates in `document_templates/`:

| Document Type | Template File | Required Placeholders |
|---|---|---|
| Barangay Clearance | `barangay_clearance.docx` | full_name, age, civil_status, address, purpose, date_issued, reference_no, official_name, official_position |
| Certificate of Residency | `certificate_of_residency.docx` | full_name, address, years_of_residency, purpose, date_issued, reference_no, official_name, official_position |
| Certificate of Indigency | `certificate_of_indigency.docx` | full_name, address, purpose, date_issued, reference_no, official_name, official_position |
| Certificate of Good Moral Character | `certificate_of_good_moral_character.docx` | full_name, address, purpose, date_issued, reference_no, official_name, official_position |
| Business Permit Endorsement | `business_permit_endorsement.docx` | owner_name, business_name, business_type, business_address, purpose, date_issued, reference_no, official_name, official_position |

Placeholders in the template are written as `{{full_name}}`, `{{purpose}}`, etc. The
generator fills them, saves the DOCX, and converts it to PDF via LibreOffice.

---

## 🧮 Vulnerability Scoring (Eligibility Score)

Every resident has an `eligibility_score` (0–100) computed from profiling data.
This is the **rule-based** baseline used by the Vulnerability Registry and by
Calamity Triage when Gemini is unavailable.

| Factor | Points |
|---|---|
| Employment: Unemployed | +30 |
| Employment: Self-Employed | +10 |
| Dependents | +5 each, capped at +25 |
| PWD | +20 |
| Age ≥ 60 (computed from birthdate) | +15 |
| Monthly income below ₱5,000 | +20 |
| Monthly income ₱5,000 – ₱10,000 | +10 |

**Cap:** 100. **Floor:** 0.

- Calculated automatically on resident create/update.
- Recalculable for all residents via the **Recalculate All Scores** button in the
  Vulnerability Registry (`api/residents/recalculate_all_scores.php`).
- Stored in `residents.eligibility_score`.
- AI assessments are **not** saved to the database — they are display-only and are
  layered on top of the formula score.

---

## 🔌 AI Analyst

The AI Analyst tab is a hub with five tools. All AI calls route through `ai_chat()`
in `api/common.php` and read `GEMINI_API_KEY` from the server-side `.env`.

| Tool | Endpoint(s) | What it does |
|---|---|---|
| **Vulnerability Registry** | `api/ai/assess_residents.php` | Formula scores + on-demand Gemini assessment; sortable, filterable by Purok, paginated (20/page), CSV-exportable |
| **Calamity Triage** | `api/ai/extract_puroks.php`, `api/ai/assess_residents.php`, `api/audit/log_triage.php` | AI ranks relief priority for a situation report; auto-detects affected Puroks from text; falls back to formula ranking if AI is unavailable |
| **Labor Matcher** | `api/ai/match_labor.php` | Free-text labor need is matched against active residents who are unemployed, self-employed, or open to work |
| **Workforce Insights** | `api/ai/classify_workforce.php` | Gemini classifies residents into a fixed skill-category list; charts for skill category, employment status, education, and availability |
| **Connectivity Test** | `api/ai/echo_test.php` | Diagnostic prompt to Gemini (admin-only variant lives at `api/ai/test.php`) |

**Access:** All AI endpoints require an authenticated official session.
`api/ai/test.php` additionally requires the `admin` role.

**Reference formula for `ai_score` tiers** (returned by `assess_residents.php`):
- 80–100 → **Critical**
- 60–79 → **High**
- 35–59 → **Moderate**
- 0–34 → **Low**

### Diagnostic test

With an authenticated admin official session:

```text
POST /api/ai/test.php
Content-Type: application/json
```

```json
{
  "prompt": "Reply with a short confirmation that the connection is working."
}
```

---

## Authentication Model

- **No email anywhere in the system.** Residents and officials both authenticate with
  **username + password** only.
- **Residents** are registered in person by staff (ID + proof of residency), issued a
  username/password directly — there is no public resident self-registration.
- **Officials** have no self-registration either — accounts are created only by an
  existing **admin**-role official via the in-app "Manage Officials" panel, or manually
  in the database for the very first account.
- **No default account is shipped.** `database.sql` does **not** seed any official.
  You must create the first admin yourself (see Setup step 6).
- **Roles:** `staff` and `admin` (superior account). Admins can manage other official
  accounts and see the full audit log; staff see only their own audit history.
- **Login lockout:** 5 consecutive failed password attempts locks the account for
  10 minutes. The lockout clears automatically after expiry, on successful password
  reset, or on successful login with the correct password.
- **Password recovery** is OTP-based via SMS:
  - 6-digit code, 10-minute expiry, single-use.
  - Max **5 wrong guesses** per code; the counter is incremented server-side so parallel guesses are counted.
  - Resend throttled to one code per 60 seconds per account.
  - Successful reset also clears the login lockout.
- **Last-admin protection:**
  - You cannot deactivate your own account.
  - You cannot demote your own account from `admin` to `staff`.
  - The **last active admin** cannot be deactivated or demoted.
- **AI diagnostic testing** (`api/ai/test.php`) requires an authenticated admin
  session and a JSON `POST`. Send JSON such as
  `{"prompt":"Reply with a short confirmation."}`.

---

## Security Notes

This project was built for a **localhost capstone demonstration**, not public
production hosting. Before deploying anywhere reachable outside your local machine:

- Move database credentials in `db.php` out of hardcoded values into `.env`.
- Never commit a real `.env` / `.env.sms` file — confirm `.gitignore` excludes them
  (it does by default in this repo) and that no API keys ever end up in git history.
- Keep `GEMINI_API_KEY` server-side. Do not expose it in frontend code, URLs, logs,
  or API responses. Rotate the key immediately if it is accidentally disclosed.
- **No default credentials ship with the project.** `database.sql` intentionally does
  not insert any official account. The first admin must be created manually with a
  `password_hash()`-generated password. Never leave a weak or shared default
  password in a live system.
- Gemini is used by the connectivity test (`api/ai/echo_test.php`, `api/ai/test.php`),
  the AI Vulnerability Registry assessment and Calamity Triage ranking
  (`api/ai/assess_residents.php`), Purok detection for triage
  (`api/ai/extract_puroks.php`), labor-request matching (`api/ai/match_labor.php`),
  and workforce classification (`api/ai/classify_workforce.php`).
  All calls go through `ai_chat()` on the server.
- AI features send **de-identified** resident data to Google's Gemini API. No
  names, addresses, or contact numbers ever leave the server:
  - **Vulnerability assessment** (`api/ai/assess_residents.php`) and **Labor
    matcher** (`api/ai/match_labor.php`) send only structured profiling fields
    — PWD / 4Ps flags, income bracket, employment status, skills, dependents,
    Purok. Names and contacts are used server-side to render results back to
    the official but are excluded from the AI payload.
  - **Calamity triage Purok detection** (`api/ai/extract_puroks.php`) sends only
    the situation text and the list of known Purok names.
  - **Workforce classifier** (`api/ai/classify_workforce.php`) sends only
    occupation, skills, educational attainment, employment status, work
    experience years, and training certifications. No names or contact
    numbers.
  - **Connectivity test** (`api/ai/echo_test.php` / `api/ai/test.php`) sends
    only the diagnostic prompt.
  Review the de-identified data flow under the Data Privacy Act of 2012 before
  real deployment; free-tier API data may be used by Google to improve its models.
- The rule-based `eligibility_score` remains the stored baseline. AI assessments are
  not saved to the database and Calamity Triage falls back to formula ranking when
  Gemini is unavailable.
- Review file upload limits/validation in `api/residents/upload_photo.php`,
  `api/officials/upload_photo.php`, and the `generated_documents/` / `profile_img/`
  folder permissions for a hardened deployment.
- `document_type` on `document_requests` is free text (no enum/check constraint) —
  add server-side validation against a fixed list if opening this system to less
  trusted input sources.
- **Landing page stats:** `api/stats/landing.php` returns two **placeholder**
  values (`request_fulfillment_rate = 92`, `avg_response_time_minutes = 15`).
  These are not derived from real request data — replace or remove before
  treating them as metrics.

---

## Acknowledgments

Built as a capstone project. Document generation, SMS integration, the AI Analyst
suite, and role-based access were iteratively developed and hardened over the course
of the project — see commit history for the progression from initial prototype to
current state.

<div align="center">

---

### 🌱 Built for better barangay services

`BarangayLink`

</div>
