<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RangRiti - Modern Wedding Planning</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <div class="logo">RangRiti</div>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#about">About</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
                    <a href="includes/logout.php" class="btn btn-outline">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Get Started</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <h1>Plan Your Dream Wedding</h1>
                <p>Organize every detail of your special day with our elegant and intuitive wedding planning tools.</p>
                <div class="cta-buttons">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn btn-primary btn-large">Start Planning</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary btn-large">Start Planning</a>
                    <?php endif; ?>
                    <a href="#features" class="btn btn-outline btn-large">Learn More</a>
                </div>
            </div>
        </section>

        <section id="features" class="features">
            <h2>Everything You Need</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <img src="assets/images/checklist.svg" alt="Checklist">
                    <h3>Wedding Checklist</h3>
                    <p>Stay organized with our comprehensive wedding planning checklist.</p>
                </div>
                <div class="feature-card">
                    <img src="assets/images/budget.svg" alt="Budget">
                    <h3>Budget Planner</h3>
                    <p>Track expenses and manage your wedding budget effortlessly.</p>
                </div>
                <div class="feature-card">
                    <img src="assets/images/guests.svg" alt="Guests">
                    <h3>Guest Management</h3>
                    <p>Manage your guest list and RSVPs in one place.</p>
                </div>
                <div class="feature-card">
                    <img src="assets/images/vendors.svg" alt="Vendors">
                    <h3>Vendor Contacts</h3>
                    <p>Keep all your vendor information organized and accessible.</p>
                </div>
            </div>
        </section>

        <section id="about" class="about">
            <div class="about-content">
                <h2>Why Choose RangRiti?</h2>
                <p>RangRiti is more than just a wedding planner - it's your personal wedding planning assistant. We combine beautiful design with powerful features to make your wedding planning journey smooth and enjoyable.</p>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">RangRiti</div>
            <div class="footer-links">
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms of Service</a>
                <a href="contact.php">Contact Us</a>
            </div>
            <div class="footer-social">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="Pinterest"><i class="fab fa-pinterest"></i></a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> RangRiti. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
