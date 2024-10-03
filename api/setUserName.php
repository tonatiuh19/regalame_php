<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody);
    $params = (array) $params;

    if ($params['id_user']) {
        $id_user = $params['id_user'];
        $userUserName = $params['userUserName'];
        $contactDate = date("Y-m-d H:i:s");

        $sql = "UPDATE users SET user_name='$userUserName' WHERE id_user=$id_user";

        if ($conn->query($sql) === TRUE) {
            echo "1";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

    } else {
        echo "Not valid Body Data";
    }

} else {
    echo "Not valid Data";
}

$conn->close();
?>