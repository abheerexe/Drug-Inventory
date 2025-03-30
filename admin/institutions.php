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

$institution_success_message = "";
$institution_name = $institution_address = $institution_contact_person = $institution_phone = $institution_email = $institution_type = "";
$institution_name_err = $institution_address_err = $institution_contact_person_err = $institution_phone_err = $institution_email_err = $institution_type_err = "";

// --- Institution Form Processing (Add) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_institution'])) {

    $institution_name = trim($_POST["name"]);
    $institution_address = trim($_POST["address"]);
    $institution_contact_person = trim($_POST["contact_person"]);
    $institution_phone = trim($_POST["phone"]);
    $institution_email = trim($_POST["email"]);
    $institution_type = $_POST["type"];

    // Validate name
    if (empty($institution_name)) {
        $institution_name_err = "Please enter a name.";
    }

    // Validate email (if provided)
    if (!empty($institution_email) && !filter_var($institution_email, FILTER_VALIDATE_EMAIL)) {
        $institution_email_err = "Invalid email format.";
    }

    // Validate type
    if (empty($institution_type)) {
        $institution_type_err = "Please select a type.";
    }

    // Check input errors before inserting in database
    if (empty($institution_name_err) && empty($institution_address_err) && empty($institution_contact_person_err) && empty($institution_phone_err) && empty($institution_email_err) && empty($institution_type_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO institutions (name, address, contact_person, phone, email, type) VALUES (?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssss", $param_name, $param_address, $param_contact_person, $param_phone, $param_email, $param_type);

            // Set parameters
            $param_name = $institution_name;
            $param_address = $institution_address;
            $param_contact_person = $institution_contact_person;
            $param_phone = $institution_phone;
            $param_email = $institution_email;
            $param_type = $institution_type;


            if ($stmt->execute()) {
                $institution_success_message = "Institution added successfully.";
                //Clear input fields after successful submission
                $institution_name = $institution_address = $institution_contact_person = $institution_phone = $institution_email = $institution_type = "";

            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error in preparing statement: " . $conn->error;
        }
    }
}


// Fetch Institutions for Display
$institutions_sql = "SELECT id, name, address, contact_person, phone, email, type FROM institutions";
$institutions_result = executeQuery($conn, $institutions_sql);
$institutions = $institutions_result ? $institutions_result->fetch_all(MYSQLI_ASSOC) : [];


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
                <li><a href="institutions.php" style="text-decoration: underline;text-underline-offset:0.2em;">Institutions</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="drugs.php">Drugs</a></li>
                <li><a href="requests.php">Manage Requests</a></li>  
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        <hr/>

        <?php if (!empty($institution_success_message)) : ?>
            <div class="alert alert-success"><?php echo $institution_success_message; ?></div>
        <?php endif; ?>

        <h3>Add Institution</h3>
        <!-- Institution Form -->
        <form name="add_institution" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control <?php echo (!empty($institution_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $institution_name; ?>" required>
                <span class="invalid-feedback"><?php echo $institution_name_err; ?></span>
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" class="form-control <?php echo (!empty($institution_address_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $institution_address; ?>">
                <span class="invalid-feedback"><?php echo $institution_address_err; ?></span>
            </div>
            <div class="form-group">
                <label>Contact Person</label>
                <input type="text" name="contact_person" class="form-control <?php echo (!empty($institution_contact_person_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $institution_contact_person; ?>">
                <span class="invalid-feedback"><?php echo $institution_contact_person_err; ?></span>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" class="form-control <?php echo (!empty($institution_phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $institution_phone; ?>">
                <span class="invalid-feedback"><?php echo $institution_phone_err; ?></span>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control <?php echo (!empty($institution_email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $institution_email; ?>">
                <span class="invalid-feedback"><?php echo $institution_email_err; ?></span>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type" id="type" class="form-control <?php echo (!empty($institution_type_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">Select type</option>
                    <option value="Institution" <?php if ($institution_type === 'Institution') echo 'selected'; ?>>Institution</option>
                    <option value="Vendor" <?php if ($institution_type === 'Vendor') echo 'selected'; ?>>Vendor</option>
                </select>
                <span class="invalid-feedback"><?php echo $institution_type_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn button-40" value="Add Institution" name="add_institution">
                <input type="reset" class="btn button-40 ml-2" value="Reset">
            </div>
        </form>

        <hr>

        <div class="table-responsive">
            <h3>Current Institutions</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Address</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($institutions)) : ?>
                        <tr>
                            <td colspan="7">No institutions found.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($institutions as $institution) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($institution['id']); ?></td>
                                <td><?php echo htmlspecialchars($institution['name']); ?></td>
                                <td><?php echo htmlspecialchars($institution['type']); ?></td>
                                <td><?php echo htmlspecialchars($institution['address'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($institution['contact_person'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($institution['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($institution['email'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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