<?php
session_start();

// Prevent caching of this page (to fix back button issue)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

require 'database.php';
// Check if already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php"); // Redirect to the home page or dashboard
    exit;
}

// Process login when form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Basic input validation (you should add more robust validation)
    if (empty($username) || empty($password)) {
        $login_err = "Please enter both username and password.";
    } else {
        // Connect to the database
        $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Prepare and execute the query (using a prepared statement)
        $sql = "SELECT id, username, password, role, supplier_id, institution_id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();

            if (password_verify($password, $row['password'])) {  // Verify hashed password
                session_start();

                // Store data in session variables
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $row["id"];
                $_SESSION["username"] = $row["username"];
                $_SESSION["role"] = $row["role"];
                $_SESSION['supplier_id'] = $row['supplier_id'];
                $_SESSION['institution_id'] = $row['institution_id'];

                // Redirect to appropriate page based on role
                if ($row["role"] == "Admin") {
                    header("location: admin/admin.php");
                } else if($row['role'] === 'Super Admin') {
                    header("location: super_admin_dashboard.php");
                } else if ($row['role'] === "Institution Staff") {
                    header("location: institution/dashboard.php");
                } else {
                    header("location: index.php");
                }
                exit;
            } else {
                $login_err = "Invalid username or password.";
            }
        } else {
            $login_err = "Invalid username or password.";
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login_style.css">
</head>
<body>
    <div class="wrapper">
        <div class="login-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <h1>Login</h1>

                <?php
                if(!empty($login_err)){
                    echo '<div class="alert">' . $login_err . '</div>';
                }
                ?>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>

                <button type="submit" class="button-40">Login</button>

                <p class="signup-link">
                    Don't have an account? <a href="signup.php">Sign up now</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>