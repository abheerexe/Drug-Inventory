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

$user_success_message = "";
$user_username = $user_password = $user_role = $user_institution_id = "";
$user_username_err = $user_password_err = $user_role_err = $user_institution_err = "";
$user_edit_err = ""; // Error message for user edit/delete actions

// --- User Form Processing (Add) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    // Validate username
    if (empty(trim($_POST["user_username"]))) {
        $user_username_err = "Please enter a username.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["user_username"]))) {
        $user_username_err = "Username can only contain letters, numbers, and underscores.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);

            // Set parameters
            $param_username = trim($_POST["user_username"]);

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // store result
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $user_username_err = "This username is already taken.";
                } else {
                    $user_username = trim($_POST["user_username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }

    // Validate password
    if (empty(trim($_POST["user_password"]))) {
        $user_password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["user_password"])) < 6) {
        $user_password_err = "Password must have atleast 6 characters.";
    } else {
        $user_password = trim($_POST["user_password"]);
    }

    // Validate role
    if (empty($_POST["user_role"])) {
        $user_role_err = "Please select a role.";
    } else {
        $user_role = $_POST["user_role"];
    }
    // Validate institution - Required only for 'Institution Staff' role
    if ($_POST["user_role"] === 'Institution Staff' && empty($_POST["user_institution_id"])) {
        $user_institution_err = "Please select an institution for Institution Staff.";
    } else {
        $user_institution_id = $_POST["user_institution_id"];
    }

    // Check input errors before inserting in database
    if (empty($user_username_err) && empty($user_password_err) && empty($user_role_err) && empty($user_institution_err)) {

        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, role, institution_id) VALUES (?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("sssi", $param_username, $param_password, $param_role, $param_institution_id);

            // Set parameters
            $param_username = $user_username;
            $param_password = password_hash($user_password, PASSWORD_DEFAULT); // Creates a password hash
            $param_role = $user_role;
            $param_institution_id = $user_institution_id;


            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                $user_success_message = "User added successfully.";
                //Clear input fields
                $user_username = $user_password = $user_role = $user_institution_id = "";
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error in preparing statement: " . $conn->error;
        }
    }
}

// --- User Delete Processing ---
if (isset($_GET['delete_user'])) {
    $delete_user_id = $_GET['delete_user'];
    $delete_sql = "DELETE FROM users WHERE id = ?";
    if ($stmt = $conn->prepare($delete_sql)) {
        $stmt->bind_param("i", $delete_user_id);
        if ($stmt->execute()) {
            $user_success_message = "User deleted successfully.";
        } else {
            $user_edit_err = "Error deleting user: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $user_edit_err = "Error preparing delete statement: " . $conn->error;
    }
}


// Fetch Users for Display
$users_sql = "SELECT id, username, role, institution_id FROM users";
$users_result = executeQuery($conn, $users_sql);
$users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch Institutions for User Dropdown
$institutions_sql = "SELECT id, name FROM institutions";
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
                <li><a href="institutions.php">Institutions</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="drugs.php">Drugs</a></li>
                <li><a href="admin.php">Dashboard</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
        <hr/>

        <?php if (!empty($user_success_message)) : ?>
            <div class="alert alert-success"><?php echo $user_success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($user_edit_err)) : ?>
            <div class="alert alert-danger"><?php echo $user_edit_err; ?></div>
        <?php endif; ?>

        <h3>Manage Users</h3>
        <!-- User Add Form -->
        <form name="add_user" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="user_username" class="form-control <?php echo (!empty($user_username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $user_username; ?>" required>
                <span class="invalid-feedback"><?php echo $user_username_err; ?></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="user_password" class="form-control <?php echo (!empty($user_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $user_password; ?>" required>
                <span class="invalid-feedback"><?php echo $user_password_err; ?></span>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="user_role" id="user_role" class="form-control <?php echo (!empty($user_role_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">Select Role</option>
                    <option value="Institution Staff" <?php if ($user_role === 'Institution Staff') echo 'selected'; ?>>Institution Staff</option>
                </select>
                <span class="invalid-feedback"><?php echo $user_role_err; ?></span>
            </div>
            <div class="form-group">
                <label>Institution (For Institution Staff)</label>
                <select name="user_institution_id" id="user_institution_id" class="form-control <?php echo (!empty($user_institution_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">Select Institution</option>
                    <?php foreach ($institutions as $institution) : ?>
                        <option value="<?php echo $institution['id']; ?>" <?php if ($user_institution_id == $institution['id']) echo 'selected'; ?>><?php echo htmlspecialchars($institution['name']); ?></option>
                    <?php endforeach; ?>
                    <span class="invalid-feedback"><?php echo $user_institution_err; ?></span>
                </select>
            </div>

            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Add User" name="add_user">
                <input type="reset" class="btn btn-secondary ml-2" value="Reset">
            </div>
        </form>

        <div class="table-responsive">
            <h3>Current Users</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Institution ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)) : ?>
                        <tr>
                            <td colspan="5">No users found.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($users as $user) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td><?php echo htmlspecialchars($user['institution_id'] ?? 'N/A'); ?></td>
                                <td class="action-buttons">
                                    <a href="?delete_user=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                </td>
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