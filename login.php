<?php
session_start();

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

                // Redirect to appropriate page based on role (you'll need to create these pages)
                if ($row["role"] == "Admin") {
                  header("location: supplier_dashboard.php");  // Or wherever your admin dashboard is
                }else if($row['role'] === 'Super Admin'){
                  header("location: super_admin_dashboard.php");
                } else if ($row['role'] === "Institution Staff"){
                  header("location: institution_dashboard.php");
                } else {
                  // Redirect to a generic logged-in page or home page
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
        $conn->close(); // Close connection after usage
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <link rel="stylesheet" href="login_style.css">
  <style>*{
    /* background: #000; */
}

#searchForm button {
    padding: 8px 20px;
    background: rgb(63,94,251);
    background: radial-gradient(circle, rgba(63,94,251,1) 0%, rgba(188,70,252,1) 100%);
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    right: 2px;
}
.search-container {
    position: relative;
    display: block;
    justify-content: left;
    align-items: center;
    width: 50%;
    margin: 20px auto;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    gap: 5px; /* Add 5px gap between flex items */
}</style>
</head>
<body>
  <!-- <h2>Login</h2> -->
  <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
    ?>

  <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="searchForm" class="search-container">
    <label style="display: flex; font-size: xx-large; justify-content: center;">Login</label>
    <br>
    <label for="username">Username:</label>
    <input type="text" name="username" id="username" required><br><br>


    <label for="password">Password:</label>
    <input type="password" name="password" id="password" required><br><br>


    <input type="submit" value="Login">
    <p>Don't have an account? <a href="signup.php">Sign up now</a>.</p>
  </form>
</body>
</html>