<?php
session_start();  // Start the session at the very beginning
require 'database.php';

// Check if the user is already logged in, if yes then redirect him to the home page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

// Define variables and initialize with empty values
$username = $password = $confirm_password = $role = $institution_id = "";
$username_err = $password_err = $confirm_password_err = $role_err = $institution_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))){
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        $result = executeQuery($conn, $sql, 's', trim($_POST["username"]));

        if ($result && $result->num_rows == 1) {
            $username_err = "This username is already taken.";
        } else {
            $username = trim($_POST["username"]);
        }
    }

    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }

    // Fixed role (Institution Staff) - No longer in the form
    $role = "Institution Staff";

    if (empty($_POST['institution'])) {
        $institution_err = "Please select an institution";
    } else {
        $institution_id = $_POST['institution'];
    }

    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($institution_err)){

        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, role, institution_id) VALUES (?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("sssi", $username, $hashed_password, $role, $institution_id);


            if ($stmt->execute()) {
                header("location: login.php");
                exit(); //Important to exit.
            } else {
                echo "Error: " . $stmt->error; //Provide error from prepared statement
            }

            $stmt->close();
        }else{
            echo "Error in preparing statement: " . $conn->error;
        }

    }

}

//Close connection
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; }
        .wrapper{ width: 360px; padding: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Sign Up</h2>
        <p>Please fill this form to create an account.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
            </div>
            <div>
                <label for="role">Role:</label>
                <select name="role" id="role" class="form-control <?php echo (!empty($role_err)) ? 'is-invalid' : ''; ?>">
                  <option value="">--Select Role--</option>
                  <!-- <option value="Admin">Admin</option> -->
                  <option value="Institution Staff">Institution Staff</option>
                </select>
                <span class="invalid-feedback"><?php echo $role_err ?></span>
            </div>
            <div id="institutionDiv" style="display: none">
                <label for="institution">Institution:</label>
                <select name="institution" id="institution" class="form-control <?php echo (!empty($institution_err)) ? 'is-invalid' : ''; ?>">
                  <option value="">Select an institution</option>
                  <?php
                  $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
                  $institutionQuery = "SELECT id, name from institutions";
                  $institutionResult = $conn->query($institutionQuery);
                  while($row = $institutionResult->fetch_assoc()){
                    echo "<option value='". $row['id'] ."'>" . $row['name'] . "</option>";
                  }
                  ?>
                  <span class="invalid-feedback"><?php echo $institution_err ?></span>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-secondary ml-2" value="Reset">
            </div>
            <p>Already have an account? <a href="login.php">Login here</a>.</p>
        </form>
    </div>    
    <script>
      const roleSelect = document.getElementById('role')
      const institutionDiv = document.getElementById('institutionDiv')
      roleSelect.addEventListener('change', () => {
        if(roleSelect.value === 'Institution Staff'){
          institutionDiv.style.display = "block"
        }else{
          institutionDiv.style.display = "none"
        }
      })
    </script>
</body>
</html>