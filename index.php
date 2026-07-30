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

        <!-- Main Hero Section -->
        <div class="landing-hero-banner" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;">
            <div style="flex: 1; min-width: 300px;">
                <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Established 2006</span>
                <h1 style="font-size: 2.5rem; margin: 1rem 0 0.5rem; line-height: 1.2;">Msantha Pigs & Livestock Management System</h1>
                <p style="font-size: 1.15rem; opacity: 0.95; margin-bottom: 1.5rem;">Your Trusted Partner in Livestock & Poultry Production and Veterinary Services.</p>
                <div style="display: flex; gap: 1rem;">
                    <a href="login.php" class="btn" style="background: #ffffff; color: var(--primary-color); font-weight: 700; padding: 0.8rem 1.5rem;">Access System Portal</a>
                </div>
            </div>
            <div style="flex: 1; min-width: 300px; text-align: center;">
                <img src="images/landing_pigs.jpg" alt="Msantha Farm Pigs" style="max-width: 100%; height: auto; border-radius: 16px; box-shadow: 0 12px 24px rgba(0,0,0,0.3); border: 3px solid #ffffff;">
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
            <h3 style="font-size: 1.5rem; margin-bottom: 1.5rem; color: var(--primary-color); text-align: center;">Our Services & Specialties</h3>
            <div class="landing-grid">
                <div class="service-card">
                    <h4>🐄 Livestock & Poultry Supply</h4>
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
                    <h4>💉 Veterinary Products & Ear Tags</h4>
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
                <h4>📊 Farm Management & Consultancy</h4>
                <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.6;">
                    Professional consultancy services in livestock breeding, nutrition, housing design, and poultry production.
                </p>
            </div>
        </div>

        <!-- Official Brochures Showcase -->
        <div class="card" style="margin-bottom: 2rem;">
            <h3 style="font-size: 1.5rem; margin-bottom: 1.5rem; color: var(--primary-color); text-align: center;">Official Company Brochures</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div style="text-align: center; border: 1px solid var(--border-color); padding: 10px; border-radius: 12px;">
                    <img src="images/brochure1.jpg" alt="MIGS Official Brochure 1" style="max-width: 100%; height: auto; border-radius: 8px;">
                    <p style="margin-top: 10px; font-weight: 600; color: var(--text-main);">Brochure 1: Overview & Services</p>
                </div>
                <div style="text-align: center; border: 1px solid var(--border-color); padding: 10px; border-radius: 12px;">
                    <img src="images/brochure2.jpg" alt="MIGS Official Brochure 2" style="max-width: 100%; height: auto; border-radius: 8px;">
                    <p style="margin-top: 10px; font-weight: 600; color: var(--text-main);">Brochure 2: Livestock & Veterinary</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="landing-footer" style="background: #ffffff; padding: 1.5rem 2rem; border-radius: 16px; text-align: center; box-shadow: 0 -2px 10px rgba(0,0,0,0.03); margin-top: 2rem;">
            <p style="font-weight: 700; color: var(--primary-color); margin-bottom: 0.5rem;">Msantha Investments and General Suppliers (MIGS)</p>
            <p style="color: var(--text-main); font-size: 0.95rem; margin-bottom: 0.5rem;">P.O. Box 250, Liwonde, Machinga, Malawi</p>
            <p style="color: var(--text-main); font-size: 0.95rem;">
                📞 <strong>Phone:</strong> +265 888899620 / +265 999899620 &nbsp;|&nbsp; ✉️ <strong>Email:</strong> icchipeta@gmail.com / icchipeta@yahoo.co.uk
            </p>
        </footer>

    </div>
</body>
</html>
