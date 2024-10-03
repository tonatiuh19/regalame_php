<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    // Query to select the active categories
    $selectCategoriesQuery = "SELECT a.id_categories, a.category, a.active 
FROM categories as a 
WHERE a.active=1";

    $categoriesResult = $conn->query($selectCategoriesQuery);

    if ($categoriesResult->num_rows > 0) {
        $categories = [];
        while ($categoryRow = $categoriesResult->fetch_assoc()) {
            $categoryRow = array_map('utf8_encode', $categoryRow);
            $categories[] = $categoryRow;
        }

        $res = json_encode($categories, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
        header('Content-type: application/json; charset=utf-8');
        echo $res;
    } else {
        echo json_encode(["message" => "No active categories found"], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(["message" => "Invalid request method"], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>