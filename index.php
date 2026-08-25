<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title>Msantha Investments and General Suppliers (MIGS) - Management System</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo file_exists(__DIR__ . '/css/style.css') ? filemtime(__DIR__ . '/css/style.css') : time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- PWA & Mobile Icons -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2E7D32">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Msantha Pigs">
    <link rel="apple-touch-icon" href="images/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="images/icon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="images/icon-512.png">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').then((reg) => {
                    console.log('PWA Service Worker registered:', reg.scope);
                }).catch((err) => {
                    console.log('PWA Service Worker registration failed:', err);
                });
            });
        }
    </script>
</head>
<body class="landing-page">
    <div class="landing-container">
        
        <!-- Header Topbar -->
        <header class="landing-header">
            <div class="landing-logo-brand">
                <img src="images/logo.png" alt="MIGS Official Logo" class="landing-logo" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCI+PGNpcmNsZSBjeD0iMjUiIGN5PSIyNSIgcj0iMjQiIGZpbGw9IiM0Q0FGNTAiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZmlsbD0id2hpdGUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtc2l6ZT0iMjAiPk08L3RleHQ+PC9zdmc+'">
                <div class="landing-brand-info">
                    <h2 class="landing-brand-title">Msantha Investments and General Suppliers</h2>
                    <p class="landing-brand-subtitle">MIGS — Liwonde, Machinga, Malawi</p>
                </div>
            </div>
            <a href="login.php" class="btn btn-primary landing-login-btn">System Login &rarr;</a>
        </header>

        <!-- Main Hero Section with Livestock & Poultry Background Photo -->
        <div class="landing-hero-banner">
            <div class="landing-hero-text">
                <span class="landing-hero-badge">Established 2006</span>
                <h1 class="landing-hero-title">Msantha Livestock &amp; Poultry Management System</h1>
                <p class="landing-hero-desc">Your Trusted Partner in Livestock Production, Poultry Incubation &amp; Veterinary Services across Malawi.</p>

                <div class="landing-hero-actions">
                    <a href="login.php" class="btn landing-hero-cta">Access System Portal &rarr;</a>
                </div>
            </div>
            <div class="landing-hero-img-wrap">
                <img src="images/landing_pigs.jpg" alt="Msantha Livestock & Farm" class="landing-hero-img">
            </div>
        </div>

        <!-- About Us & Quote Card -->
        <div class="card landing-about-card">
            <h3 class="landing-section-title">About Msantha Investments and General Suppliers (MIGS)</h3>
            <p class="landing-about-text">
                Msantha Investments and General Suppliers (MIGS) is based in <strong>Liwonde, Mawira area along Chipamba Road in Machinga District</strong>. Since our establishment in 2006, we have been dedicated to livestock and poultry production as well as comprehensive veterinary services across Malawi.
            </p>
            <p class="landing-about-text">
                We specialize in supplying high-quality livestock, poultry, breeding stock, and farm management technology to farmers, institutions, and agricultural organizations.
            </p>
            
            <div class="quote-box">
                <p class="quote-text">"We work as a team. Members of the organization have a reputable record of services, they set a pace for others to follow. <strong>Try us, and you will never regret.</strong>"</p>
            </div>
        </div>

        <!-- Services Grid (Directly from Brochure) -->
        <div class="landing-services-section">
            <h3 class="landing-section-title text-center">Our Services &amp; Specialties</h3>
            <div class="landing-grid">
                <div class="service-card">
                    <h4>🐄 Livestock &amp; Poultry Supply</h4>
                    <p>
                        Supplying diverse livestock species including goats, sheep, pigs, rabbits, graded dairy cattle, Malawi Zebu cattle, and poultry (Local chickens, Mikolongwe, Kuroiler, Turkeys, and Guinea Fowls).
                    </p>
                </div>
                <div class="service-card">
                    <h4>🐣 Poultry Egg Incubation</h4>
                    <p>
                        State-of-the-art incubation and hatching services for various poultry eggs with high hatchability rates.
                    </p>
                </div>
                <div class="service-card">
                    <h4>💉 Veterinary Products &amp; Ear Tags</h4>
                    <p>
                        Quality veterinary products including vaccines, livestock equipment, feeds, and animal ear tags.
                    </p>
                </div>
                <div class="service-card">
                    <h4>🎓 Training Programs for Farmers</h4>
                    <p>
                        Comprehensive training for farmers, extension workers, and livestock stakeholders in production, husbandry, and animal health management.
                    </p>
                </div>
            </div>
            <div class="service-card landing-service-full">
                <h4>📊 Farm Management &amp; Consultancy</h4>
                <p>
                    Professional consultancy services in livestock breeding, nutrition, housing design, and poultry production.
                </p>
            </div>
        </div>


        <!-- Modern Rich Landing Footer -->
        <footer class="modern-landing-footer">
            <div class="modern-landing-footer-top">
                <div class="modern-landing-footer-brand">
                    <div class="brand-header">
                        <img src="images/logo.png" alt="MIGS Logo" class="modern-landing-logo" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCI+PGNpcmNsZSBjeD0iMjUiIGN5PSIyNSIgcj0iMjQiIGZpbGw9IiM0Q0FGNTAiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZmlsbD0id2hpdGUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtc2l6ZT0iMjAiPk08L3RleHQ+PC9zdmc+'">
                        <div>
                            <h3>Msantha Investments &amp; General Suppliers</h3>
                            <p class="brand-slogan">Your Trusted Partner in Livestock &amp; Poultry Production</p>
                        </div>
                    </div>
                    <p class="brand-address">📍 Mawira area along Chipamba Road, Liwonde, Machinga District, Malawi</p>
                </div>

                <div class="modern-landing-footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home Page</a></li>
                        <li><a href="login.php">System Login Portal</a></li>
                        <li><a href="tel:+265888899620">Customer Support</a></li>
                    </ul>
                </div>

                <div class="modern-landing-footer-contact">
                    <h4>Contact Info</h4>
                    <div class="contact-pill">
                        <span class="icon">📞</span>
                        <div>
                            <span class="label">Phone Support</span>
                            <a href="tel:+265888899620" class="value">+265 888 899 620 / +265 999 899 620</a>
                        </div>
                    </div>
                    <div class="contact-pill">
                        <span class="icon">✉️</span>
                        <div>
                            <span class="label">Email Address</span>
                            <a href="mailto:icchipeta@gmail.com" class="value">icchipeta@gmail.com</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modern-landing-footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <strong>Msantha Investments and General Suppliers (MIGS)</strong>. All Rights Reserved.</p>
                <span class="badge">MIGS v2.4 System</span>
            </div>
        </footer>

    </div>
</body>
</html>
