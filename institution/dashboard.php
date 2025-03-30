<?php
session_start();
require_once '../database.php';

// Check if the user is logged in and is Institution Staff
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'Institution Staff') {
    header("location: ../login.php");
    exit;
}

$institution_id = $_SESSION['institution_id'];
$username = $_SESSION['username'];

// Fetch recent requests for the institution (e.g., last 5)
$recent_requests_sql = "SELECT r.id, r.quantity, r.request_date, r.status, d.name AS drug_name
                         FROM requests r
                         JOIN drugs d ON r.drug_id = d.id
                         WHERE r.institution_id = ?
                         ORDER BY r.request_date DESC
                         LIMIT 5";
$recent_requests_result = executeQuery($conn, $recent_requests_sql, 'i', $institution_id);
$recent_requests = $recent_requests_result ? $recent_requests_result->fetch_all(MYSQLI_ASSOC) : [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Institution Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <!-- <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 800px; padding: 20px; margin: 0 auto; }
        .dashboard-nav { margin-bottom: 20px; }
        .dashboard-nav a { margin-right: 10px; }
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .table tbody tr:nth-child(even) { background-color: #f9f9f9; }
    </style> -->
</head>
<body>
    <div class="wrapper">
        <h2>Institution Dashboard</h2>
        <p>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>!</p>

        <nav>
            <ul>
            <a href="dashboard.php"  style="text-decoration: underline;text-underline-offset:0.2em;">Dashboard</a>
            <a href="inventory.php">View Inventory</a>
            <a href="request_drug.php">Request Drugs</a>
            <a href="requests.php">View Requests</a>
            <a href="bill.php">Generate Bill</a>
</ul>
</nav>

        <h3>Recent Request Status</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Drug Name</th>
                        <th>Quantity</th>
                        <th>Request Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_requests)): ?>
                        <tr><td colspan="5">No recent requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['id']); ?></td>
                                <td><?php echo htmlspecialchars($request['drug_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                                <td><?php echo htmlspecialchars($request['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p><a href="../admin/logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a></p>
    </div>
</body>
</html>

<?php
// Close connection
$conn->close();
?>