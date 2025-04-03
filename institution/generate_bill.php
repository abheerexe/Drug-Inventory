<?php
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.


session_start();
require_once '../database.php'; // Ensure this path is correct!

// Check if the user is logged in and is Institution Staff
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'Institution Staff') {
    header("location: ../login.php");
    exit;
}

if (isset($_GET['date']) && isset($_GET['request_ids'])) {
    $date = $_GET['date'];
    $request_ids_string = $_GET['request_ids'];
    $institution_id = $_SESSION['institution_id'];

    // Security: Validate the date format
    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
        echo "Invalid date format.";
        exit;
    }

    // Sanitize the request IDs (VERY IMPORTANT)
    $request_ids = explode(",", $request_ids_string);
    $request_ids = array_map('intval', $request_ids); // Convert to integers
    $request_ids = array_filter($request_ids, function($id) { return is_int($id) && $id > 0; }); // Ensure positive integers

    if (empty($request_ids)) {
        echo "No valid request IDs provided.";
        exit;
    }

    // Create placeholders for the prepared statement
    $request_ids_placeholders = implode(',', array_fill(0, count($request_ids), '?'));

    // Security: Double-check that ALL request IDs belong to the institution AND are "Approved"
    $check_sql = "SELECT COUNT(*) FROM requests WHERE id IN ($request_ids_placeholders) AND institution_id = ? AND status = 'Approved'";

    // Corrected $types and $params for check_sql
    $types = str_repeat('i', count($request_ids)) . 'i'; // Types for prepared statement - Corrected: Kept extra 'i' for institution_id
    $params = array_merge($request_ids, [$institution_id]); // Corrected: kept institution_id as parameter

    $check_result = executeQuery($conn, $check_sql, $types, ...$params);

    if ($check_result) {
        $row = $check_result->fetch_row();
        $count = $row[0];

        if ($count != count($request_ids)) {
            echo "Error: Not all request IDs belong to this institution or are not 'Approved'!";
            exit;
        }
    } else {
        echo "Database error checking request IDs.";
        exit;
    }

    // Now you can safely fetch the data for the bill
    $bill_sql = "SELECT r.id, r.quantity, r.request_date, d.name AS drug_name, d.price AS drug_price
                   FROM requests r
                   JOIN drugs d ON r.drug_id = d.id
                   WHERE r.id IN ($request_ids_placeholders)
                   ORDER BY r.request_date";

    // Corrected $types and $params for bill_sql
    $types = str_repeat('i', count($request_ids)); // Corrected: Removed extra 'i'
    $params = $request_ids; // Corrected: Removed institution_id from params

    $bill_result = executeQuery($conn, $bill_sql, $types, ...$params);

    if ($bill_result) {
        // Process the bill data
        echo "<!DOCTYPE html>";
        echo "<html lang='en'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<title>Bill for " . htmlspecialchars($date) . "</title>";
        echo "<link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>"; // Optional: Bootstrap CSS
        echo "<link rel='stylesheet' href='style.css'>";
        echo "</head>";
        echo "<body>";
        echo "<div class='wrapper'>"; // Optional: Bootstrap container

        echo "<h1>Bill for Date: " . htmlspecialchars($date) . "</h1>";
        echo "<table class='table table-bordered'>"; // Optional: Bootstrap table styling
        echo "<thead class='thead-light'><tr><th>Request ID</th><th>Drug Name</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead>";
        echo "<tbody>";
        $total_bill = 0;

        while ($row = $bill_result->fetch_assoc()) {
            $item_total = $row['drug_price'] * $row['quantity'];
            $total_bill += $item_total;

            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['drug_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
            echo "<td>" . htmlspecialchars($row['drug_price']) . "</td>";
            echo "<td>" . htmlspecialchars(number_format($item_total, 2)) . "</td>";  //Format to 2 decimal places
            echo "</tr>";
        }

        echo "<tr><td colspan='4' style='text-align:right;'><b>Total Bill:</b></td><td><b>" . htmlspecialchars(number_format($total_bill, 2)) . "</b></td></tr>"; //Format to 2 decimal places
        echo "</tbody>";
        echo "</table>";
        echo "<button onclick='window.print()' class='btn btn-primary ml-3 no-print'>Print</button>";

        echo "</div>"; // Optional: Bootstrap container
        echo "</body>";
        echo "</html>";

        // Add code to generate the actual bill (e.g., PDF) here.  Use a library like TCPDF or Dompdf.  Example:
        // include 'generate_pdf.php';  //Include a file to handle PDF generation
        // generatePDF($date, $bill_result, $total_bill);  //Pass the necessary data to the PDF generation function

    } else {
        echo "Error fetching bill details: " . $conn->error;
    }

} else {
    echo "Date and Request IDs not provided.";
}

$conn->close();
?>