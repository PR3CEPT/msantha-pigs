            </main>
            
            <!-- Landing Page Style Modern Footer -->
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
                        <h4>Quick Navigation</h4>
                        <ul>
                            <li><a href="dashboard.php">Dashboard</a></li>
                            <li><a href="pigs.php">Pig Records</a></li>
                            <li><a href="reports.php">Reports &amp; Analytics</a></li>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <li><a href="users.php">User Management</a></li>
                            <?php endif; ?>
                            <li><a href="profile.php">My Profile</a></li>
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
    </div>
</body>
</html>
