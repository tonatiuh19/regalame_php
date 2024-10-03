<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['id_user'])) {
        $id_user = $conn->real_escape_string($params['id_user']);

        // Query to select the payments for the specified user
        $selectPaymentsQuery = "SELECT a.id_payments, a.id_user, a.id_extra, a.date, a.question_answer, a.note_fan, a.isPublic_note_fan, a.payment_name, a.amount, a.transfered 
FROM payments as a 
WHERE a.status='Paid' AND a.id_user=$id_user";

        $paymentsResult = $conn->query($selectPaymentsQuery);

        if ($paymentsResult->num_rows > 0) {
            $payments = [];
            while ($paymentRow = $paymentsResult->fetch_assoc()) {
                $paymentRow = array_map('utf8_encode', $paymentRow);
                $payments[] = $paymentRow;
            }

            $res = json_encode($payments, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
            header('Content-type: application/json; charset=utf-8');
            echo $res;
        } else {
            echo json_encode(["message" => "No payments found"], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(["message" => "id_user not provided"], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(["message" => "Invalid request method"], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>