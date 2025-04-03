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
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Added viewport meta tag -->
    <link rel="stylesheet" href="style.css"> <!-- Linked to style.css -->
    <!-- Bootstrap CSS link and inline styles removed -->
</head>
<body>
    <div class="wrapper">
        <h2>View Drug Requests</h2>
        <nav class="no-print">
            <button class="hamburger-menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="inventory.php" >View Inventory</a></li>
            <li><a href="request_drug.php">Request Drugs</a></li>
            <li><a href="requests.php" class="active" style="text-decoration: underline;text-underline-offset:0.2em;">View Requests</a></li> <!-- Added active class to View Requests link -->
            <li><a href="bill.php">Generate Bill</a></li>
</ul>
</nav>
        <p>Viewing Requests for: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></p>



        <?php if (!empty($requests_err)): ?>
            <div class="alert alert-danger"><?php echo $requests_err; ?></div> <!-- Kept alert and alert-danger classes -->
        <?php endif; ?>

        <div class="table-responsive"> <!-- Added table-responsive class for table -->
            <table class="table"> <!-- Added table class for styling -->
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

        <p class="no-print"><a href="../admin/logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a></p> <!-- Kept btn and btn-danger classes -->
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
$conn->close();
?>