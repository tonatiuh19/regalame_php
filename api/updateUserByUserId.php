<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

$method = $_SERVER['REQUEST_METHOD'];

function utf8_encode_recursive($data)
{
    if (is_array($data)) {
        return array_map('utf8_encode_recursive', $data);
    } elseif (is_string($data)) {
        return utf8_encode($data);
    }
    return $data;
}

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['id_user'])) {
        $id_user = $conn->real_escape_string($params['id_user']);
        $name = $conn->real_escape_string($params['name']);
        $last_name = $conn->real_escape_string($params['last_name']);
        $phone = $conn->real_escape_string($params['phone']);
        $categories = $params['categories'];
        $today = date("Y-m-d H:i:s");

        // Update user information
        $updateUserQuery = "UPDATE users SET name='$name', last_name='$last_name', phone='$phone' WHERE id_user=$id_user";

        if ($conn->query($updateUserQuery) === TRUE) {
            // Delete existing categories for the user
            $deleteCategoriesQuery = "DELETE FROM users_categories WHERE id_user=$id_user";
            $conn->query($deleteCategoriesQuery);

            // Insert new categories for the user
            foreach ($categories as $category) {
                $category = $conn->real_escape_string($category);
                $insertCategoryQuery = "INSERT INTO users_categories(id_categories, id_user, date, active) VALUES ('$category', '$id_user', '$today', 1)";
                $conn->query($insertCategoryQuery);
            }

            // Fetch the updated user data
            $checkUserQuery = "SELECT a.id_user, a.email, a.email_verified, a.picture, a.name, a.last_name, a.stripe_id, a.phone, a.about, a.user_name 
FROM users as a 
WHERE a.id_user='$id_user'";

            $result = $conn->query($checkUserQuery);

            if ($result->num_rows > 0) {
                // User exists, fetch the user data
                $user = $result->fetch_assoc();

                // Fetch the categories for the user
                $categoriesQuery = "SELECT a.id_users_categories, a.id_categories, a.id_user 
FROM users_categories as a 
WHERE a.id_user=$id_user";

                $categoriesResult = $conn->query($categoriesQuery);
                $categories = [];
                if ($categoriesResult->num_rows > 0) {
                    while ($row = $categoriesResult->fetch_assoc()) {
                        $categories[] = $row;
                    }
                }
                $user['categories'] = $categories;

                $res = json_encode(utf8_encode_recursive($user), JSON_NUMERIC_CHECK);
                header('Content-type: application/json; charset=utf-8');
                echo $res;
            } else {
                echo json_encode(["message" => "User not found"], JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode(["message" => "Error updating user: " . $conn->error], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(["message" => "Not valid Body Data"], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(["message" => "Invalid request method"], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>