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

$drug_success_message = "";
$drug_name = $drug_price = $drug_manufacturer_name = $drug_short_composition1 = "";
$drug_name_err = $drug_price_err = $drug_manufacturer_name_err = $drug_short_composition1_err = "";
$drug_edit_err = ""; // Error message for drug edit/delete actions


// --- Drug Form Processing (Add) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_drug'])) {
    // Validate drug name
    if (empty(trim($_POST["drug_name"]))) {
        $drug_name_err = "Please enter drug name.";
    } else {
        $drug_name = trim($_POST["drug_name"]);
    }

    // Validate drug price
    if (empty(trim($_POST["drug_price"])) || !is_numeric(trim($_POST["drug_price"]))) {
        $drug_price_err = "Please enter a valid price.";
    } else {
        $drug_price = trim($_POST["drug_price"]);
    }

    // Validate manufacturer name (can be empty)
    $drug_manufacturer_name = trim($_POST["drug_manufacturer_name"]);

    // Validate composition (can be empty)
    $drug_short_composition1 = trim($_POST["drug_short_composition1"]);


    // Check input errors before inserting in database
    if (empty($drug_name_err) && empty($drug_price_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO drugs (name, price, manufacturer_name, short_composition1) VALUES (?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sdss", $param_name, $param_price, $param_manufacturer_name, $param_composition);

            // Set parameters
            $param_name = $drug_name;
            $param_price = $drug_price;
            $param_manufacturer_name = $drug_manufacturer_name;
            $param_composition = $drug_short_composition1;


            if ($stmt->execute()) {
                $drug_success_message = "Drug added successfully.";
                //Clear input fields
                $drug_name = $drug_price = $drug_manufacturer_name = $drug_short_composition1 = "";
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error in preparing statement: " . $conn->error;
        }
    }
}


// --- Drug Delete Processing ---
if (isset($_GET['delete_drug'])) {
    $delete_drug_id = $_GET['delete_drug'];
    $delete_sql = "DELETE FROM drugs WHERE id = ?";
    if ($stmt = $conn->prepare($delete_sql)) {
        $stmt->bind_param("i", $delete_drug_id);
        if ($stmt->execute()) {
            $drug_success_message = "Drug deleted successfully.";
        } else {
            $drug_edit_err = "Error deleting drug: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $drug_edit_err = "Error preparing delete statement: " . $conn->error;
    }
}


// --- Pagination for Drugs ---
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Count total drugs for pagination
$total_drugs_sql = "SELECT COUNT(*) AS total FROM drugs";
$stmt = $conn->prepare($total_drugs_sql);
$stmt->execute();
$result = $stmt->get_result();
$total_drugs = $result->fetch_assoc()['total'];
$total_pages = ceil($total_drugs / $records_per_page);

// Fetch Drugs for Display with Pagination
$drugs_sql = "SELECT id, name, price, manufacturer_name, short_composition1 FROM drugs ORDER BY name LIMIT ? OFFSET ?";
$stmt = $conn->prepare($drugs_sql);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$drugs_result = $stmt->get_result();
$drugs = $drugs_result->fetch_all(MYSQLI_ASSOC);


// No connection close here, it will be closed at the end of the file

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
                <li><a href="admin.php">Dashboard</a></li>
                <li><a href="institutions.php">Institutions</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="drugs.php"  style="text-decoration: underline;text-underline-offset:0.2em;">Drugs</a></li>
                <li><a href="requests.php">Manage Requests</a></li>  
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        <hr/>

        <?php if (!empty($drug_success_message)) : ?>
            <div class="alert alert-success"><?php echo $drug_success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($drug_edit_err)) : ?>
            <div class="alert alert-danger"><?php echo $drug_edit_err; ?></div>
        <?php endif; ?>


        <h3>Manage Drugs</h3>
        <!-- Drug Add Form -->
        <form name="add_drug" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Drug Name</label>
                <input type="text" name="drug_name" class="form-control <?php echo (!empty($drug_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $drug_name; ?>" required>
                <span class="invalid-feedback"><?php echo $drug_name_err; ?></span>
            </div>
            <div class="form-group">
                <label>Price</label>
                <input type="number" step="0.01" name="drug_price" class="form-control <?php echo (!empty($drug_price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $drug_price; ?>" required>
                <span class="invalid-feedback"><?php echo $drug_price_err; ?></span>
            </div>
            <div class="form-group">
                <label>Manufacturer Name</label>
                <input type="text" name="drug_manufacturer_name" class="form-control" value="<?php echo $drug_manufacturer_name; ?>">
            </div>
            <div class="form-group">
                <label>Short Composition</label>
                <input type="text" name="drug_short_composition1" class="form-control" value="<?php echo $drug_short_composition1; ?>">
            </div>

            <div class="form-group">
                <input type="submit" class="btn button-40" value="Add Drug" name="add_drug">
                <input type="reset" class="btn button-40 ml-2" value="Reset">
            </div>
        </form>

        <div class="table-responsive">
            <h3>Current Drugs</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Manufacturer</th>
                        <th>Composition</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($drugs)) : ?>
                        <tr>
                            <td colspan="6">No drugs found.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($drugs as $drug) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($drug['id']); ?></td>
                                <td><?php echo htmlspecialchars($drug['name']); ?></td>
                                <td><?php echo htmlspecialchars($drug['price']); ?></td>
                                <td><?php echo htmlspecialchars($drug['manufacturer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($drug['short_composition1'] ?? 'N/A'); ?></td>
                                <td class="action-buttons">
                                    <a href="?delete_drug=<?php echo $drug['id']; ?>" class="btn button-40 btn-sm" onclick="return confirm('Are you sure you want to delete this drug?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Drug Pagination Links -->
        <div class="pagination">
            <?php if ($page > 1) : ?>
                <a href="?page=<?= $page - 1 ?>" class="nav-btn"><</a>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            if ($start_page > 1) {
                echo "<a href='?page=1'>1</a>";
                if ($start_page > 2) {
                    echo "<span class='pagination-ellipsis'>...</span>";
                }
            }

            for ($i = $start_page; $i <= $end_page; $i++) : ?>
                <a href="?page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
            <?php endfor;

            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo "<span class='pagination-ellipsis'>...</span>";
                }
                echo "<a href='?page={$total_pages}'>{$total_pages}</a>";
            }
            ?>

            <?php if ($page < $total_pages) : ?>
                <a href="?page=<?= $page + 1 ?>" class="nav-btn">></a>
            <?php endif; ?>
        </div>


        <hr>


        <a href="../logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a>
    </div>
</body>

</html>
<?php
//Close connection after all operations are complete
$conn->close();
?>