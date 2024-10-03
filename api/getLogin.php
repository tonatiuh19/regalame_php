<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');
require_once('vendor/autoload.php');
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody);
    $params = (array) $params;

    if ($params['userEmail']) {

        /*$stripe = new \Stripe\StripeClient(
            "sk_test_51OtL2vI0AVtzugqlOig4E1ACAVjBX28q4H3PtW5AWEeICiAi6USnIgtDTB4SkQ2cg2FhWReBjT4sVqqNJ321lxHq00ApVEJXcL"
        );*/

        $userEmail = $params['userEmail'];
        $userEmailVerified = $params['userEmailVerified'];
        $userName = $params['userName'];
        $userUserName = $params['userUserName'];
        $userSurname = $params['userSurname'];
        $picture = $params['picture'];
        $today = date("Y-m-d H:i:s");


        /*$customer = $stripe->customers->create([
             'name' => $custName . " " . $custSurname,
             'email' => $custEmail,
         ]);
         $custStripeID = $customer["id"];*/

        $custStripeID = '';

        $checkUserQuery = "SELECT a.id_user, a.email, a.email_verified, a.picture, a.name, a.last_name, a.stripe_id, a.phone, a.about, a.user_name FROM users as a WHERE a.email='$userEmail'";

        $result = $conn->query($checkUserQuery);

        if ($result->num_rows > 0) {
            // User exists, fetch the user data
            $user = $result->fetch_assoc();
            $res = json_encode(array_map('utf8_encode', $user), JSON_NUMERIC_CHECK);
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
                    $newUserQuery = "SELECT a.id_user, a.email, a.email_verified, a.picture, a.name, a.last_name, a.stripe_id, a.phone, a.about, a.user_name FROM users as a WHERE a.id_user = $newUserId";

                    $newUserResult = $conn->query($newUserQuery);
                    if ($newUserResult->num_rows > 0) {
                        $newUser = $newUserResult->fetch_assoc();
                        $res = json_encode(array_map('utf8_encode', $newUser), JSON_NUMERIC_CHECK);
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
