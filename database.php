<?php

// Database credentials
$dbHost = 'localhost';
$dbUser = 'root'; // Your MySQL username
$dbPass = ''; // Your MySQL password
$dbName = 'drug'; // Your database name

// Create a database connection
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);


// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Function to execute queries securely (using prepared statements)
function executeQuery($conn, $sql, $types = null, ...$params) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();  // Use get_result() if you expect a result set (SELECT queries)
        $stmt->close();
        return $result; // Return the mysqli result object
    } else {
        die("Execute failed: " . $stmt->error);  // Handle errors appropriately
    }
}


// Example usage of executeQuery()
// $username = "test";
// $result = executeQuery($conn, "SELECT id FROM users WHERE username = ?", "s", $username);


// Close the database connection when done
// $conn->close(); // Close this if you are requiring in other files.
?>