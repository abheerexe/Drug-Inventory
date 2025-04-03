<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wrapper">
        <h2>Institution Dashboard</h2>
        <nav class="no-print">
            <button class="hamburger-menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul>
                <li><a href="dashboard.php" class="active" style="text-decoration: underline;text-underline-offset:0.2em;">Dashboard</a></li>
                <li><a href="inventory.php">View Inventory</a></li>
                <li><a href="request_drug.php">Request Drugs</a></li>
                <li><a href="requests.php">View Requests</a></li>
                <li><a href="bill.php">Generate Bill</a></li>
            </ul>
        </nav>
        <p>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>!</p>



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

        <p class="no-print"><a href="../admin/logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a></p>
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

<?php
// Close connection
$conn->close();
?>