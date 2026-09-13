<div align="center">

# 🌱 BarangayLink

### Web-Based Barangay Management System

<img src="https://readme-typing-svg.demolab.com?font=Inter&weight=600&size=20&duration=3200&pause=900&color=2563EB&center=true&vCenter=true&width=760&lines=Digital+services+for+Brgy.+Sampaguita;Resident+requests%2C+complaints%2C+and+community+updates;Secure+official+workflows+with+real+document+generation" alt="Animated BarangayLink description" />

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
records, public announcements, a local services directory, SMS notifications, and
role-based staff/admin access.

> **Status:** Localhost/LAN deployment only. Not configured for production/public
> hosting as-is (see [Security Notes](#security-notes) below).

<div align="center">

| 👥 Residents | 📄 Documents | 📣 Announcements | 📱 SMS | 🤖 AI Gateway |
|:---:|:---:|:---:|:---:|:---:|
| Portal access | Word/PDF generation | Public updates | OTP & alerts | OmniRoute |

</div>

---

## Features

| Area | What it provides |
|---|---|
| 🌐 **Public landing page** | Announcements, local services directory, live stats, no login required |
| 🏠 **Resident portal** | Document requests, complaints, notifications, profile management, and photo uploads |
| 🏛️ **Official portal** | Request processing, resident management, complaints, announcements, and local services |
| 👑 **Admin tools** | Official account management and complete audit-log access |
| 📲 **SMS notifications** | Status updates, complaint resolution, announcement broadcasts, and OTP recovery via [httpSMS](https://httpsms.com) |
| 🤖 **AI connectivity** | OpenAI-compatible diagnostic requests through OmniRoute |
| 📄 **Document generation** | Word-template filling and PDF conversion through LibreOffice |
| 🧾 **Audit logging** | Official write actions are recorded for traceability |
| 🔐 **Authentication** | Username + password only; no email-based accounts |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Server | Apache (via [Laragon](https://laragon.org/)) |
| Language | PHP 8.x (MySQLi, no framework) |
| Database | MySQL / MariaDB |
| Frontend | Vanilla HTML/CSS/JavaScript |
| SMS | [httpSMS](https://httpsms.com) API |
| AI gateway | OmniRoute OpenAI-compatible API |
| Document generation | PHP `ZipArchive` (docx templating) + [LibreOffice](https://www.libreoffice.org/) headless (PDF conversion) |

No Composer or npm dependencies — the project runs as-is once the prerequisites below are installed.

## ✨ Quick Start

```text
1. Start Laragon (Apache + MySQL)
2. Import database.sql
3. Create the ignored .env file
4. Create the first admin official account
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
  - `extension=curl` — required for httpSMS and OmniRoute API requests
- Restart Apache after enabling any extension.

### 2. LibreOffice (required — document generation will not work without it)
- Download: https://www.libreoffice.org/download/download/
- Used **headless** (no UI interaction) to convert generated `.docx` documents to PDF for in-browser preview.
- Microsoft Word does **not** substitute for this — Word has no reliable scriptable CLI conversion path. Both can be installed side by side; LibreOffice is only used silently by the backend.
- **Default expected path** (Windows): `C:\Program Files\LibreOffice\program\soffice.exe`
  If your install path differs, update the `$sofficePath` variable in `api/requests/generate_document.php`.

### 3. httpSMS account (required for all SMS features)
- Sign up: https://httpsms.com
- You need：
  - An API key
  - A registered "from" number (the phone acting as the SMS gateway, connected via the httpSMS Android app)
  - A SIM slot identifier (`SIM1` or `SIM2`) if the sending device is dual-SIM
- Without this, document-ready notices, complaint resolution SMS, announcement broadcasts, and OTP password recovery will silently fail to send (the rest of the app still works).

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
   OMNIROUTE_API_KEY=your_omniroute_api_key
   OMNIROUTE_BASE_URL=https://omni.inamoriyama.com/v1
   ```
   (`.env.sms` is also supported as an alternate/legacy filename — `db.php` loads both if present.)
   OmniRoute settings are optional unless you use the AI diagnostic endpoint. The API key
   is read server-side only and must never be placed in browser JavaScript or committed.

4. **Confirm the LibreOffice path.**
   Open `api/requests/generate_document.php` and confirm `$sofficePath` matches your
   actual LibreOffice install location.

5. **Create your first official (admin) account manually.**
   Official self-registration is intentionally disabled (see
   [Security Notes](#security-notes)). Insert your first admin account directly via
   phpMyAdmin/MySQL — a commented example `INSERT` statement is included at the bottom
   of `database.sql`. Passwords must be hashed with PHP's `password_hash()` — do not
   insert a plain-text password.

6. **Start Laragon** (Apache + MySQL) and visit:
   ```
   http://localhost/BarangayLink/index.php
   ```
   (adjust the folder name to whatever you cloned the project as)

---

## Project Structure

```
├─ api/                  All backend endpoints, grouped by feature
│  ├─ announcements/      ├─ officials/           ├─ requests/
│  ├─ audit/               ├─ residents/            ├─ services/
│  ├─ auth/                (OTP recovery)           ├─ stats/
│  ├─ complaints/          ├─ notifications/
│  ├─ ai/                  (OmniRoute diagnostic endpoint)
├─ pages/                Login + resident/official dashboards
├─ js/  css/             Frontend logic and styling per page
├─ document_templates/  Local Word (.docx) templates (files are git-ignored)
├─ generated_documents/  Local generated PDFs/DOCX (files are git-ignored)
├─ profile_img/          Local resident-uploaded profile photos (git-ignored)
├─ database.sql          Full schema + example seed INSERT
├─ db.php                DB connection + .env loader
└─ index.php              Public landing page
```

## 🔌 OmniRoute Diagnostic Test

OmniRoute is currently connected only to the admin diagnostic endpoint. It does not
automatically route normal application requests or this project's conversations.

With an authenticated admin official session, send a JSON `POST` request to:

```text
POST /api/ai/test.php
Content-Type: application/json
```

```json
{
  "prompt": "Reply with a short confirmation that the connection is working."
}
```

The endpoint reads `OMNIROUTE_API_KEY` and `OMNIROUTE_BASE_URL` from the server-side
`.env` file and calls `omniroute_chat()` from [api/common.php](api/common.php).

---

## Authentication Model

- **No email anywhere in the system.** Residents and officials both authenticate with
  **username + password** only.
- **Residents** are registered in person by staff (ID + proof of residency), issued a
  username/password directly — there is no public resident self-registration.
- **Officials** have no self-registration either — accounts are created only by an
  existing **admin**-role official via the in-app "Manage Officials" panel, or manually
  in the database for the very first account.
- **Roles:** `staff` and `admin` (superior account). Admins can manage other official
  accounts and see the full audit log; staff see only their own audit history.
- **Password recovery** is OTP-based via SMS (10-minute expiry, single-use codes) —
  there is no email-based "forgot password" flow.
- **OmniRoute diagnostic testing** is available at `api/ai/test.php`. It requires an
  authenticated admin official session and a `POST` request. Send JSON such as
  `{"prompt":"Reply with a short confirmation."}`. The endpoint calls OmniRoute
  through `omniroute_chat()` in `api/common.php`; it does not route ordinary
  application requests or this project's user conversations through OmniRoute.

---

## Security Notes

This project was built for a **localhost capstone demonstration**, not public
production hosting. Before deploying anywhere reachable outside your local machine:

- Move database credentials in `db.php` out of hardcoded values into `.env`
- Never commit a real `.env` / `.env.sms` file — confirm `.gitignore` excludes them
  (it does by default in this repo) and that no API keys ever end up in git history
- Keep `OMNIROUTE_API_KEY` server-side. Do not expose it in frontend code, URLs, logs,
  or API responses. Rotate the key immediately if it is accidentally disclosed.
- OmniRoute is currently used only by the admin diagnostic endpoint; adding other AI
  features should route through a server-side PHP endpoint and `omniroute_chat()`.
- Review file upload limits/validation in `api/residents/upload_photo.php` and
  `generated_documents/` / `profile_img/` folder permissions for a hardened deployment
- `document_type` on `document_requests` is free text (no enum/check constraint) —
  add server-side validation against a fixed list if opening this system to less
  trusted input sources

---

## Acknowledgments

Built as a capstone project. Document generation, SMS integration, and role-based
access were iteratively developed and hardened over the course of the project — see
commit history for the progression from initial prototype to current state.

<div align="center">

---

### 🌱 Built for better barangay services

`BarangayLink` • Brgy. Sampaguita • Tagana-an, SDN

</div>
