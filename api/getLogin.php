<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "./phpmailer/phpmailer/src/Exception.php";
require "./phpmailer/phpmailer/src/PHPMailer.php";
require "./phpmailer/phpmailer/src/SMTP.php";
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

            // Fetch the payment information for the user
            $paymentQuery = "SELECT a.id_users_payment, a.id_users_payment_type, a.value, a.place, b.title 
FROM users_payment as a 
INNER JOIN users_payment_types as b on b.id_users_payment_types=a.id_users_payment_type 
WHERE a.id_user=$idUser";

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

                        $categoriesQuery = "SELECT a.id_users_categories, a.id_categories, a.id_user 
FROM users_categories as a 
WHERE a.id_user=$newUserId";
                        // Fetch the categories for the new user
                        $categoriesResult = $conn->query($categoriesQuery);
                        $categories = [];
                        if ($categoriesResult->num_rows > 0) {
                            while ($row = $categoriesResult->fetch_assoc()) {
                                $categories[] = $row;
                            }
                        }
                        $newUser['categories'] = $categories;

                        $paymentQuery = "SELECT a.id_users_payment, a.id_users_payment_type, a.value, a.place, b.title 
FROM users_payment as a 
INNER JOIN users_payment_types as b on b.id_users_payment_types=a.id_users_payment_type 
WHERE a.id_user=$newUserId";
                        // Fetch the payment information for the new user
                        $paymentResult = $conn->query($paymentQuery);
                        $payments = [];
                        if ($paymentResult->num_rows > 0) {
                            while ($row = $paymentResult->fetch_assoc()) {
                                $payments[] = $row;
                            }
                        }
                        $newUser['paymentsTypes'] = $payments;

                        sendMailWelcome($userEmail, $userName);
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

