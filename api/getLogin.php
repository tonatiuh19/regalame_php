<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');
require_once('vendor/autoload.php');
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

    if (isset($params['userEmail'])) {
        $userEmail = $conn->real_escape_string($params['userEmail']);
        $userEmailVerified = $conn->real_escape_string($params['userEmailVerified']);
        $userName = $conn->real_escape_string($params['userName']);
        $userUserName = $conn->real_escape_string($params['userUserName']);
        $userSurname = $conn->real_escape_string($params['userSurname']);
        $picture = $conn->real_escape_string($params['picture']);
        $today = date("Y-m-d H:i:s");

        $custStripeID = '';

        $checkUserQuery = "SELECT a.id_user, a.email, a.email_verified, a.picture, a.name, a.last_name, a.stripe_id, a.phone, a.about, a.user_name 
FROM users as a 
WHERE a.email='$userEmail'";

        $result = $conn->query($checkUserQuery);

        if ($result->num_rows > 0) {
            // User exists, fetch the user data
            $user = $result->fetch_assoc();
            $idUser = $user['id_user'];

            // Fetch the categories for the user
            $categoriesQuery = "SELECT a.id_users_categories, a.id_categories, a.id_user 
FROM users_categories as a 
WHERE a.id_user=$idUser";

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
            // User does not exist, insert the user
            $insertUserQuery = "INSERT INTO users(email, email_verified, name, last_name, user_name, picture, date, about, stripe_id) 
VALUES ('$userEmail', '$userEmailVerified', '$userName', '$userSurname', '$userUserName', '$picture', '$today', '¡Oye!, acabo de crear una super página aquí. ¡Ahora puedes invitarme a un café!', '$custStripeID')";

            if ($conn->query($insertUserQuery) === TRUE) {
                // Fetch the newly inserted user
                $newUserId = $conn->insert_id;

                $insertUserExtra = "INSERT INTO extras(title, id_user, description, confirmation, price, date, active, about) 
VALUES ('Coffee','$newUserId','¡Me estas invitando un cafe!','Te agradezco de todo corazon. Tu apoyo me permite seguir motivado :)','50','$today','4', '¡Oye!, acabo de crear una super página aquí. ¡Ahora puedes invitarme a un café!')";
                if ($conn->query($insertUserExtra) === TRUE) {
                    $newUserQuery = "SELECT a.id_user, a.email, a.email_verified, a.picture, a.name, a.last_name, a.stripe_id, a.phone, a.about, a.user_name 
FROM users as a 
WHERE a.id_user = $newUserId";

                    $newUserResult = $conn->query($newUserQuery);
                    if ($newUserResult->num_rows > 0) {
                        $newUser = $newUserResult->fetch_assoc();

                        // Fetch the categories for the new user
                        $categoriesResult = $conn->query($categoriesQuery);
                        $categories = [];
                        if ($categoriesResult->num_rows > 0) {
                            while ($row = $categoriesResult->fetch_assoc()) {
                                $categories[] = $row;
                            }
                        }
                        $newUser['categories'] = $categories;

                        $res = json_encode(utf8_encode_recursive($newUser), JSON_NUMERIC_CHECK);
                        header('Content-type: application/json; charset=utf-8');
                        echo $res;
                    } else {
                        echo json_encode(["message" => "User inserted but not found"]);
                    }
                } else {
                    echo json_encode(["message" => "Error inserting user: " . $conn->error]);
                }
            } else {
                echo json_encode(["message" => "Error inserting user: " . $conn->error]);
            }
        }
    } else {
        echo "Not valid Body Data";
    }
} else {
    echo "Not valid Data";
}

$conn->close();
?>