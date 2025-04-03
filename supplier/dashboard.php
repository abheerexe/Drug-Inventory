<?php
session_start();
require_once '../database.php';

// Check if the user is logged in and is Supplier
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'Supplier') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supplier Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style.css"> <!-- Link to your main style.css -->
    <style>
        /* Add any specific styling for supplier dashboard here */
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Supplier Dashboard</h2>
        <p>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong> (Supplier)!</p>

        <nav>
            <ul>
                <li><a href="dashboard.php"  style="text-decoration: underline;text-underline-offset:0.2em;">Dashboard</a></li>
                <li><a href="requests.php">Manage Requests</a></li>
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

        <a href="../admin/logout.php" class="button-40 btn ml-3">Sign Out of Your Account</a>
    </div>
</body>
</html>
<?php
$conn->close();
?>