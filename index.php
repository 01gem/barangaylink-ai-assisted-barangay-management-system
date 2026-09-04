<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BarangayLink — Barangay Sampaguita</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/visitor.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

  <nav class="navbar" id="navbar">
    <div class="nav-inner">
      <a href="index.php" class="nav-brand">
        <div class="brand-icon"><i class="fa-solid fa-seedling"></i></div>
        <div>
          <span class="brand-name">BarangayLink</span>
          <span class="brand-loc">Brgy. Sampaguita, Tagana-an, SDN</span>
        </div>
      </a>
      <div class="nav-links">
        <a href="#home" class="nav-link active">Home</a>
        <a href="#announcements" class="nav-link">Announcements</a>
        <a href="#services" class="nav-link">Local Services</a>
      </div>
      <div class="nav-actions">
        <a href="pages/login.php" class="btn-outline-nav">Log In</a>
      </div>
    </div>
  </nav>

  <section class="hero" id="home">
    <div class="hero-bg-deco" aria-hidden="true">
      <svg viewBox="0 0 700 700" xmlns="http://www.w3.org/2000/svg">
        <circle cx="350" cy="350" r="300" stroke="white" stroke-opacity="0.04" stroke-width="90" fill="none"/>
        <circle cx="350" cy="350" r="200" stroke="white" stroke-opacity="0.06" stroke-width="60" fill="none"/>
        <circle cx="350" cy="350" r="110" stroke="white" stroke-opacity="0.08" stroke-width="35" fill="none"/>
        <ellipse cx="350" cy="160" rx="30" ry="68" fill="white" fill-opacity="0.05" transform="rotate(0 350 350)"/>
        <ellipse cx="350" cy="160" rx="30" ry="68" fill="white" fill-opacity="0.05" transform="rotate(45 350 350)"/>
        <ellipse cx="350" cy="160" rx="30" ry="68" fill="white" fill-opacity="0.05" transform="rotate(90 350 350)"/>
        <ellipse cx="350" cy="160" rx="30" ry="68" fill="white" fill-opacity="0.05" transform="rotate(135 350 350)"/>
        <ellipse cx="350" cy="160" rx="30" ry="68" fill="white" fill-opacity="0.05" transform="rotate(180 350 350)"/>
        <ellipse cx="350" cy="160" rx="30" ry="68" fill="white" fill-opacity="0.05" transform="rotate(225 350 350)"/>
        <ellipse cx="350" cy="160" rx="30" ry="68" fill="white" fill-opacity="0.05" transform="rotate(270 350 350)"/>
        <ellipse cx="350" cy="160" rx="30" ry="68" fill="white" fill-opacity="0.05" transform="rotate(315 350 350)"/>
      </svg>
    </div>
    <div class="hero-content">
      <div class="hero-pill"><i class="fa-solid fa-map-pin"></i> Barangay Sampaguita, Tagana-an, Surigao del Norte</div>
      <h1>Barangay Sampaguita,<br /><em>Digitally Connected.</em></h1>
      <p class="hero-sub">Access barangay services, request official documents, track complaints, and discover verified local businesses — all from one secure platform.</p>
      <div class="hero-ctas">
        <a href="pages/login.php" class="btn-hero-primary"><i class="fa-solid fa-right-to-bracket"></i> Resident Log In</a>
      </div>
    </div>
    <div class="hero-cards-col">
      <div class="hfc" style="--d:0ms">
        <div class="hfc-icon"><i class="fa-solid fa-file-lines"></i></div>
        <div class="hfc-text"><strong>Document Requests</strong><span>Certificates &amp; clearances online</span></div>
      </div>
      <div class="hfc" style="--d:80ms">
        <div class="hfc-icon"><i class="fa-solid fa-bullhorn"></i></div>
        <div class="hfc-text"><strong>Announcements</strong><span>Real-time community updates</span></div>
      </div>
      <div class="hfc" style="--d:160ms">
        <div class="hfc-icon"><i class="fa-solid fa-store"></i></div>
        <div class="hfc-text"><strong>Local Services</strong><span>Verified businesses near you</span></div>
      </div>
      <div class="hfc" style="--d:240ms">
        <div class="hfc-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="hfc-text"><strong>Complaint Portal</strong><span>Submit &amp; track your concerns</span></div>
      </div>
    </div>
  </section>

  <div class="stats-bar">
    <div class="stat-item"><span class="sn">0</span><span class="sl">Registered Residents</span></div>
    <div class="stat-sep"></div>
    <div class="stat-item"><span class="sn">0</span><span class="sl">Verified Local Services</span></div>
    <div class="stat-sep"></div>
    <div class="stat-item"><span class="sn">0%</span><span class="sl">Request Fulfillment Rate</span></div>
    <div class="stat-sep"></div>
    <div class="stat-item"><span class="sn">0 min</span><span class="sl">Avg. Response Time</span></div>
  </div>

  <section class="section" id="announcements">
    <div class="container">
      <div class="sec-head">
        <div>
          <span class="sec-tag">Latest Updates</span>
          <h2 class="sec-title">Barangay Announcements</h2>
        </div>
        <a href="pages/login.php" class="see-all">View all <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <div class="announce-grid" id="announceGrid"></div>
    </div>
  </section>

  <section class="section section-alt" id="services">
    <div class="container">
      <div class="sec-head">
        <div>
          <span class="sec-tag">Verified Businesses</span>
          <h2 class="sec-title">Local Services Directory</h2>
        </div>
        <div class="filter-row" id="filterRow"></div>
      </div>
      <div class="services-grid" id="servicesGrid"></div>
    </div>
  </section>

  <section class="cta-section">
    <div class="cta-inner">
      <div class="cta-icon"><i class="fa-solid fa-id-card"></i></div>
      <h2>Need account access?</h2>
      <p>Residents can log in to request certificates and track complaints. New resident accounts are created only by authorized barangay officials.</p>
      <div class="cta-btns">
        <a href="pages/login.php" class="btn-cta-main">Resident Log In</a>
        <a href="pages/login.php#official" class="btn-cta-ghost">Official Log In</a>
      </div>
    </div>
  </section>

  <footer class="footer">
    <div class="footer-grid">
      <div class="footer-brand-col">
        <div class="footer-brand"><i class="fa-solid fa-seedling"></i> BarangayLink</div>
        <p>A Web-Based Integrated Management Information System for Barangay Operations and Verified Local Services.</p>
      </div>
      <div class="footer-col">
        <h4>Navigation</h4>
        <a href="#home">Home</a>
        <a href="#announcements">Announcements</a>
        <a href="#services">Local Services</a>
        <a href="pages/login.php">Resident Login</a>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <p><i class="fa-solid fa-location-dot"></i> Barangay Sampaguita, Tagana-an, Surigao del Norte</p>
        <p><i class="fa-solid fa-envelope"></i> sampaguita.barangay@email.com</p>
        <p><i class="fa-solid fa-phone"></i> (086) 000-0000</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2026 BarangayLink — Barangay Sampaguita. For presentation purposes only.</p>
    </div>
  </footer>

  <script src="js/visitor.js"></script>
</body>
</html>
