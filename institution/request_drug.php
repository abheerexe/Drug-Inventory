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
$request_success_message = "";
$request_drug_err = $request_quantity_err = "";
$drug_id = $quantity = "";


// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate drug selection
    if (empty($_POST["drug_id"])) {
        $request_drug_err = "Please select a drug.";
    } else {
        $drug_id = $_POST["drug_id"];
    }

    // Validate quantity
    if (empty(trim($_POST["quantity"])) || !is_numeric(trim($_POST["quantity"])) || intval(trim($_POST["quantity"])) <= 0) {
        $request_quantity_err = "Please enter a valid quantity (greater than 0).";
    } else {
        $quantity = intval(trim($_POST["quantity"]));
    }

    // Check input errors before inserting in database
    if (empty($request_drug_err) && empty($request_quantity_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO requests (drug_id, institution_id, quantity, status) VALUES (?, ?, ?, 'Pending')";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("iii", $param_drug_id, $param_institution_id, $param_quantity);

            // Set parameters
            $param_drug_id = $drug_id;
            $param_institution_id = $institution_id;
            $param_quantity = $quantity;


            if ($stmt->execute()) {
                $request_success_message = "Drug request submitted successfully.";
                $drug_id = $quantity = ""; //Clear fields on success
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error in preparing statement: " . $conn->error;
        }
    }
}


// Fetch Drugs for Dropdown
$drugs_sql = "SELECT id, name, strength, dosage_form FROM drugs ORDER BY name";
$drugs_result = executeQuery($conn, $drugs_sql);
$drugs = $drugs_result ? $drugs_result->fetch_all(MYSQLI_ASSOC) : [];


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Drugs</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 800px; padding: 20px; margin: 0 auto; }
        .dashboard-nav { margin-bottom: 20px; }
        .dashboard-nav a { margin-right: 10px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Request Drugs</h2>
        <p>Request Drugs for: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></p>

        <div class="dashboard-nav">
            <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
            <a href="inventory.php" class="btn btn-primary">View Inventory</a>
            <a href="request_drug.php" class="btn btn-primary">Request Drugs</a>
            <a href="requests.php" class="btn btn-primary">View Requests</a>
        </div>

        <?php if (!empty($request_success_message)): ?>
            <div class="alert alert-success"><?php echo $request_success_message; ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Select Drug</label>
                <select name="drug_id" id="drug_id" class="form-control <?php echo (!empty($request_drug_err)) ? 'is-invalid' : ''; ?>" required>
                    <option value="">Select Drug</option>
                    <?php if (!empty($drugs)): ?>
                        <?php foreach ($drugs as $drug): ?>
                            <option value="<?php echo $drug['id']; ?>" <?php if($drug_id == $drug['id']) echo 'selected'; ?>><?php echo htmlspecialchars($drug['name'] . ' (' . $drug['strength'] . ' ' . $drug['dosage_form'] . ')'); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <span class="invalid-feedback"><?php echo $request_drug_err; ?></span>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" id="quantity" class="form-control <?php echo (!empty($request_quantity_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $quantity; ?>" min="1" required>
                <span class="invalid-feedback"><?php echo $request_quantity_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit Request">
                <input type="reset" class="btn btn-secondary ml-2" value="Reset">
            </div>
        </form>


        <p><a href="../logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a></p>
    </div>
</body>
</html>
<?php
$conn->close();
?>