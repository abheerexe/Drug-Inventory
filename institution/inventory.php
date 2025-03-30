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

// Fetch institution inventory - MODIFIED QUERY: Removed strength and dosage_form
$inventory_sql = "SELECT ii.quantity, d.name AS drug_name, d.manufacturer_name
                  FROM institution_inventory ii
                  JOIN drugs d ON ii.drug_id = d.id
                  WHERE ii.institution_id = ?";

$inventory_result = executeQuery($conn, $inventory_sql, 'i', $institution_id);

if ($inventory_result) {
    $inventory = $inventory_result->fetch_all(MYSQLI_ASSOC);
} else {
    $inventory_err = "Error fetching inventory: " . $conn->error;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Institution Inventory</title>
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
        <h2>Institution Inventory</h2>
        <p>Viewing Inventory for: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></p>

        <nav>
            <ul>
            <a href="dashboard.php">Dashboard</a>
            <a href="inventory.php"   style="text-decoration: underline;text-underline-offset:0.2em;">View Inventory</a>
            <a href="request_drug.php">Request Drugs</a>
            <a href="requests.php">View Requests</a>
            <a href="bill.php">Generate Bill</a>
</ul>
</nav>

        <?php if (!empty($inventory_err)): ?>
            <div class="alert alert-danger"><?php echo $inventory_err; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Drug Name</th>
                        <!-- Removed Strength Header -->
                        <!-- Removed Dosage Form Header -->
                        <th>Manufacturer</th>
                        <th>Quantity on Hand</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory)): ?>
                        <tr><td colspan="4">Inventory is empty.</td></tr> <!-- Adjusted colspan to 4 -->
                    <?php else: ?>
                        <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['drug_name']); ?></td>
                                <!-- Removed Strength Data -->
                                <!-- Removed Dosage Form Data -->
                                <td><?php echo htmlspecialchars($item['manufacturer_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
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