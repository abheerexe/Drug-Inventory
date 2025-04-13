<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
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
$inventory_err = "";
$inventory = [];
$recent_requests = [];

// --- Fetch Dashboard Data ---
$total_drugs_inventory = 0;
$total_inventory_quantity = 0;
$low_stock_drugs_count = 0;
$total_requests_count = 0;
$pending_requests_count = 0;
$approved_requests_count = 0;
$rejected_requests_count = 0;
$delivered_requests_count = 0;

// 1. Count Total Drugs in Institution Inventory
$total_drugs_inventory_result = executeQuery($conn, "SELECT COUNT(DISTINCT drug_id) AS count FROM institution_inventory WHERE institution_id = ?", 'i', $institution_id);
if ($total_drugs_inventory_result) {
    $total_drugs_inventory = $total_drugs_inventory_result->fetch_assoc()['count'];
}

// 2. Sum of Total Inventory Quantity
$total_inventory_quantity_result = executeQuery($conn, "SELECT SUM(quantity) AS total_quantity FROM institution_inventory WHERE institution_id = ?", 'i', $institution_id);
if ($total_inventory_quantity_result) {
    $total_inventory_quantity = $total_inventory_quantity_result->fetch_assoc()['total_quantity'] ?: 0; // Use 0 if SUM is NULL
}


// 3. Count Low Stock Drugs (Quantity < 100)
$low_stock_threshold = 100;
$low_stock_drugs_count_result = executeQuery($conn, "SELECT COUNT(*) AS count FROM institution_inventory WHERE institution_id = ? AND quantity < ?", 'ii', $institution_id, $low_stock_threshold);
if ($low_stock_drugs_count_result) {
    $low_stock_drugs_count = $low_stock_drugs_count_result->fetch_assoc()['count'];
}


// 4. Count Total Requests by Institution
$total_requests_count_result = executeQuery($conn, "SELECT COUNT(*) AS count FROM requests WHERE institution_id = ?", 'i', $institution_id);
if ($total_requests_count_result) {
    $total_requests_count = $total_requests_count_result->fetch_assoc()['count'];
}


// 5. Count Pending Requests by Institution
$pending_requests_count_result = executeQuery($conn, "SELECT COUNT(*) AS count FROM requests WHERE institution_id = ? AND status = 'Pending'", 'i', $institution_id);
if ($pending_requests_count_result) {
    $pending_requests_count = $pending_requests_count_result->fetch_assoc()['count'];
}

// 6. Count Approved Requests by Institution
$approved_requests_count_result = executeQuery($conn, "SELECT COUNT(*) AS count FROM requests WHERE institution_id = ? AND status = 'Approved'", 'i', $institution_id);
if ($approved_requests_count_result) {
    $approved_requests_count = $approved_requests_count_result->fetch_assoc()['count'];
}

// 7. Count Rejected Requests by Institution
$rejected_requests_count_result = executeQuery($conn, "SELECT COUNT(*) AS count FROM requests WHERE institution_id = ? AND status = 'Rejected'", 'i', $institution_id);
if ($rejected_requests_count_result) {
    $rejected_requests_count = $rejected_requests_count_result->fetch_assoc()['count'];
}

// 8. Count Delivered Requests by Institution
$delivered_requests_count_result = executeQuery($conn, "SELECT COUNT(*) AS count FROM requests WHERE institution_id = ? AND status = 'Delivered'", 'i', $institution_id);
if ($delivered_requests_count_result) {
    $delivered_requests_count = $delivered_requests_count_result->fetch_assoc()['count'];
}



// 9. Fetch Recent Requests for the institution (e.g., last 5) - NO CHANGES
$recent_requests_sql = "SELECT r.id, r.quantity, r.request_date, r.status, d.name AS drug_name
                         FROM requests r
                         JOIN drugs d ON r.drug_id = d.id
                         WHERE r.institution_id = ?
                         ORDER BY r.request_date DESC
                         LIMIT 5";
$recent_requests_result = executeQuery($conn, $recent_requests_sql, 'i', $institution_id);
if ($recent_requests_result) {
    $recent_requests = $recent_requests_result->fetch_all(MYSQLI_ASSOC);
}


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

        <div class="dashboard-content">

            <h3>Inventory Summary</h3>
             <div class="row">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h4>Total Drug Types in Inventory</h4>
                        <p><?php echo htmlspecialchars($total_drugs_inventory); ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h4>Total Inventory Quantity</h4>
                        <p><?php echo htmlspecialchars($total_inventory_quantity); ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h4>Low Stock Drugs</h4>
                        <p><?php echo htmlspecialchars($low_stock_drugs_count); ?></p>
                    </div>
                </div>
            </div>


            <h3 style="margin-top: 30px;">Request Summary</h3>
             <div class="row">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h4>Total Requests</h4>
                        <p><?php echo htmlspecialchars($total_requests_count); ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h4>Pending Requests</h4>
                        <p><?php echo htmlspecialchars($pending_requests_count); ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h4>Approved Requests</h4>
                        <p><?php echo htmlspecialchars($approved_requests_count); ?></p>
                    </div>
                </div>
                 <div class="col-md-3">
                    <div class="dashboard-card">
                        <h4>Delivered Requests</h4>
                        <p><?php echo htmlspecialchars($delivered_requests_count); ?></p>
                    </div>
                </div>
            </div>


            <h3 style="margin-top: 30px;">Recent Request Status</h3>
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