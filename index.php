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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Msantha Investments and General Suppliers (MIGS) - Management System</title>
    <link rel="stylesheet" href="css/style.css">
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
<body class="landing-page" style="background: #F4F7F6; min-height: 100vh;">
    <div class="landing-container" style="max-width: 1200px; margin: 0 auto; padding: 1.5rem;">
        
        <!-- Header Topbar -->
        <header class="landing-header" style="background: #ffffff; padding: 1rem 2rem; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div class="logo-container" style="display: flex; align-items: center; gap: 15px;">
                <img src="images/logo.png" alt="MIGS Official Logo" class="landing-logo" style="height: 65px; width: 65px; border-radius: 50%; background: #fff; padding: 2px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                <div>
                    <h2 style="color: var(--primary-color); font-size: 1.3rem; margin: 0;">Msantha Investments and General Suppliers</h2>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">MIGS — Liwonde, Machinga, Malawi</p>
                </div>
            </div>
            <a href="login.php" class="btn btn-primary" style="padding: 0.8rem 1.8rem; font-size: 1rem;">System Login &rarr;</a>
        </header>

        <!-- Main Hero Section with Livestock & Poultry Background Photo -->
        <div class="landing-hero-banner" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;">
            <div style="flex: 1; min-width: 300px; z-index: 2;">
                <span style="background: rgba(255,255,255,0.25); padding: 5px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; backdrop-filter: blur(4px);">Established 2006</span>
                <h1 style="font-size: 2.5rem; margin: 1rem 0 0.5rem; line-height: 1.2; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Msantha Livestock &amp; Poultry Management System</h1>
                <p style="font-size: 1.15rem; opacity: 0.95; margin-bottom: 1.2rem; text-shadow: 0 1px 3px rgba(0,0,0,0.3);">Your Trusted Partner in Livestock Production, Poultry Incubation &amp; Veterinary Services across Malawi.</p>

                <div style="display: flex; gap: 1rem;">
                    <a href="login.php" class="btn" style="background: #ffffff; color: var(--primary-color); font-weight: 700; padding: 0.8rem 1.6rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">Access System Portal &rarr;</a>
                </div>
            </div>
            <div style="flex: 1; min-width: 300px; text-align: center; z-index: 2;">
                <img src="images/landing_pigs.jpg" alt="Msantha Livestock & Farm" style="max-width: 100%; height: auto; border-radius: 16px; box-shadow: 0 12px 24px rgba(0,0,0,0.35); border: 3px solid #ffffff;">
            </div>
        </div>

        <!-- About Us & Quote Card -->
        <div class="card" style="margin-bottom: 2rem; border-top: 4px solid var(--primary-color);">
            <h3 style="font-size: 1.5rem; margin-bottom: 1rem; color: var(--primary-color);">About Msantha Investments and General Suppliers (MIGS)</h3>
            <p style="font-size: 1.05rem; line-height: 1.7; color: var(--text-main); margin-bottom: 1rem;">
                Msantha Investments and General Suppliers (MIGS) is based in <strong>Liwonde, Mawira area along Chipamba Road in Machinga District</strong>. Since our establishment in 2006, we have been dedicated to livestock and poultry production as well as comprehensive veterinary services across Malawi.
            </p>
            <p style="font-size: 1.05rem; line-height: 1.7; color: var(--text-main);">
                We specialize in supplying high-quality livestock, poultry, breeding stock, and farm management technology to farmers, institutions, and agricultural organizations.
            </p>
            
            <div class="quote-box">
                <p style="margin: 0; font-size: 1.1rem;">"We work as a team. Members of the organization have a reputable record of services, they set a pace for others to follow. <strong>Try us, and you will never regret.</strong>"</p>
            </div>
        </div>

        <!-- Services Grid (Directly from Brochure) -->
        <div style="margin-bottom: 2rem;">
            <h3 style="font-size: 1.5rem; margin-bottom: 1.5rem; color: var(--primary-color); text-align: center;">Our Services &amp; Specialties</h3>
            <div class="landing-grid">
                <div class="service-card">
                    <h4>🐄 Livestock &amp; Poultry Supply</h4>
                    <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.6;">
                        Supplying diverse livestock species including goats, sheep, pigs, rabbits, graded dairy cattle, Malawi Zebu cattle, and poultry (Local chickens, Mikolongwe, Kuroiler, Turkeys, and Guinea Fowls).
                    </p>
                </div>
                <div class="service-card">
                    <h4>🐣 Poultry Egg Incubation</h4>
                    <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.6;">
                        State-of-the-art incubation and hatching services for various poultry eggs with high hatchability rates.
                    </p>
                </div>
                <div class="service-card">
                    <h4>💉 Veterinary Products &amp; Ear Tags</h4>
                    <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.6;">
                        Quality veterinary products including vaccines, livestock equipment, feeds, and animal ear tags.
                    </p>
                </div>
                <div class="service-card">
                    <h4>🎓 Training Programs for Farmers</h4>
                    <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.6;">
                        Comprehensive training for farmers, extension workers, and livestock stakeholders in production, husbandry, and animal health management.
                    </p>
                </div>
            </div>
            <div class="service-card" style="max-width: 600px; margin: 0 auto;">
                <h4>📊 Farm Management &amp; Consultancy</h4>
                <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.6;">
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
