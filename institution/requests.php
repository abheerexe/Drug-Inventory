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
$requests_err = "";
$requests = [];

// Fetch institution requests
$requests_sql = "SELECT r.id, r.quantity, r.request_date, r.status, r.supplier_notes, d.name AS drug_name
                  FROM requests r
                  JOIN drugs d ON r.drug_id = d.id
                  WHERE r.institution_id = ?
                  ORDER BY r.request_date DESC"; // Order by request date, newest first

$requests_result = executeQuery($conn, $requests_sql, 'i', $institution_id);

if ($requests_result) {
    $requests = $requests_result->fetch_all(MYSQLI_ASSOC);
} else {
    $requests_err = "Error fetching requests: " . $conn->error;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Requests</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 90%; padding: 20px; margin: 0 auto; } /* Wider wrapper */
        .dashboard-nav { margin-bottom: 20px; }
        .dashboard-nav a { margin-right: 10px; }
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .table tbody tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>View Drug Requests</h2>
        <p>Viewing Requests for: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></p>

        <div class="dashboard-nav">
            <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
            <a href="inventory.php" class="btn btn-primary">View Inventory</a>
            <a href="request_drug.php" class="btn btn-primary">Request Drugs</a>
            <a href="requests.php" class="btn btn-primary">View Requests</a>
        </div>

        <?php if (!empty($requests_err)): ?>
            <div class="alert alert-danger"><?php echo $requests_err; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Drug Name</th>
                        <th>Quantity Requested</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Supplier Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="6">No requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['id']); ?></td>
                                <td><?php echo htmlspecialchars($request['drug_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                                <td><?php echo htmlspecialchars($request['status']); ?></td>
                                <td><?php echo htmlspecialchars($request['supplier_notes'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <p><a href="../logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a></p>
    </div>
</body>
</html>
<?php
$conn->close();
?>