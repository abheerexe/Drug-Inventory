<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drug Inventory Management System - Home</title>
    <link rel="stylesheet" href="home_style.css"> <!-- Link to your existing style.css file -->
</head>
<body>
    <div class="wrapper">
        <header>
            <nav>
                <button class="hamburger-menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <ul>
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="drug_search.php">Search for drugs</a></li>
                    <!-- Add more navigation links as needed -->
                </ul>
            </nav>
            <div class="header-content">
                <h1>Streamline Your Drug Inventory</h1>
                <p>Efficiently manage your drug inventory, reduce errors, and ensure optimal stock levels with our comprehensive system.</p>
                <a href="./login.php" class="button-40">Get Started</a> <a href="features.php" class="button-40 secondary-button">Learn More</a>
            </div>
        </header>

        <main class="dashboard-content"> <!-- Using dashboard-content class for main content styling -->
            <section class="hero-section">
                <div class="hero-image">
                    <img src="med.jpg" alt="Pharmacy Inventory Management" loading="lazy"> <!-- Replace with your image -->
                </div>
                <div class="hero-text">
                    <h2>Take Control of Your Pharmacy Stock</h2>
                    <p>Our intuitive drug inventory management system is designed to simplify your workflow, improve accuracy, and save valuable time. From tracking stock levels to generating reports, we provide the tools you need to optimize your inventory process.</p>
                    <ul>
                        <li>Real-time Inventory Tracking</li>
                        <li>Automated Stock Alerts</li>
                        <li>Comprehensive Reporting & Analytics</li>
                        <li>User-Friendly Interface</li>
                    </ul>
                </div>
            </section>

            <section class="features-section">
                <h2>Key Features</h2>
                <div class="features-grid">
                    <div class="feature">
                        <i class="fas fa-cubes"></i> <!-- Example Icon - Font Awesome (you'd need to include Font Awesome if using) -->
                        <h3>Inventory Tracking</h3>
                        <p>Monitor stock levels in real-time, ensuring you always know what's on hand and preventing stockouts.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-bell"></i> <!-- Example Icon -->
                        <h3>Automated Alerts</h3>
                        <p>Set up low-stock alerts to automatically notify you when it's time to reorder, minimizing disruptions.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-chart-bar"></i> <!-- Example Icon -->
                        <h3>Reporting & Analytics</h3>
                        <p>Generate detailed reports on inventory trends, drug usage, and more to make informed decisions.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-user-friends"></i> <!-- Example Icon -->
                        <h3>Multi-User Access</h3>
                        <p>Securely manage user roles and permissions, allowing your team to collaborate effectively within the system.</p>
                    </div>
                </div>
            </section>

            <section class="call-to-action-section">
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of pharmacies and institutions already benefiting from our efficient drug inventory management system.</p>
                <a href="./signup.php" class="button-40 large-button">Sign Up for Free</a>
            </section>
        </main>

        <footer class="footer-content">
            <p>© <?php echo date("Y"); ?> Drug Inventory Management System. All rights reserved.</p>
            <div>
                <a href="terms.php">Terms of Service</a> | <a href="privacy.php">Privacy Policy</a> | <a href="contact.php">Contact Us</a>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerMenu = document.querySelector('.hamburger-menu');
            const nav = document.querySelector('nav');

            if (hamburgerMenu && nav) {
                hamburgerMenu.addEventListener('click', () => {
                    nav.classList.toggle('nav-active');
                });
            }
        });
    </script>
</body>
</html>