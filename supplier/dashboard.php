<?php
session_start();
require_once '../database.php';

// Check if the user is logged in and is Supplier
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'Supplier') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];

// Determine the current page filename to dynamically set the 'active' class
$currentPage = basename($_SERVER['PHP_SELF']); // Gets the filename of the current script

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supplier Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="supplier.css"> <!-- Use your supplier-specific stylesheet -->
    <!-- Removed Bootstrap CSS link - assuming your style.css handles all styling -->
    <style>
        /* Inline styles are generally discouraged, move these to your CSS file if needed */
        /* Consider removing this <style> block and managing all styles in CSS files */
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Supplier Dashboard</h2>
        <p>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong> (Supplier)!</p>

        <nav>
            <ul>
                <li><a href="dashboard.php" class="<?php if ($currentPage == 'dashboard.php') echo 'active'; ?>" style="text-decoration: underline;text-underline-offset:0.2em;">Dashboard</a></li>
                <li><a href="requests.php" class="<?php if ($currentPage == 'requests.php') echo 'active'; ?>">Manage Requests</a></li>
                <li><a href="../admin/logout.php">Logout</a></li>
            </ul>
        </nav>
        <hr/>

        <div class="dashboard-content">
            <h3>Supplier Actions</h3>
            <p>Use the navigation menu to manage drug requests and supply chain activities.</p>
            <ul>
                <li><strong>Manage Requests:</strong> View and process pending drug requests from institutions. Approve requests and mark them as delivered once fulfilled.</li>
                <!-- Add more descriptions for supplier functionalities as you implement them -->
            </ul>
        </div>

        <hr>

        <a href="../admin/logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerMenu = document.querySelector('.hamburger-menu'); // Ensure hamburger menu is still not needed, if yes then uncomment this and related script
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
<?php
$conn->close();
?>