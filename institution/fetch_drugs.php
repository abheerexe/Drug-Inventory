<?php
require_once '../database.php';

if (isset($_POST['searchTerm'])) {
    $searchTerm = trim($_POST['searchTerm']);
    $drugs = [];

    if (!empty($searchTerm)) {
        $sql = "SELECT id, name FROM drugs WHERE name LIKE ? ORDER BY name LIMIT 20"; // Limiting results for better performance
        if ($stmt = $conn->prepare($sql)) {
            $param_term = "%" . $searchTerm . "%";
            $stmt->bind_param("s", $param_term);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $drugs[] = $row;
                }
            } else {
                // **Include error in JSON response**
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Database query failed', 'db_error' => $stmt->error]);
                exit; // Stop execution
            }
            $stmt->close();
        } else {
            // **Include error in JSON response**
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to prepare statement', 'db_error' => $conn->error]);
            exit; // Stop execution
        }
    }

    header('Content-Type: application/json');
    echo json_encode($drugs);

} else {
    // Handle cases where searchTerm is not set (e.g., direct access to this file)
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Search term not provided.']);
}

$conn->close(); // Close the connection in this script as well
?>