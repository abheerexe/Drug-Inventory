<?php
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

session_start();
require_once '../database.php'; // Ensure this path is correct!

// Check if the user is logged in and is Institution Staff
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'Institution Staff') {
    header("location: ../login.php");
    exit;
}

$institution_id = $_SESSION['institution_id'];
$username = $_SESSION['username'];
$requests_err = "";
$requests = [];

// Fetch institution requests, grouped by date
$requests_sql = "SELECT DATE(r.request_date) AS request_date, GROUP_CONCAT(r.id) AS request_ids
                  FROM requests r
                  WHERE r.institution_id = ? AND r.status = 'Approved'
                  GROUP BY DATE(r.request_date)
                  ORDER BY DATE(r.request_date) DESC";

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
    <title>View Bills</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wrapper">
        <h2>View Bills</h2>
        <p>Viewing Bills for: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></p>

        <nav>
            <ul>
            <a href="dashboard.php">Dashboard</a>
            <a href="inventory.php" >View Inventory</a>
            <a href="request_drug.php">Request Drugs</a>
            <a href="requests.php">View Requests</a>
            <a href="bill.php" style="text-decoration: underline;text-underline-offset:0.2em;">Generate Bill</a>
</ul>
</nav>

        <?php if (!empty($requests_err)): ?>
            <div class="alert alert-danger"><?php echo $requests_err; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Request Date</th>
                        <th>Request IDs</th>
                        <th>Generate Bill</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="6">No requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                                <td><?php echo htmlspecialchars($request['request_ids']); ?></td>
                                <td>
                                    <a href="generate_bill.php?date=<?php echo urlencode($request['request_date']); ?>&request_ids=<?php echo urlencode($request['request_ids']); ?>">Generate Bill</a>
                                </td>
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
$conn->close();
?>