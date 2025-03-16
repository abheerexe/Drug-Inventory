<?php
session_start();  // Start the session at the very beginning
require 'database.php';

// Check if the user is already logged in, if yes then redirect him to the home page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

// Define variables and initialize with empty values
$username = $password = $confirm_password = $role = $institution_id = $email = "";
$username_err = $password_err = $confirm_password_err = $role_err = $institution_err = $email_err = "";

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

    // Validate Email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email address.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Invalid email format.";
    } else{
        $email = trim($_POST["email"]);
    }

    // Fixed role (Institution Staff) - No longer in the form
    $role = "Institution Staff";

    if (empty($_POST['institution'])) {
        $institution_err = "Please select an institution";
    } else {
        $institution_id = $_POST['institution'];
    }

    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($institution_err) && empty($email_err)){

        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, role, institution_id, email) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("ssssi", $username, $hashed_password, $role, $institution_id, $email); //Added email to bind param


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

// Fetch institutions for dropdown
$institutions = [];
$institutionQuery = "SELECT id, name from institutions";
$institutionResult = $conn->query($institutionQuery);
if ($institutionResult) {
    while($row = $institutionResult->fetch_assoc()){
        $institutions[] = $row;
    }
    $institutionResult->free_result();
}

//Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link rel="stylesheet" href="signup_style.css">
    <style>
        .error-message {
            color: red;
            font-size: 0.8em;
            display: block; /* To make it appear below the input */
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="signup-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="signupForm">
                <h1>Sign Up</h1>

                <div id="alertBox" class="alert" style="display: none;"></div>
                <?php
                if(!empty($username_err) || !empty($password_err) || !empty($confirm_password_err) || !empty($institution_err) || !empty($email_err)){
                    echo '<div class="alert">';
                    echo !empty($username_err) ? $username_err . '<br>' : '';
                    echo !empty($password_err) ? $password_err . '<br>' : '';
                    echo !empty($confirm_password_err) ? $confirm_password_err . '<br>' : '';
                    echo !empty($institution_err) ? $institution_err . '<br>' : '';
                    echo !empty($email_err) ? $email_err : '';
                    echo '</div>';
                }
                ?>

                <p class="form-description">Please fill this form to create an account.</p>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" value="<?php echo $username; ?>" required>
                    <span id="username-error" class="error-message"></span>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="<?php echo $email; ?>" required oninput="validateEmailLive()">
                    <span id="email-error" class="error-message"></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" value="<?php echo $password; ?>" required oninput="validatePasswordLive()">
                    <span id="password-error" class="error-message"></span>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" value="<?php echo $confirm_password; ?>" required>
                    <span id="confirm_password-error" class="error-message"></span>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select name="role" id="role" required>
                        <option value="">--Select Role--</option>
                        <option value="Institution Staff" <?php echo ($role === "Institution Staff") ? "selected" : ""; ?>>Institution Staff</option>
                    </select>
                </div>

                <div class="form-group" id="institutionDiv" style="display: none;">
    <label for="institution">Institution</label>
    <select name="institution" id="institution">
        <option value="">Select an institution</option>
        <?php
        $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName); // Re-establish connection - important!
        $institutionQuery = "SELECT id, name from institutions WHERE name != 'admin'"; // Modified query to exclude 'admin'
        $institutionResult = $conn->query($institutionQuery);
        if ($institutionResult) { // Added check if $institutionResult is valid
            while($row = $institutionResult->fetch_assoc()){
                $selected = ($institution_id == $row['id']) ? 'selected' : '';
                echo "<option value='" . $row['id'] . "' " . $selected . ">" . htmlspecialchars($row['name']) . "</option>";
            }
            $institutionResult->free_result(); // Free result set
        } else {
            // Handle query error if needed, e.g., echo "Error fetching institutions";
        }
        $conn->close(); // Close the connection here as well, to be safe
        ?>
    </select>
    <span id="institution-error" class="error-message"></span>
</div>

                <div class="button-group">
                    <button type="submit" class="button-40">Sign Up</button>
                    <button type="reset" class="button-40">Reset</button>
                </div>

                <p class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('signupForm').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const role = document.getElementById('role').value;
        const institution = document.getElementById('institution').value;
        const email = document.getElementById('email').value; // Get email input value
        const alertBox = document.getElementById('alertBox');
        const emailErrorSpan = document.getElementById('email-error'); // Get email error span
        const passwordErrorSpan = document.getElementById('password-error'); // Get password error span
        const confirmPasswordErrorSpan = document.getElementById('confirm_password-error');

        // Client-side validation
        let message = "";
        let hasError = false;

        if (!username || !password || !confirmPassword || !role || !email) { // Added email to required check
            message = "Please fill in all required fields.";
            hasError = true;
        } else if (password !== confirmPassword) {
            message = "Passwords do not match.";
            hasError = true;
        } else if (password.length < 6) {
            message = "Password must have at least 6 characters and ";
            hasError = true;
        } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-])/.test(password)) {
            message = "Password must contain at least 6 characters with 1 uppercase, 1 lowercase, and 1 special character.";
            hasError = true;
        }
         else if (role === 'Institution Staff' && !institution) {
            message = "Please select an institution.";
            hasError = true;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { // Email validation regex
            message = "Invalid email format.";
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
            alertBox.textContent = message;
            alertBox.style.display = "block";
            return;
        }
    });

    function validateEmailLive() {
        const emailInput = document.getElementById('email');
        const emailErrorSpan = document.getElementById('email-error');
        const emailValue = emailInput.value.trim();

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
            emailErrorSpan.textContent = "Invalid email format.";
        } else {
            emailErrorSpan.textContent = ""; // Clear error message if valid
        }
    }

    function validatePasswordLive() {
        const passwordInput = document.getElementById('password');
        const passwordErrorSpan = document.getElementById('password-error');
        const passwordValue = passwordInput.value;
        let passwordMessage = "";
        let isValid = true;

        if (passwordValue.length < 6) {
            passwordMessage += "Password must be at least 6 characters. ";
            isValid = false;
        }
        if (!/(?=.*[a-z])/.test(passwordValue)) {
            passwordMessage += "Must contain at least 1 lowercase letter. ";
            isValid = false;
        }
        if (!/(?=.*[A-Z])/.test(passwordValue)) {
            passwordMessage += "Must contain at least 1 uppercase letter. ";
            isValid = false;
        }
        if (!/(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-])/.test(passwordValue)) {
            passwordMessage += "Must contain at least 1 special character.";
            isValid = false;
        }

        if (!isValid) {
            passwordErrorSpan.textContent = passwordMessage.trim();
        } else {
            passwordErrorSpan.textContent = ""; // Clear error message if valid
        }
    }


    // Show/hide institution select based on role
    document.getElementById('role').addEventListener('change', function() {
        const institutionDiv = document.getElementById('institutionDiv');
        institutionDiv.style.display = this.value === 'Institution Staff' ? 'block' : 'none';
    });

    // Trigger the change event on page load if role is pre-selected
    window.addEventListener('load', function() {
        const roleSelect = document.getElementById('role');
        if (roleSelect.value === 'Institution Staff') {
            document.getElementById('institutionDiv').style.display = 'block';
        }
    });
    </script>
</body>
</html>