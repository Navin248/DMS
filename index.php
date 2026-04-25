<?php
session_start();
require_once 'config/database.php';

// If user is logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: /DMS/dashboard.php");
    exit();
}

// Initialize variables with error checking
$active_disasters = 0;
$total_resources = 0;
$total_resource_types = 0;
$pending_requests = 0;
$latest_disasters = null;
$all_disasters = null;

// Get statistics from database with error handling
if ($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM disasters WHERE status = 'active'");
    if ($result) {
        $row = $result->fetch_assoc();
        $active_disasters = $row['count'] ?? 0;
    }

    $result = $conn->query("SELECT SUM(quantity) as total FROM resources");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_resources = $row['total'] ?? 0;
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM resources");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_resource_types = $row['count'] ?? 0;
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM requests WHERE status = 'pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pending_requests = $row['count'] ?? 0;
    }

    $latest_disasters = $conn->query("SELECT * FROM disasters ORDER BY created_at DESC LIMIT 5");
    $all_disasters = $conn->query("SELECT * FROM disasters WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disaster Relief Management System - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/swiper@10/swiper-bundle.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #F3F4F6;
            color: #333;
        }

        .navbar-custom {
            background: linear-gradient(135deg, #1E3A8A 0%, #0f2847 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-custom .navbar-brand {
            font-size: 24px;
            font-weight: bold;
            color: #F97316 !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-custom .nav-link {
            color: white !important;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            font-weight: 500;
            margin: 0 5px;
        }

        .navbar-custom .nav-link:hover {
            color: #F97316 !important;
        }

        .btn-login {
            background-color: #F97316;
            color: white;
            border: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            font-weight: 500;
            padding: 8px 24px;
            border-radius: 6px;
        }

        .btn-login:hover {
            background-color: #e86a0a;
            transform: scale(1.05);
            color: white;
        }

        .hero {
            height: 70vh;
            background: linear-gradient(135deg, #1E3A8A 0%, #0f2847 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.1) 0%, transparent 70%);
            animation: slowMove 20s ease-in-out infinite;
        }

        @keyframes slowMove {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(20px, 20px);
            }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
        }

        .hero h1 {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.3);
        }

        .hero p {
            font-size: 1.5rem;
            margin-bottom: 30px;
            color: #E5E7EB;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-hero {
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            border: none;
        }

        .btn-hero-primary {
            background-color: #F97316;
            color: white;
        }

        .btn-hero-primary:hover {
            background-color: #e86a0a;
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(249, 115, 22, 0.3);
        }

        .btn-hero-secondary {
            background-color: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-hero-secondary:hover {
            background-color: white;
            color: #1E3A8A;
            transform: translateY(-5px);
        }

        .helpline-number {
            font-size: 2rem;
            font-weight: bold;
            color: #F97316;
            display: inline-block;
            font-family: monospace;
        }

        .section {
            padding: 80px 20px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 900;
            color: #1E3A8A;
            margin-bottom: 15px;
        }

        .section-header p {
            color: #6B7280;
            font-size: 1.1rem;
            margin-top: 20px;
        }

        .awareness-section {
            background: white;
        }

        .swiper {
            width: 100%;
        }

        .disaster-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-top: 4px solid #F97316;
            cursor: pointer;
            height: 100%;
        }

        .disaster-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            border-top-color: #1E3A8A;
        }

        .disaster-card-header {
            background: linear-gradient(135deg, #1E3A8A 0%, #0f2847 100%);
            color: white;
            padding: 30px;
            text-align: center;
            font-size: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 150px;
        }

        .disaster-card-header i {
            font-size: 4rem;
            color: #FED7AA;
            text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .disaster-card:hover .disaster-card-header i {
            font-size: 4.5rem;
            color: #F97316;
            transform: scale(1.1);
        }

        .disaster-card-body {
            padding: 30px;
        }

        .disaster-card h4 {
            color: #1E3A8A;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .disaster-card-list {
            list-style: none;
        }

        .disaster-card-list li {
            padding: 8px 0;
            border-bottom: 1px solid #E5E7EB;
            color: #555;
        }

        .disaster-card-list li.do {
            color: #16a34a;
        }

        .disaster-card-list li.dont {
            color: #dc2626;
        }

        .news-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #fef3c7 100%);
        }

        .news-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #F97316;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .news-card:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .news-badge {
            display: inline-block;
            background: #FE7E3C;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .news-title {
            color: #1E3A8A;
            font-weight: bold;
            margin: 15px 0 8px 0;
            font-size: 1.1rem;
        }

        .news-description {
            color: #555;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .news-date {
            color: #9CA3AF;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .map-section {
            background: white;
        }

        #disaster-map {
            width: 100%;
            height: 500px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .stats-section {
            background: linear-gradient(135deg, #1E3A8A 0%, #0f2847 100%);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 35px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            background: rgba(249, 115, 22, 0.2);
            transform: translateY(-10px);
            border-color: #F97316;
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.3);
        }

        .stat-icon {
            font-size: 3rem;
            color: #F97316;
            margin-bottom: 15px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            color: #FED7AA;
            margin: 15px 0;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #E5E7EB;
            font-weight: 600;
        }

        .contacts-section {
            background: white;
        }

        .contacts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-top: 50px;
        }

        .contact-card {
            background: linear-gradient(135deg, #1E3A8A 0%, #0f2847 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(30, 58, 138, 0.3);
        }

        .contact-icon {
            font-size: 2.5rem;
            color: #F97316;
            margin-bottom: 15px;
        }

        .contact-name {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .contact-number {
            font-size: 1.3rem;
            font-family: monospace;
            color: #FED7AA;
        }

        .contact-description {
            font-size: 0.85rem;
            color: #D1D5DB;
            margin-top: 10px;
        }

        .cta-section {
            background: linear-gradient(135deg, #F97316 0%, #ea580c 100%);
            color: white;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            color: #FED7AA;
        }

        footer {
            background: #0f2847;
            color: white;
            text-align: center;
            border-top: 3px solid #F97316;
        }

        @media (max-width: 768px) {
            .hero {
                height: 50vh;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .section-header h2 {
                font-size: 1.8rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .btn-hero {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-shield-alt"></i> DRMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span
                    class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#awareness">Safety</a></li>
                    <li class="nav-item"><a class="nav-link" href="#news">News</a></li>
                    <li class="nav-item"><a class="nav-link" href="#map">Map</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contacts">Contacts</a></li>
                </ul>
                <a href="login.php" class="btn btn-login ms-3">Login</a>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Disaster Relief Management</h1>
            <p>Helping communities respond faster to disasters and emergencies</p>
            <div class="hero-buttons">
                <a href="login.php" class="btn-hero btn-hero-primary"><i class="fas fa-sign-in-alt"></i> Login to
                    Dashboard</a>
                <a href="#awareness" class="btn-hero btn-hero-secondary"><i class="fas fa-book"></i> Learn Safety
                    Tips</a>
            </div>
            <div class="hero-helpline" style="margin-top: 40px;">
                <div style="font-size: 1.1rem; color: #FED7AA;">Emergency Helpline</div>
                <div class="helpline-number">112</div>
            </div>
        </div>
    </section>

    <!-- AWARENESS SLIDER -->
    <section class="section awareness-section" id="awareness">
        <div class="container">
            <div class="section-header">
                <h2>Disaster Awareness</h2>
                <p>Learn what to do during different disasters</p>
            </div>
            <div class="swiper mySwiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="disaster-card">
                            <div class="disaster-card-header"><i class="fas fa-house-crack"></i></div>
                            <div class="disaster-card-body">
                                <h4>Earthquake Safety</h4>
                                <ul class="disaster-card-list">
                                    <li class="do">✓ Take cover under sturdy desk</li>
                                    <li class="do">✓ Move to open area if outdoor</li>
                                    <li class="do">✓ Stay away from power lines</li>
                                    <li class="dont">✗ Don't use elevators</li>
                                    <li class="dont">✗ Don't stand in doorways</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="disaster-card">
                            <div class="disaster-card-header"><i class="fas fa-water"></i></div>
                            <div class="disaster-card-body">
                                <h4>Flood Safety</h4>
                                <ul class="disaster-card-list">
                                    <li class="do">✓ Move to higher ground</li>
                                    <li class="do">✓ Turn off utilities</li>
                                    <li class="do">✓ Keep emergency kit ready</li>
                                    <li class="dont">✗ Don't use vehicles in water</li>
                                    <li class="dont">✗ Don't drink flood water</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="disaster-card">
                            <div class="disaster-card-header"><i class="fas fa-wind"></i></div>
                            <div class="disaster-card-body">
                                <h4>Cyclone Safety</h4>
                                <ul class="disaster-card-list">
                                    <li class="do">✓ Stay indoors</li>
                                    <li class="do">✓ Secure loose objects</li>
                                    <li class="do">✓ Board up windows</li>
                                    <li class="dont">✗ Don't go outside</li>
                                    <li class="dont">✗ Don't ignore warnings</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="disaster-card">
                            <div class="disaster-card-header"><i class="fas fa-fire"></i></div>
                            <div class="disaster-card-body">
                                <h4>Wildfire Safety</h4>
                                <ul class="disaster-card-list">
                                    <li class="do">✓ Evacuate immediately</li>
                                    <li class="do">✓ Keep car fueled</li>
                                    <li class="do">✓ Close windows/doors</li>
                                    <li class="dont">✗ Don't wait for orders</li>
                                    <li class="dont">✗ Don't block exits</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="disaster-card">
                            <div class="disaster-card-header"><i class="fas fa-wave-square"></i></div>
                            <div class="disaster-card-body">
                                <h4>Tsunami Safety</h4>
                                <ul class="disaster-card-list">
                                    <li class="do">✓ Move inland/uphill</li>
                                    <li class="do">✓ Heed all warnings</li>
                                    <li class="do">✓ Don't return too early</li>
                                    <li class="dont">✗ Don't go to beach</li>
                                    <li class="dont">✗ Don't ignore sirens</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="disaster-card">
                            <div class="disaster-card-header"><i class="fas fa-mountain"></i></div>
                            <div class="disaster-card-body">
                                <h4>Landslide Safety</h4>
                                <ul class="disaster-card-list">
                                    <li class="do">✓ Move away from slope</li>
                                    <li class="do">✓ Stay alert to changes</li>
                                    <li class="do">✓ Know escape routes</li>
                                    <li class="dont">✗ Don't play on slopes</li>
                                    <li class="dont">✗ Don't build near cliffs</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </div>
    </section>

    <!-- NEWS SECTION -->
    <section class="section news-section" id="news">
        <div class="container">
            <div class="section-header">
                <h2>Latest Disaster Updates</h2>
                <p>Real-time alerts from the system</p>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <?php if ($latest_disasters && $latest_disasters->num_rows > 0): ?>
                        <?php while ($disaster = $latest_disasters->fetch_assoc()): ?>
                            <div class="news-card">
                                <span class="news-badge"><i class="fas fa-exclamation-triangle"></i>
                                    <?php echo strtoupper($disaster['severity']); ?></span>
                                <div class="news-title"><?php echo htmlspecialchars($disaster['type']); ?> in
                                    <?php echo htmlspecialchars($disaster['location']); ?></div>
                                <div class="news-description">Affected:
                                    <strong><?php echo number_format($disaster['affected_population']); ?></strong> people |
                                    Status: <strong><?php echo ucfirst($disaster['status']); ?></strong></div>
                                <div class="news-date"><i class="fas fa-calendar"></i>
                                    <?php echo date('M d, Y H:i', strtotime($disaster['created_at'])); ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info"><i class="fas fa-info-circle"></i> No active alerts. Stay prepared!
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-4">
                    <div class="card"
                        style="background: linear-gradient(135deg, #1E3A8A, #0f2847); color: white; border: none;">
                        <div class="card-body">
                            <h5 class="card-title mb-4"><i class="fas fa-info-circle"></i> Quick Info</h5>
                            <p><strong>Active Disasters:</strong> <span
                                    style="color: #F97316;"><?php echo $active_disasters; ?></span></p>
                            <p><strong>Pending Requests:</strong> <span
                                    style="color: #FED7AA;"><?php echo $pending_requests; ?></span></p>
                            <p><strong>Total Resources:</strong> <span
                                    style="color: #10b981;"><?php echo number_format($total_resources); ?></span></p>
                            <hr style="border-color: rgba(255,255,255,0.2);">
                            <p style="font-size: 0.9rem;">Last Updated: Just now</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MAP SECTION -->
    <section class="section map-section" id="map">
        <div class="container">
            <div class="section-header">
                <h2>Disaster Locations Map</h2>
                <p>Real-time tracking of active zones</p>
            </div>
            <div id="disaster-map"></div>
        </div>
    </section>

    <!-- STATISTICS SECTION -->
    <section class="section stats-section">
        <div class="container">
            <div class="section-header" style="color: white;">
                <h2 style="color: white;">System Statistics</h2>
                <p style="color: #E5E7EB;">Current status of operations</p>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-exclamation-circle stat-icon"></i>
                    <div class="stat-number"><?php echo $active_disasters; ?></div>
                    <div class="stat-label">Active Disasters</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-boxes stat-icon"></i>
                    <div class="stat-number"><?php echo number_format($total_resources); ?></div>
                    <div class="stat-label">Total Resources</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-cube stat-icon"></i>
                    <div class="stat-number"><?php echo $total_resource_types; ?></div>
                    <div class="stat-label">Resource Types</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-hourglass-half stat-icon"></i>
                    <div class="stat-number"><?php echo $pending_requests; ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
            </div>
        </div>
    </section>

    <!-- EMERGENCY CONTACTS -->
    <section class="section contacts-section" id="contacts">
        <div class="container">
            <div class="section-header">
                <h2>Emergency Contacts</h2>
                <p>Available 24/7</p>
            </div>
            <div class="contacts-grid">
                <div class="contact-card">
                    <div class="contact-icon"><i class="fas fa-phone-volume"></i></div>
                    <div class="contact-name">National Helpline</div>
                    <div class="contact-number">112</div>
                    <div class="contact-description">Emergency Services</div>
                </div>
                <div class="contact-card">
                    <div class="contact-icon"><i class="fas fa-hospital"></i></div>
                    <div class="contact-name">Disaster Helpline</div>
                    <div class="contact-number">108</div>
                    <div class="contact-description">Ambulance & Rescue</div>
                </div>
                <div class="contact-card">
                    <div class="contact-icon"><i class="fas fa-fire"></i></div>
                    <div class="contact-name">Fire Service</div>
                    <div class="contact-number">101</div>
                    <div class="contact-description">Fire & Rescue</div>
                </div>
                <div class="contact-card">
                    <div class="contact-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="contact-name">Police</div>
                    <div class="contact-number">100</div>
                    <div class="contact-description">Police & Security</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="section cta-section" style="padding: 60px 20px;">
        <div class="container">
            <h2>Ready to Help? Join Our Network</h2>
            <p>Be part of the disaster relief management system</p>
            <a href="login.php" class="btn btn-light btn-lg"><i class="fas fa-arrow-right"></i> Login to Dashboard</a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="py-4">
        <div class="container">
            <p>&copy; 2026 Disaster Relief Management System. All rights reserved.</p>
            <p style="font-size: 0.9rem; color: #9CA3AF; margin-top: 10px;">Emergency Hotline: 112 | Disaster Helpline:
                108 | Always Prepared</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/swiper@10/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({ duration: 800, offset: 100, once: true });

        // Initialize Swiper
        const swiper = new Swiper('.mySwiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
            autoplay: { delay: 4000, disableOnInteraction: false },
            breakpoints: {
                640: { slidesPerView: 2, spaceBetween: 20 },
                768: { slidesPerView: 3, spaceBetween: 30 },
            }
        });

        // Initialize Map
        const map = L.map('disaster-map').setView([20.5937, 78.9629], 4);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 19,
        }).addTo(map);

        // Add markers
        <?php
        if ($all_disasters && $all_disasters->num_rows > 0) {
            $all_disasters->data_seek(0);
            while ($disaster = $all_disasters->fetch_assoc()):
                ?>
                L.marker([<?php echo $disaster['latitude']; ?>, <?php echo $disaster['longitude']; ?>])
                    .addTo(map)
                    .bindPopup('<strong><?php echo htmlspecialchars($disaster['type']); ?></strong><br/>Location: <?php echo htmlspecialchars($disaster['location']); ?><br/>Severity: <?php echo htmlspecialchars($disaster['severity']); ?><br/>Affected: <?php echo number_format($disaster['affected_population']); ?> people');
            <?php endwhile;
        } ?>
    </script>
</body>

</html>