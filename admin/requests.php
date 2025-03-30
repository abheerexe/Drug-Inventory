
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

$requests_err = "";
$requests = [];
$update_message = ""; // Success/error message for updates

// --- Handle Approve/Reject Actions ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_request'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; // 'Approve' or 'Reject'
    $supplier_notes = isset($_POST['supplier_notes']) ? trim($_POST['supplier_notes']) : '';
    // Validate action (optional, but good practice)
    if (!in_array($action, ['Approved', 'Rejected'])) {
        $update_message = "<div class='alert alert-danger'>Invalid action.</div>";
    } else {
        // Prepare an update statement
        $sql = "UPDATE requests SET status = ?, supplier_notes = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssi", $action, $supplier_notes, $request_id);

            if ($stmt->execute()) {
                $update_message = "<div class='alert alert-success'>Request " . htmlspecialchars($request_id) . " " . htmlspecialchars($action) . " successfully.</div>";

                // --- Inventory Update on Approval ---
                if ($action === 'Approved') {
                    // Fetch request details to get drug_id and institution_id
                    $request_details_sql = "SELECT drug_id, institution_id, quantity FROM requests WHERE id = ?";
                    $request_details_stmt = $conn->prepare($request_details_sql);
                    $request_details_stmt->bind_param("i", $request_id);
                    $request_details_stmt->execute();
                    $request_details_result = $request_details_stmt->get_result();
                    $request_details = $request_details_result->fetch_assoc();
                    $request_details_stmt->close();

                    if ($request_details) {
                        updateInventory($conn, $request_details['drug_id'], $request_details['institution_id'], $request_details['quantity']);
                    }
                }


            } else {
                $update_message = "<div class='alert alert-danger'>Error updating request: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $update_message = "<div class='alert alert-danger'>Error preparing update statement: " . $conn->error . "</div>";
        }
    }
}

function updateInventory($conn, $drugId, $institutionId, $quantity) {
    // Check if an inventory record already exists
    $checkSql = "SELECT * FROM institution_inventory WHERE drug_id = ? AND institution_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $drugId, $institutionId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Update existing record
        $existingInventory = $checkResult->fetch_assoc();
        $newQuantity = $existingInventory['quantity'] + $quantity;
        $updateInventorySql = "UPDATE institution_inventory SET quantity = ? WHERE drug_id = ? AND institution_id = ?";
        $updateInventoryStmt = $conn->prepare($updateInventorySql);
        $updateInventoryStmt->bind_param("iii", $newQuantity, $drugId, $institutionId);
        $updateInventoryStmt->execute();
        $updateInventoryStmt->close();
    } else {
        // Insert new record
        $insertInventorySql = "INSERT INTO institution_inventory (drug_id, institution_id, quantity) VALUES (?, ?, ?)";
        $insertInventoryStmt = $conn->prepare($insertInventorySql);
        $insertInventoryStmt->bind_param("iii", $drugId, $institutionId, $quantity);
        $insertInventoryStmt->execute();
        $insertInventoryStmt->close();
    }
    $checkStmt->close();
}


// Fetch Pending Requests for Display
$requests_sql = "SELECT r.id, r.quantity, r.request_date, r.status, r.supplier_notes, d.name AS drug_name, i.name AS institution_name
                  FROM requests r
                  JOIN drugs d ON r.drug_id = d.id
                  JOIN institutions i ON r.institution_id = i.id
                  WHERE r.status = 'Pending'
                  ORDER BY r.request_date ASC"; //Order by request date, oldest first
$requests_result = executeQuery($conn, $requests_sql);
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
    <title>Process Requests</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <!-- <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 95%; padding: 20px; margin: 0 auto; } /* Wider wrapper */
        .dashboard-nav { margin-bottom: 20px; }
        .dashboard-nav a { margin-right: 10px; }
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .action-buttons button { margin-right: 5px; }
        textarea { width: 100%; box-sizing: border-box; }
    </style> -->
</head>
<body>
    <div class="wrapper">
        <h2>Process Drug Requests</h2>
        <p>Manage and process pending drug requests.</p>

        <nav>
        <ul>
                <li><a href="admin.php">Dashboard</a></li>
                <li><a href="institutions.php">Institutions</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="drugs.php">Drugs</a></li>
                <li><a href="requests.php"   style="text-decoration: underline;text-underline-offset:0.2em;">Manage Requests</a></li>  
                <li><a href="logout.php">Logout</a></li>
            </ul>
</nav>

        <?php echo $update_message; ?> <?php //Display update message ?>

        <?php if (!empty($requests_err)): ?>
            <div class="alert alert-danger"><?php echo $requests_err; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Institution</th>
                        <th>Drug Name</th>
                        <th>Quantity Requested</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="7">No pending requests.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['id']); ?></td>
                                <td><?php echo htmlspecialchars($request['institution_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['drug_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                                <td><?php echo htmlspecialchars($request['status']); ?></td>
                                <td class="action-buttons">
                                    <form method="post" style="display:inline-block;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="Approved">
                                        <button type="submit" name="update_request" class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#rejectModal<?php echo $request['id']; ?>">Reject</button>

                                    <!-- Rejection Modal -->
                                    <div class="modal fade" id="rejectModal<?php echo $request['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="rejectModalLabel<?php echo $request['id']; ?>">Reject Request <?php echo $request['id']; ?></h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">×</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="post" action="">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                        <input type="hidden" name="action" value="Rejected">
                                                        <div class="form-group">
                                                            <label for="supplier_notes">Reason for Rejection (Optional):</label>
                                                            <textarea class="form-control" id="supplier_notes" name="supplier_notes" rows="3"></textarea>
                                                        </div>
                                                        <button type="submit" name="update_request" class="btn btn-danger">Reject Request</button>
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <p><a href="../logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a></p>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>