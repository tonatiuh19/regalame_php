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

    if (isset($params['id_user']) && isset($params['id_users_payment_type']) && isset($params['value']) && isset($params['place'])) {
        $id_user = $conn->real_escape_string($params['id_user']);
        $id_users_payment_type = $conn->real_escape_string($params['id_users_payment_type']);
        $value = $conn->real_escape_string($params['value']);
        $place = $conn->real_escape_string($params['place']);
        $today = date("Y-m-d H:i:s");

        // Delete existing payment information for the user
        $deletePaymentQuery = "DELETE FROM users_payment WHERE id_user=$id_user";
        $conn->query($deletePaymentQuery);

        // Insert new payment information
        $insertPaymentQuery = "INSERT INTO users_payment (id_user, id_users_payment_type, value, place, date) 
VALUES ('$id_user', '$id_users_payment_type', '$value', '$place', '$today')";

        if ($conn->query($insertPaymentQuery) === TRUE) {
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

                // Fetch the payment information for the user
                $paymentQuery = "SELECT a.id_users_payment, a.id_users_payment_type, a.value, a.place, b.title 
FROM users_payment as a 
INNER JOIN users_payment_types as b on b.id_users_payment_types=a.id_users_payment_type 
WHERE a.id_user=$id_user";

                $paymentResult = $conn->query($paymentQuery);
                $payments = [];
                if ($paymentResult->num_rows > 0) {
                    while ($row = $paymentResult->fetch_assoc()) {
                        $payments[] = $row;
                    }
                }
                $user['paymentsTypes'] = $payments;

                $res = json_encode(utf8_encode_recursive($user), JSON_NUMERIC_CHECK);
                header('Content-type: application/json; charset=utf-8');
                echo $res;
            } else {
                echo json_encode(["message" => "User not found"], JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode(["message" => "Error inserting payment information: " . $conn->error], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(["message" => "Not valid Body Data"], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(["message" => "Invalid request method"], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>