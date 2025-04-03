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
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Add viewport meta tag for responsiveness -->
    <link rel="stylesheet" href="style.css"> <!-- Link to your CSS file (style.css) -->
    <!-- Bootstrap CSS link and inline styles removed as style.css now handles styling -->
</head>
<body>
    <div class="wrapper">
        <h2>Institution Inventory</h2>
        <nav class="no-print">
            <button class="hamburger-menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="inventory.php" class="active" style="text-decoration: underline;text-underline-offset:0.2em;">View Inventory</a></li> <!-- Added 'active' class to View Inventory link -->
            <li><a href="request_drug.php">Request Drugs</a></li>
            <li><a href="requests.php">View Requests</a></li>
            <li><a href="bill.php">Generate Bill</a></li>
</ul>
</nav>
        <p>Viewing Inventory for: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></p>



        <?php if (!empty($inventory_err)): ?>
            <div class="alert alert-danger"><?php echo $inventory_err; ?></div> <!-- Kept alert and alert-danger classes if you intend to style them -->
        <?php endif; ?>

        <div class="table-responsive"> <!-- Added table-responsive class for table responsiveness -->
            <table class="table">  <!-- Added table class for styling -->
                <thead>
                    <tr>
                        <th>Drug Name</th>
                        <th>Manufacturer</th>
                        <th>Quantity on Hand</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory)): ?>
                        <tr><td colspan="3">Inventory is empty.</td></tr> <!-- Adjusted colspan to 3 -->
                    <?php else: ?>
                        <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['drug_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['manufacturer_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p class="no-print"><a href="../admin/logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a></p>  <!-- Kept btn and btn-danger classes -->
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