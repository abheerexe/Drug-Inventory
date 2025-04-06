<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once '../database.php';

// Check if the user is logged in and is Supplier
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'Supplier') {
    header("location: ../login.php");
    exit;
}

$requests_err = "";
$requests = [];
$update_message = ""; // Success/error message for updates

$currentPage = basename($_SERVER['PHP_SELF']);

// --- Handle Approve/Reject/Delivered Actions ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_request'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; // 'Approved', 'Rejected', or 'Delivered'
    $supplier_notes = trim($_POST['supplier_notes']);

    // Validate action
    if (!in_array($action, ['Approved', 'Rejected', 'Delivered'])) {
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

// (updateInventory function - same as in admin/requests.php - copy it here)
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


// Fetch Requests for Display - For Supplier, show only 'Approved' and 'Delivered'
$requests_sql = "SELECT r.id, r.quantity, r.request_date, r.status, r.supplier_notes, d.name AS drug_name, i.name AS institution_name
                  FROM requests r
                  JOIN drugs d ON r.drug_id = d.id
                  JOIN institutions i ON r.institution_id = i.id
                  WHERE r.status IN ('Approved', 'Delivered')  -- Show only Approved and Delivered requests to Supplier
                  ORDER BY r.request_date DESC"; // Show latest requests first
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
    <title>Supplier Request Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="supplier.css">
    <style>
        /* Add specific styling for supplier requests page if needed */
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Manage Drug Requests</h2>
        <p>Approve or Reject Drug Requests from Institutions and mark them as Delivered.</p>

        <nav>
      <ul>
      <li><a href="dashboard.php" class="<?php if ($currentPage == 'dashboard.php') echo 'active'; ?>">Dashboard</a></li>
                <li><a href="requests.php" class="<?php if ($currentPage == 'requests.php') echo 'active'; ?>" style="text-decoration: underline;text-underline-offset:0.2em;">Manage Requests</a></li>
                <li><a href="../admin/logout.php">Logout</a></li>
      </ul>
  </nav>
        <hr/>

        <?php echo $update_message; ?> <?php //Display update message ?>

        <?php if (!empty($requests_err)): ?>
            <div class="alert alert-danger"><?php echo $requests_err; ?></div>
        <?php endif; ?>

        <div class="table-responsive dashboard-content">
            <h3>Approved Drug Requests</h3>
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
                        <tr><td colspan="7">No approved requests.</td></tr>
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
                                    <?php if ($request['status'] === 'Approved'): ?>
                                        <form method="post" style="display:inline-block;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <input type="hidden" name="action" value="Delivered">
                                            <button type="submit" name="update_request" class="button-40 btn btn-primary btn-sm">Mark as Delivered</button>
                                        </form>
                                    <?php elseif ($request['status'] === 'Delivered'): ?>
                                        <span class="text-success">Delivered</span> <?php // Display text if already delivered ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <p><a href="../admin/logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a></p>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerMenu = document.querySelector('.hamburger-menu'); // Ensure hamburger menu is still not needed, if yes then uncomment this and related script
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