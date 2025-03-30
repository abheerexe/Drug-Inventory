<?php

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

session_start();
require 'database.php';
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['q'])) {
    $searchTerm = $_GET['q'];


    // --- Security: Sanitize User Input (VERY IMPORTANT!) ---
    $searchTerm = $conn->real_escape_string($searchTerm); // Or use parameterized queries


    // --- Construct and Execute the SQL Query (Using Prepared Statements is even better) ---
    $sql = "SELECT * FROM drugs WHERE name LIKE ? LIMIT 10"; // Add LIMIT clause

    $stmt = $conn->prepare($sql);
    $searchPattern = "%" . $searchTerm . "%";
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();




    // --- Display the Search Results ---
    if ($result->num_rows > 0) {
        echo "<table>"; // Or any other HTML structure you want to use for display
        echo "<thead><tr><th>Name</th><th>Price</th><th>Manufacturer</th><th>Composition</th></tr></thead>"; // Add more columns if needed
        echo "<tbody>";


        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>"; // Always escape output!
            echo "<td>" . htmlspecialchars($row['price']) . "</td>";
            echo "<td>" . htmlspecialchars($row['manufacturer_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['short_composition1']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";

    } else {
        echo "<p>No results found.</p>";
    }
    $stmt->close();


}


$conn->close();

?>