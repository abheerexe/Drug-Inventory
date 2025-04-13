<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
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

// --- Fetch Dashboard Data ---
$drug_count = 0;
$institution_count = 0;
$user_count = 0;
$pending_requests_count = 0;
$approved_requests_count = 0;
$rejected_requests_count = 0;
$delivered_requests_count = 0;
$recent_requests = [];
$top_institutions_requests = [];
$low_stock_drugs = [];


// 1. Count Drugs
$drug_count_result = executeQuery($conn, "SELECT COUNT(*) as count FROM drugs");
if ($drug_count_result) {
    $drug_count = $drug_count_result->fetch_assoc()['count'];
}

// 2. Count Institutions
$institution_count_result = executeQuery($conn, "SELECT COUNT(*) as count FROM institutions");
if ($institution_count_result) {
    $institution_count = $institution_count_result->fetch_assoc()['count'];
}

// 3. Count Users
$user_count_result = executeQuery($conn, "SELECT COUNT(*) as count FROM users");
if ($user_count_result) {
    $user_count = $user_count_result->fetch_assoc()['count'];
}

// 4. Count Pending Requests
$pending_requests_count_result = executeQuery($conn, "SELECT COUNT(*) as count FROM requests WHERE status = 'Pending'");
if ($pending_requests_count_result) {
    $pending_requests_count = $pending_requests_count_result->fetch_assoc()['count'];
}

// 5. Count Approved Requests
$approved_requests_count_result = executeQuery($conn, "SELECT COUNT(*) as count FROM requests WHERE status = 'Approved'");
if ($approved_requests_count_result) {
    $approved_requests_count = $approved_requests_count_result->fetch_assoc()['count'];
}

// 6. Count Rejected Requests
$rejected_requests_count_result = executeQuery($conn, "SELECT COUNT(*) as count FROM requests WHERE status = 'Rejected'");
if ($rejected_requests_count_result) {
    $rejected_requests_count = $rejected_requests_count_result->fetch_assoc()['count'];
}

// 7. Count Delivered Requests
$delivered_requests_count_result = executeQuery($conn, "SELECT COUNT(*) as count FROM requests WHERE status = 'Delivered'");
if ($delivered_requests_count_result) {
    $delivered_requests_count = $delivered_requests_count_result->fetch_assoc()['count'];
}


// 8. Fetch Recent Pending Requests
$recent_requests_sql = "SELECT r.id, r.quantity, r.request_date, i.name AS institution_name, d.name AS drug_name
                         FROM requests r
                         JOIN institutions i ON r.institution_id = i.id
                         JOIN drugs d ON r.drug_id = d.id
                         WHERE r.status = 'Pending'
                         ORDER BY r.request_date DESC
                         LIMIT 5";
$recent_requests_result = executeQuery($conn, $recent_requests_sql);
if ($recent_requests_result) {
    $recent_requests = $recent_requests_result->fetch_all(MYSQLI_ASSOC);
}


// 9. Fetch Top Institutions by Request Count
$top_institutions_sql = "SELECT i.name AS institution_name, COUNT(r.id) AS request_count
                          FROM requests r
                          JOIN institutions i ON r.institution_id = i.id
                          GROUP BY i.name
                          ORDER BY request_count DESC
                          LIMIT 5";
$top_institutions_result = executeQuery($conn, $top_institutions_sql);
if ($top_institutions_result) {
    $top_institutions_requests = $top_institutions_result->fetch_all(MYSQLI_ASSOC);
}

// 10. Fetch Low Stock Drugs (Threshold: Quantity < 100)
$low_stock_threshold = 100;
$low_stock_drugs_sql = "SELECT d.name AS drug_name, ii.quantity, i.name AS institution_name
                          FROM institution_inventory ii
                          JOIN drugs d ON ii.drug_id = d.id
                          JOIN institutions i ON ii.institution_id = i.id
                          WHERE ii.quantity < ?";
