<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['section'])) {
        $section = $conn->real_escape_string($params['section']);
        $today = date("Y-m-d H:i:s");

        // Insert visitor information
        $insertVisitorQuery = "INSERT INTO visitors (section, date) VALUES ('$section', '$today')";

        if ($conn->query($insertVisitorQuery) === TRUE) {
            echo json_encode(["message" => "Visitor information inserted successfully"], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["message" => "Error inserting visitor information: " . $conn->error], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(["message" => "Not valid Body Data"], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(["message" => "Invalid request method"], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>