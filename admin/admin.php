<?php
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
?>
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once '../database.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'Admin') {
    header("location: ../login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="wrapper">
        <h2>Admin Dashboard</h2>
        <nav>
            <ul>
                <li><a href="admin.php" style="text-decoration: underline;text-underline-offset:0.2em;">Dashboard</a></li><!-- Link to admin.php - which is this page itself -->
                <li><a href="institutions.php">Institutions</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="drugs.php">Drugs</a></li>
                <li><a href="requests.php">Manage Requests</a></li>  
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        <hr/>

        <div class="dashboard-content">
            <h3>Welcome to the Admin Dashboard!</h3>
            <p>Use the navigation menu above to manage different sections of the application:</p>
            <ul>
                <li><strong>Institutions:</strong> Add, view, and manage institutions and vendors.</li>
                <li><strong>Users:</strong> Add, view, and manage system users (Institution Staff).</li>
                <li><strong>Drugs:</strong> Add, view, and manage drug information.</li>
                <!-- Add more list items as you expand your dashboard -->
            </ul>
            <p>This is your central control panel.  Select an option from the menu to begin.</p>
        </div>


        <hr>

        <a href="../logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a>
    </div>
</body>

</html>
<?php
//Close connection if you were using it for anything specific on the dashboard page
// $conn->close(); // No database operations on the dashboard page in this example, so no need to close here.
?>