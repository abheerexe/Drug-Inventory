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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Drugs</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <!-- <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 800px; padding: 20px; margin: 0 auto; }
        .dashboard-nav { margin-bottom: 20px; }
        .dashboard-nav a { margin-right: 10px; }
        #drugSearchInput { width: 100%; padding: 10px; margin-bottom: 10px; box-sizing: border-box; }
        #drugList { border: 1px solid #ddd; max-height: 200px; overflow-y: auto; margin-bottom: 10px; position: absolute; background-color: white; z-index: 1000; width: 95%; display: none; } /* Initially hidden */
        #drugList div { padding: 8px; cursor: pointer; }
        #drugList div:hover { background-color: #f0f0f0; }
        .selected-drug { background-color: #e0e0e0; } /* Style for selected drug */
    </style> -->
</head>
<body>
    <div class="wrapper">
        <h2>Request Drugs</h2>
        <p>Request Drugs for: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></p>

        <nav>
            <ul>
            <a href="dashboard.php">Dashboard</a>
            <a href="inventory.php" >View Inventory</a>
            <a href="request_drug.php" style="text-decoration: underline;text-underline-offset:0.2em;">Request Drugs</a>
            <a href="requests.php">View Requests</a>
            <a href="bill.php">Generate Bill</a>
</ul>
</nav>

        <?php if (!empty($request_success_message)): ?>
            <div class="alert alert-success"><?php echo $request_success_message; ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Search Drug</label>
                <input type="text" id="drugSearchInput" placeholder="Type to search drugs..." class="form-control <?php echo (!empty($request_drug_err)) ? 'is-invalid' : ''; ?>" oninput="filterDrugs()">
                <div id="drugList">
                    <!-- Drug suggestions will be loaded here via AJAX -->
                </div>
                <input type="hidden" name="drug_id" id="selectedDrugId" value="<?php echo $drug_id; ?>">
                <span class="invalid-feedback"><?php echo $request_drug_err; ?></span>
            </div>

            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" id="quantity" class="form-control <?php echo (!empty($request_quantity_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $quantity; ?>" min="1" required>
                <span class="invalid-feedback"><?php echo $request_quantity_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit Request">
                <input type="reset" class="btn btn-primary ml-2" value="Reset">
            </div>
        </form>


        <p><a href="../logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a></p>
    </div>

    <script>
        const drugSearchInput = document.getElementById('drugSearchInput');
        const drugListDiv = document.getElementById('drugList');
        const selectedDrugIdInput = document.getElementById('selectedDrugId');
        let selectedDrugElement = null;
        let lastSearchTerm = '';
        let searchTimeout;

        drugSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();

            // Clear previous timeout
            clearTimeout(searchTimeout);

            // Only make a request if the search term has changed and is not empty
            if (searchTerm !== lastSearchTerm && searchTerm.length > 0) {
                // Set a **reduced** delay for more "letter by letter" feel (e.g., 100ms)
                // For truly instant, you could set delay to 0 or remove setTimeout entirely (see notes below!)
                searchTimeout = setTimeout(function() {
                    fetchDrugs(searchTerm);
                    lastSearchTerm = searchTerm;
                }, 100); // Reduced debounce delay to 100ms
            } else if (searchTerm.length === 0) {
                drugListDiv.style.display = 'none'; // Hide the list when the input is empty
                drugListDiv.innerHTML = '';
                lastSearchTerm = '';
            }
        });

        function fetchDrugs(searchTerm) {
            fetch('fetch_drugs.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'searchTerm=' + encodeURIComponent(searchTerm)
            })
            .then(response => response.json())
            .then(data => {
                drugListDiv.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(drug => {
                        const div = document.createElement('div');
                        div.dataset.drugId = drug.id;
                        div.textContent = drug.name;
                        div.addEventListener('click', function() {
                            selectDrug(this);
                        });
                        drugListDiv.appendChild(div);
                    });
                    drugListDiv.style.display = 'block';
                } else {
                    drugListDiv.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching drugs:', error);
                drugListDiv.style.display = 'none';
                drugListDiv.innerHTML = '<div class="text-danger">Error loading drugs.</div>';
            });
        }

        function selectDrug(drugElement) {
            if (selectedDrugElement) {
                selectedDrugElement.classList.remove('selected-drug');
            }
            drugElement.classList.add('selected-drug');
            selectedDrugElement = drugElement;

            const drugId = drugElement.dataset.drugId;
            selectedDrugIdInput.value = drugId;
            drugSearchInput.value = drugElement.textContent;
            drugListDiv.style.display = 'none'; // Collapse the list after selection
        }

        // Hide the drug list initially
        drugListDiv.style.display = 'none';
    </script>
</body>
</html>
<?php
$conn->close();
?>