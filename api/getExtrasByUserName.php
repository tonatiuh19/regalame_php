<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['username'])) {
        $username = $conn->real_escape_string($params['username']);

        $selectUserQuery = "SELECT a.id_user, a.user_name 
FROM users as a 
WHERE a.user_name='$username'";

        $userResult = $conn->query($selectUserQuery);

        if ($userResult->num_rows > 0) {
            $userRow = $userResult->fetch_assoc();
            $userRow = array_map('utf8_encode', $userRow);

            $selectExtrasQuery = "SELECT a.id_extra, a.title, a.picture, a.description, a.confirmation, a.about, a.limit_slots, a.price, a.question, a.subsciption, a.subsciption_id, a.active 
FROM extras as a 
WHERE a.id_user=" . $userRow['id_user'];

            $extrasResult = $conn->query($selectExtrasQuery);

            $extras = [];
            while ($extraRow = $extrasResult->fetch_assoc()) {
                if (empty($extraRow['picture'])) {
                    $extraRow['picture'] = 'https://garbrix.com/regalame/assets/images/deafult_extra.png';
                }

                $extraRow = array_map('utf8_encode', $extraRow);

                // Query to select the payments for the current extra
                $selectPaymentsQuery = "SELECT a.id_payments, a.id_user, a.id_extra, a.date, a.question_answer, a.note_fan, a.isPublic_note_fan, a.payment_name 
    FROM payments as a 
    WHERE a.status='Paid' AND a.id_extra=" . $extraRow['id_extra'];

                $paymentsResult = $conn->query($selectPaymentsQuery);

                $payments = [];
                while ($paymentRow = $paymentsResult->fetch_assoc()) {
                    $paymentRow = array_map('utf8_encode', $paymentRow);
                    $payments[] = $paymentRow;
                }

                $extraRow['payments'] = $payments;
                $extras[] = $extraRow;
            }

            $userRow['extras'] = $extras;

            $res = json_encode($userRow, JSON_NUMERIC_CHECK);
            header('Content-type: application/json; charset=utf-8');
            echo $res;
        } else {
            echo 0;
        }
    } else {
        echo json_encode(["message" => "Not valid Body Data"], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(["message" => "Not valid Data"], JSON_UNESCAPED_UNICODE);
}

$conn->close();