$low_stock_drugs_result = executeQuery($conn, $low_stock_drugs_sql, 'i', $low_stock_threshold);
if ($low_stock_drugs_result) {
    $low_stock_drugs = $low_stock_drugs_result->fetch_all(MYSQLI_ASSOC);
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
                <li><a href="admin.php" class="<?php if ($currentPage == 'admin.php') echo 'active'; ?>" style="text-decoration: underline;text-underline-offset:0.2em;">Dashboard</a></li>
                <li><a href="institutions.php" class="<?php if ($currentPage == 'institutions.php') echo 'active'; ?>">Institutions</a></li>
                <li><a href="users.php"   class="<?php if ($currentPage == 'users.php') echo 'active'; ?>">Users</a></li>
                <li><a href="drugs.php" class="<?php if ($currentPage == 'drugs.php') echo 'active'; ?>">Drugs</a></li>
                <li><a href="requests.php" class="<?php if ($currentPage == 'requests.php') echo 'active'; ?>">Manage Requests</a></li>
                <li><a href="logout.php" class="<?php if ($currentPage == 'logout.php') echo 'active'; ?>">Logout</a></li>
            </ul>
        </nav>
        <hr/>

        <div class="dashboard-content">
            <h3>System Summary</h3>
            <div class="row">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h4>Total Drugs</h4>
                        <p><?php echo htmlspecialchars($drug_count); ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h4>Total Institutions</h4>
                        <p><?php echo htmlspecialchars($institution_count); ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h4>Total Users</h4>
                        <p><?php echo htmlspecialchars($user_count); ?></p>
                    </div>
                </div>
                 <div class="col-md-3">
                    <div class="dashboard-card">
                        <h4>Pending Requests</h4>
                        <p><?php echo htmlspecialchars($pending_requests_count); ?></p>
                    </div>
                </div>
            </div>

             <div class="row">  <!-- NEW ROW FOR REQUEST STATUS BREAKDOWN -->
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h4>Approved Requests</h4>
                        <p><?php echo htmlspecialchars($approved_requests_count); ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h4>Rejected Requests</h4>
                        <p><?php echo htmlspecialchars($rejected_requests_count); ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h4>Delivered Requests</h4>
                        <p><?php echo htmlspecialchars($delivered_requests_count); ?></p>
                    </div>
                </div>

            </div>


            <h3 style="margin-top: 30px;">Recent Pending Requests</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Institution</th>
                            <th>Drug Name</th>
                            <th>Quantity</th>
                            <th>Request Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_requests)): ?>
                            <tr><td colspan="5">No pending requests.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['id']); ?></td>
                                    <td><?php echo htmlspecialchars($request['institution_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['drug_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

             <h3 style="margin-top: 30px;">Top Institutions by Request Count</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Institution Name</th>
                            <th>Request Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_institutions_requests)): ?>
                            <tr><td colspan="2">No institution request data found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($top_institutions_requests as $institution_data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($institution_data['institution_name']); ?></td>
                                    <td><?php echo htmlspecialchars($institution_data['request_count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>


             <h3 style="margin-top: 30px;">Low Stock Drugs (Quantity < <?php echo htmlspecialchars($low_stock_threshold); ?>)</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Drug Name</th>
                            <th>Quantity on Hand</th>
                            <th>Institution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($low_stock_drugs)): ?>
                            <tr><td colspan="3">No drugs are running low on stock.</td></tr>
                        <?php else: ?>
                            <?php foreach ($low_stock_drugs as $low_stock_drug): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($low_stock_drug['drug_name']); ?></td>
                                    <td><?php echo htmlspecialchars($low_stock_drug['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($low_stock_drug['institution_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>


        </div>


        <hr>

        <a href="logout.php" class="button-40 btn ml-3">Sign Out of Your Account</a>
    </div>
</body>

</html>
<?php
//Close connection after all operations are complete
$conn->close();
?>