function sendMailWelcome($email, $uname)
{
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = 2;                                     // Enable verbose debug output
        // $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host = 'mail.regalameuncafe.com';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                                   // Enable SMTP authentication
        $mail->Username = 'no-reply@regalameuncafe.com';                     // SMTP username
        $mail->Password = 'Mailer123';                               // SMTP password
        $mail->SMTPSecure = 'ssl';                                  // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 469;                                   // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
        $mail->CharSet = 'UTF-8';
        //Recipients
        $mail->setFrom('no-reply@regalameuncafe.com', 'Regalame un Cafe | Asistente');
        //$mail->addAddress('ellen@example.com');               // Name is optional
        $mail->addReplyTo('ayuda@regalameuncafe.com', 'Asistente');
        $mail->setLanguage(
            "es",
            "./phpmailer/phpmailer/language"
        );
        $mail->addAddress($email, $uname); // Add a recipient

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = "Bienvenidx :) | Regalameuncafe";
        $mail->Body =
            '<html> <head> <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> <meta name="viewport" content="width=device-width, initial-scale=1" /> <title>Regalame un Cafe</title> <style type="text/css"> img {max-width: 600px; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; } a {border: 0; outline: none; } a img {border: none; } td, h1, h2, h3  {font-family: Helvetica, Arial, sans-serif; font-weight: 400; } td {font-size: 13px; line-height: 150%; text-align: left; } body {-webkit-font-smoothing:antialiased; -webkit-text-size-adjust:none; width: 100%; height: 100%; color: #37302d; background: #ffffff; } table {border-collapse: collapse !important; } h1, h2, h3 {padding: 0; margin: 0; color: #444444; font-weight: 400; line-height: 110%; } h1 {font-size: 35px; } h2 {font-size: 30px; } h3 {font-size: 24px; } h4 {font-size: 18px; font-weight: normal; } .important-font {color: #21BEB4; font-weight: bold; } .hide {display: none !important; } .force-full-width {width: 100% !important; } </style> <style type="text/css" media="screen"> @media screen {@import url(http://fonts.googleapis.com/css?family=Open+Sans:400); td, h1, h2, h3 {font-family: "Open Sans", "Helvetica Neue", Arial, sans-serif !important; } } </style> <style type="text/css" media="only screen and (max-width: 600px)"> @media only screen and (max-width: 600px) {table[class="w320"] {width: 320px !important; } table[class="w300"] {width: 300px !important; } table[class="w290"] {width: 290px !important; } td[class="w320"] {width: 320px !important; } td[class~="mobile-padding"] {padding-left: 14px !important; padding-right: 14px !important; } td[class*="mobile-padding-left"] {padding-left: 14px !important; } td[class*="mobile-padding-right"] {padding-right: 14px !important; } td[class*="mobile-block"] {display: block !important; width: 100% !important; text-align: left !important; padding-left: 0 !important; padding-right: 0 !important; padding-bottom: 15px !important; } td[class*="mobile-no-padding-bottom"] {padding-bottom: 0 !important; } td[class~="mobile-center"] {text-align: center !important; } table[class*="mobile-center-block"] {float: none !important; margin: 0 auto !important; } *[class*="mobile-hide"] {display: none !important; width: 0 !important; height: 0 !important; line-height: 0 !important; font-size: 0 !important; } td[class*="mobile-border"] {border: 0 !important; } } </style> </head> <body class="body" style="padding:0; margin:0; display:block; background:#ffffff; -webkit-text-size-adjust:none" bgcolor="#ffffff"> <table align="center" cellpadding="0" cellspacing="0" width="100%" height="100%"> <tr> <td align="center" valign="top" bgcolor="#ffffff"  width="100%"> <table cellspacing="0" cellpadding="0" width="100%"> <tr> <td style="background:#1f1f1f" width="100%"> <center> <table cellspacing="0" cellpadding="0" width="600" class="w320"> <tr> <td valign="top" class="mobile-block mobile-no-padding-bottom mobile-center" width="270" style="background:#1f1f1f;padding:10px 10px 10px 20px;"> <a href="#" style="text-decoration:none;"> <img src="https://garbrix.com/regalame/assets/images/logo-new-version.png" height="30" alt="Your Logo"/> </a> </td> <td valign="top" class="mobile-block mobile-center" width="270" style="background:#1f1f1f;padding:10px 15px 10px 10px"> </td> </tr> </table> </center> </td> </tr> <tr> <td style="border-bottom:1px solid #e7e7e7;"> <center> <table cellpadding="0" cellspacing="0" width="600" class="w320"> <tr> <td align="left" class="mobile-padding" style="padding:20px"> <br class="mobile-hide" /> <h2>Bienvenidx</h2><br> <p>¡Hola ' .
            $uname .
            '!</p> Te felicitamos por darle a tu audiencia lo que realmente se merece y necesita. Si necesitas en algún momento alguna ayuda, puedes responder este correo o dar click en el enlace de abajo de soporte.<br> <br> <p>Equipo Regalameuncafe<br>ayuda@regalameuncafe.com</p> <table cellspacing="0" cellpadding="0" width="100%" bgcolor="#ffffff"> <tr> <td width="281" style="background-color:#ffffff; font-size:0; line-height:0;">&nbsp;</td> </tr> </table> </td> <td class="mobile-hide" style="padding-top:20px;padding-bottom:0; vertical-align:bottom;" valign="bottom"> <table cellspacing="0" cellpadding="0" width="100%"> <tr> <td align="right" valign="bottom" style="padding-bottom:0; vertical-align:bottom;"> <img  style="vertical-align:bottom;" src="https://garbrix.com/regalame/assets/images/cheers_mail.png"  width="174" height="294" /> </td> </tr> </table> </td> </tr> </table> </center> </td> </tr> <tr> <td valign="top" style="background-color:#f8f8f8;border-bottom:1px solid #e7e7e7;"> <center> <table border="0" cellpadding="0" cellspacing="0" width="600" class="w320" style="height:100%;"> <tr> <td valign="top" class="mobile-padding" style="padding:20px;"> <table cellspacing="0" cellpadding="0" width="100%"> <tr> <td style="padding-right:20px"> </td> <td> </td> </tr> <tr> <td style="padding-top:5px; padding-right:20px; border-top:1px solid #E7E7E7; vertical-align:top; "> Tambien puedes seguir apoyando a otros creadores ;) </td> <td style="padding-top:5px; border-top:1px solid #E7E7E7;"> </td> </tr> </table> </td> </tr> </table> </center> </td> </tr> <tr> <td style="background-color:#1f1f1f;"> <center> <table border="0" cellpadding="0" cellspacing="0" width="600" class="w320" style="height:100%;color:#ffffff" bgcolor="#1f1f1f" > <tr> <td align="right" valign="middle" class="mobile-padding" style="font-size:12px;padding:20px; background-color:#1f1f1f; color:#ffffff; text-align:left; "> <a style="color:#ffffff;" href="mailto:ayuda@regalameuncafe.com?Subject=Necesito%20ayuda">Soporte</a>&nbsp;&nbsp;|&nbsp;&nbsp; </td> </tr> </table> </center> </td> </tr> </table> </td> </tr> </table> </body> </html>';
        $mail->AltBody =
            "Bienvenidx " .
            $uname .
            ", <p>Te damos un reconocimiento por darle a tu audiencia lo que se merece y necesita.</p> <br>Equipo Regalameuncafe.<br>ayuda@regalameuncafe.com";
        $mail->send();
        return true;
    } catch (Exception $e) {
        //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
        // header('Location: ../algosaliomal/');
    }
}

$conn->close();
