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

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['id_user']) && isset($params['id_extra'])) {
        $id_user = $params['id_user'];
        $id_extra = $params['id_extra'];
        $email_user = $params['email_user'];
        $amount = $params['amount'];
        $description = $params['description'];
        $question_answer = $params['question_answer'];
        $payment_name = $params['payment_name'];
        $note_fan = $params['note_fan'];
        $isPublic_note_fan = $params['isPublic_note_fan'];
        $user_name = $params['user_name'];
        $payment_type = $params['payment_type'];

        $token = $params['token'];

        $todayVisit = date("Y-m-d H:i:s");

        $stripe = new \Stripe\StripeClient(
            "sk_test_51OtL2vI0AVtzugqlOig4E1ACAVjBX28q4H3PtW5AWEeICiAi6USnIgtDTB4SkQ2cg2FhWReBjT4sVqqNJ321lxHq00ApVEJXcL"
        );

        try {

            if ($payment_type === 'stripe') {
                $charge = $stripe->charges->create([
                    'amount' => $amount * 100,
                    'currency' => 'mxn',
                    'source' => $token
                ]);
                $chargeId = $charge["id"];
            } else {
                $chargeId = $token;
            }


            // Insert into BOOKING table
            $insertExtraPayment = "INSERT INTO payments(id_user, id_extra, email_user, id_stripe, type, status, date, amount, amount_fee, amount_tax, description, question_answer, payment_name, note_fan, isPublic_note_fan) VALUES ('$id_user','$id_extra','$email_user','$chargeId','$payment_type','Paid','$todayVisit','$amount','','','$description','$question_answer','$payment_name','$note_fan','$isPublic_note_fan')";

            if ($conn->query($insertExtraPayment) === TRUE) {
                $paymentID = $conn->insert_id;

                $fetchEmailQuery = "SELECT a.email FROM users as a WHERE a.id_user=$id_user";
                $emailResult = $conn->query($fetchEmailQuery);

                if ($emailResult->num_rows > 0) {
                    $emailRow = $emailResult->fetch_assoc();
                    $userEmail = $emailRow['email'];

                    if (sendMailGracias($email_user, $user_name, $note_fan, $amount, '')) {
                        sendMailHostCoffee($userEmail);
                    }
                    echo json_encode([
                        "paymentID" => $paymentID,
                        "user_name" => $user_name,
                        "user_email" => $userEmail,
                        "paymentSuccess" => true
                    ]);
                } else {
                    echo json_encode([
                        "paymentID" => $paymentID,
                        "user_name" => $user_name,
                        "user_email" => null,
                        "paymentSuccess" => true,
                        "message" => "User email not found"
                    ]);
                }
            } else {
                echo json_encode(["message" => "Error inserting payment: " . $conn->error, "paymentSuccess" => false, "errorCode" => 500]);
            }
        } catch (Exception $e) {
            echo json_encode(["message" => "Error payment: " . $e->getMessage(), "paymentSuccess" => false, "errorCode" => 500]);
        }
    } else {
        echo json_encode(["message" => "Invalid input", "paymentSuccess" => false, "errorCode" => 400]);
    }
} else {
    echo json_encode(["message" => "Invalid request method", "paymentSuccess" => false, "errorCode" => 400]);
}

function sendMailGracias($email, $uname, $message, $total, $qty)
{
    $mail = new PHPMailer(true);
    $iva = $total * 0.16;
    $subTotal = $total - $iva;
    $today = date("Y-m-d H:i:s");

    try {
        require_once "./serversettingsPhpmailer.php";
        $mail->addAddress($email, "Fan destacado"); // Add a recipient
        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = "Gracias por tu apoyo | Regalameuncafe";
        $mail->Body =
            '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Regalame un Cafe</title><style type="text/css">img{max-width:600px;outline:0;text-decoration:none;-ms-interpolation-mode:bicubic}a{border:0;outline:0}a img{border:none}h1,h2,h3,td{font-family:Helvetica,Arial,sans-serif;font-weight:400}td{font-size:13px;line-height:150%;text-align:left}body{-webkit-font-smoothing:antialiased;-webkit-text-size-adjust:none;width:100%;height:100%;color:#37302d;background:#fff}table{border-collapse:collapse!important}h1,h2,h3{padding:0;margin:0;color:#444;font-weight:400;line-height:110%}h1{font-size:35px}h2{font-size:30px}h3{font-size:24px}h4{font-size:18px;font-weight:400}.important-font{color:#21beb4;font-weight:700}.hide{display:none!important}.force-full-width{width:100%!important}td.desktop-hide{font-size:0;height:0;display:none;color:#fff}</style><style type="text/css" media="screen">@media screen{@import url(http://fonts.googleapis.com/css?family=Open+Sans:400);h1,h2,h3,td{font-family:"Open Sans","Helvetica Neue",Arial,sans-serif!important}}</style><style type="text/css" media="only screen and (max-width:600px)">@media only screen and (max-width:600px){table[class=w320]{width:320px!important}table[class=w300]{width:300px!important}table[class=w290]{width:290px!important}td[class=w320]{width:320px!important}td[class~=mobile-padding]{padding-left:14px!important;padding-right:14px!important}td[class*=mobile-padding-left]{padding-left:14px!important}td[class*=mobile-padding-right]{padding-right:14px!important}td[class*=mobile-block]{display:block!important;width:100%!important;text-align:left!important;padding-left:0!important;padding-right:0!important;padding-bottom:15px!important}td[class*=mobile-no-padding-bottom]{padding-bottom:0!important}td[class~=mobile-center]{text-align:center!important}table[class*=mobile-center-block]{float:none!important;margin:0 auto!important}[class*=mobile-hide]{display:none!important;width:0!important;height:0!important;line-height:0!important;font-size:0!important}td[class*=mobile-border]{border:0!important}td[class*=desktop-hide]{display:block!important;font-size:13px!important;height:61px!important;padding-top:10px!important;padding-bottom:10px!important;color:#444!important}}</style></head><body class="body" style="padding:0;margin:0;display:block;background:#fff;-webkit-text-size-adjust:none" bgcolor="#ffffff"><table align="center" cellpadding="0" cellspacing="0" width="100%" height="100%"><tr><td align="center" valign="top" bgcolor="#ffffff" width="100%"><table cellspacing="0" cellpadding="0" width="100%"><tr><td style="background:#1f1f1f" width="100%"><center><table cellspacing="0" cellpadding="0" width="600" class="w320"><tr><td valign="top" class="mobile-block mobile-no-padding-bottom mobile-center" width="270" style="background:#1f1f1f;padding:10px 10px 10px 20px"><a href="#" style="text-decoration:none"><img src="https://garbrix.com/regalame/assets/images/logo-new-version.png" height="30" alt="Regalame un Cafe"></a></td><td valign="top" class="mobile-block mobile-center" width="270" style="background:#1f1f1f;padding:10px 15px 10px 10px"></td></tr></table></center></td></tr><tr><td style="border-bottom:1px solid #e7e7e7"><center><table cellpadding="0" cellspacing="0" width="600" class="w320"><tr><td align="left" class="mobile-padding" style="padding:20px"><br class="mobile-hide"><div><h3>Infinitas Gracias</h3><br>Tu apoyo ahora es una realidad.<br>Aquí un mensaje especial:<br><p>' . $uname . '<br><i>"' . $message . '"</i></p><br></div><br><table cellspacing="0" cellpadding="0" width="100%" bgcolor="#ffffff"><tr><td style="width:100px;background:#74654e"><div><!--[if mso]><v:rect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="#" style="height:33px;v-text-anchor:middle;width:100px" stroke="f" fillcolor="#74654e"><w:anchorlock><center><![endif]--><a href="https://regalameuncafe.com/' .
            $uname .
            '/" style="background-color:#74654e;color:#fff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:700;line-height:33px;text-align:center;text-decoration:none;width:150px;-webkit-text-size-adjust:none">Regalar otro Cafe</a><!--[if mso]><![endif]--></div></td><td width="281" style="background-color:#fff;font-size:0;line-height:0">&nbsp;</td></tr></table></td><td class="mobile-hide" style="padding-top:20px;padding-bottom:0;vertical-align:bottom" valign="bottom"><table cellspacing="0" cellpadding="0" width="100%"><tr><td align="right" valign="bottom" style="padding-bottom:0;vertical-align:bottom"><img style="vertical-align:bottom" src="https://garbrix.com/regalame/assets/images/love_mail.svg" width="174" height="294"></td></tr></table></td></tr></table></center></td></tr><tr><td valign="top" style="background-color:#f8f8f8;border-bottom:1px solid #e7e7e7"><center><table border="0" cellpadding="0" cellspacing="0" width="600" class="w320" style="height:100%"><tr><td valign="top" class="mobile-padding" style="padding:20px"><table cellspacing="0" cellpadding="0" width="100%"><tr><td style="padding-right:20px"><b>Cafe para</b></td><td style="padding-right:20px"><b></b></td><td><b>Monto</b></td></tr><tr><td style="padding-top:5px;padding-right:20px;border-top:1px solid #e7e7e7">' . $uname . '</td><td style="padding-top:5px;padding-right:20px;border-top:1px solid #e7e7e7">' . $qty . '</td><td style="padding-top:5px;border-top:1px solid #e7e7e7" class="mobile">$' . $total . '</td></tr></table><table cellspacing="0" cellpadding="0" width="100%"><tr><td style="padding-top:35px"><table cellpadding="0" cellspacing="0" width="100%"><tr><td width="350" class="mobile-hide" style="vertical-align:top">No se puede comprar la felicidad pero si un buen café :)<br><p>Equipo RegalameunCafe<br>ayuda@regalameuncafe.com<p></td><td style="padding:0 0 15px 30px" class="mobile-block"><table cellspacing="0" cellpadding="0" width="100%"><tr><td>Subtotal:</td><td><b>$' . $subTotal . '</b></td></tr><tr><td>IVA</td><td>$' . $iva . '</td></tr><tr><td>Total:</td><td><b>$' . $total . "</b></td></tr><tr><td>Fecha:</td><td>" . $today . '</td></tr></table></td></tr><tr><td style="vertical-align:top" class="desktop-hide">Thank you for your business. Please contact us with any questions regarding this invoice,<br><br>Awesome Co</td></tr></table></td></tr></table></td></tr></table></center></td></tr><tr><td style="background-color:#1f1f1f"><center><table border="0" cellpadding="0" cellspacing="0" width="600" class="w320" style="height:100%;color:#fff" bgcolor="#1f1f1f"><tr><td align="right" valign="middle" class="mobile-padding" style="font-size:12px;padding:20px;background-color:#1f1f1f;color:#fff;text-align:left"><a style="color:#fff" href="mailto:ayuda@regalameuncafe.com?Subject=Necesito%20ayuda">Soporte</a>&nbsp;&nbsp;|&nbsp;&nbsp;</td></tr></table></center></td></tr></table></td></tr></table></body></html>';
        $mail->AltBody = "Gracias por tu apoyo!";
        $mail->CharSet = 'UTF-8';
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

function sendMailHostCoffee($email)
{
    $mail = new PHPMailer(true);

    try {
        require_once "./serversettingsPhpmailer.php";
        $mail->addAddress($email); // Add a recipient
        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = "Nuevo Café | Regalameuncafe";
        $mail->Body =
            '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Regalame un Cafe</title><style type="text/css">img{max-width:600px;outline:0;text-decoration:none;-ms-interpolation-mode:bicubic}a{border:0;outline:0}a img{border:none}h1,h2,h3,td{font-family:Helvetica,Arial,sans-serif;font-weight:400}td{font-size:13px;line-height:150%;text-align:left}body{-webkit-font-smoothing:antialiased;-webkit-text-size-adjust:none;width:100%;height:100%;color:#37302d;background:#fff}table{border-collapse:collapse!important}h1,h2,h3{padding:0;margin:0;color:#444;font-weight:400;line-height:110%}h1{font-size:35px}h2{font-size:30px}h3{font-size:24px}h4{font-size:18px;font-weight:400}.important-font{color:#21beb4;font-weight:700}.hide{display:none!important}.force-full-width{width:100%!important}td.desktop-hide{font-size:0;height:0;display:none;color:#fff}</style><style type="text/css" media="screen">@media screen{@import url(http://fonts.googleapis.com/css?family=Open+Sans:400);h1,h2,h3,td{font-family:"Open Sans","Helvetica Neue",Arial,sans-serif!important}}</style><style type="text/css" media="only screen and (max-width:600px)">@media only screen and (max-width:600px){table[class=w320]{width:320px!important}table[class=w300]{width:300px!important}table[class=w290]{width:290px!important}td[class=w320]{width:320px!important}td[class~=mobile-padding]{padding-left:14px!important;padding-right:14px!important}td[class*=mobile-padding-left]{padding-left:14px!important}td[class*=mobile-padding-right]{padding-right:14px!important}td[class*=mobile-block]{display:block!important;width:100%!important;text-align:left!important;padding-left:0!important;padding-right:0!important;padding-bottom:15px!important}td[class*=mobile-no-padding-bottom]{padding-bottom:0!important}td[class~=mobile-center]{text-align:center!important}table[class*=mobile-center-block]{float:none!important;margin:0 auto!important}[class*=mobile-hide]{display:none!important;width:0!important;height:0!important;line-height:0!important;font-size:0!important}td[class*=mobile-border]{border:0!important}td[class*=desktop-hide]{display:block!important;font-size:13px!important;height:61px!important;padding-top:10px!important;padding-bottom:10px!important;color:#444!important}}</style></head><body class="body" style="padding:0;margin:0;display:block;background:#fff;-webkit-text-size-adjust:none" bgcolor="#ffffff"><table align="center" cellpadding="0" cellspacing="0" width="100%" height="100%"><tr><td align="center" valign="top" bgcolor="#ffffff" width="100%"><table cellspacing="0" cellpadding="0" width="100%"><tr><td style="background:#1f1f1f" width="100%"><center><table cellspacing="0" cellpadding="0" width="600" class="w320"><tr><td valign="top" class="mobile-block mobile-no-padding-bottom mobile-center" width="270" style="background:#1f1f1f;padding:10px 10px 10px 20px"><a href="#" style="text-decoration:none"><img src="https://garbrix.com/regalame/assets/images/logo-new-version.png" height="30" alt="Your Logo"></a></td><td valign="top" class="mobile-block mobile-center" width="270" style="background:#1f1f1f;padding:10px 15px 10px 10px"></td></tr></table></center></td></tr><tr><td style="border-bottom:1px solid #e7e7e7"><center><table cellpadding="0" cellspacing="0" width="600" class="w320"><tr><td align="left" class="mobile-padding" style="padding:20px"><br class="mobile-hide"><div><h3>Te han regalado un cafe</h3><br>Entra a tu cuenta para revisar.<br>Muy pronto te estaremos transfiriendo tu dinero.<br><br></div><br><table cellspacing="0" cellpadding="0" width="100%" bgcolor="#ffffff"><tr><td style="width:100px;background:#74654e"><div><!--[if mso]><v:rect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="#" style="height:33px;v-text-anchor:middle;width:100px" stroke="f" fillcolor="#74654e"><w:anchorlock><center><![endif]--><a href="https://regalameuncafe.com/" style="background-color:#74654e;color:#fff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:700;line-height:33px;text-align:center;text-decoration:none;width:150px;-webkit-text-size-adjust:none">Entrar a mi cuenta</a><!--[if mso]><![endif]--></div></td><td width="281" style="background-color:#fff;font-size:0;line-height:0">&nbsp;</td></tr></table></td><td class="mobile-hide" style="padding-top:20px;padding-bottom:0;vertical-align:bottom" valign="bottom"><table cellspacing="0" cellpadding="0" width="100%"><tr><td align="right" valign="bottom" style="padding-bottom:0;vertical-align:bottom"><img style="vertical-align:bottom" src="https://garbrix.com/regalame/assets/images/love_mail.svg" width="174" height="294"></td></tr></table></td></tr></table></center></td></tr><tr><td valign="top" style="background-color:#f8f8f8;border-bottom:1px solid #e7e7e7"><center><table border="0" cellpadding="0" cellspacing="0" width="600" class="w320" style="height:100%"><tr><td valign="top" class="mobile-padding" style="padding:20px"><table cellspacing="0" cellpadding="0" width="100%"><tr><td style="padding-right:20px"></td><td style="padding-right:20px"></td><td></td></tr><tr><td style="padding-top:5px;padding-right:20px;border-top:1px solid #e7e7e7"></td><td style="padding-top:5px;padding-right:20px;border-top:1px solid #e7e7e7"></td><td style="padding-top:5px;border-top:1px solid #e7e7e7" class="mobile"></td></tr></table><table cellspacing="0" cellpadding="0" width="100%"><tr><td style="padding-top:35px"><table cellpadding="0" cellspacing="0" width="100%"><tr><td width="350" class="mobile-hide" style="vertical-align:top">No se puede comprar la felicidad pero si un buen café :)<br><p>Equipo RegalameunCafe<br>ayuda@regalameuncafe.com</p><p></p></td><td style="padding:0 0 15px 30px" class="mobile-block"><table cellspacing="0" cellpadding="0" width="100%"><tr><td></td><td></td></tr><tr><td></td><td></td></tr><tr><td></td><td></td></tr><tr><td></td><td></td></tr></table></td></tr><tr><td style="vertical-align:top" class="desktop-hide">Thank you for your business. Please contact us with any questions regarding this invoice,<br><br>Awesome Co</td></tr></table></td></tr></table></td></tr></table></center></td></tr><tr><td style="background-color:#1f1f1f"><center><table border="0" cellpadding="0" cellspacing="0" width="600" class="w320" style="height:100%;color:#fff" bgcolor="#1f1f1f"><tr><td align="right" valign="middle" class="mobile-padding" style="font-size:12px;padding:20px;background-color:#1f1f1f;color:#fff;text-align:left"><a style="color:#fff" href="mailto:ayuda@regalameuncafe.com?Subject=Necesito%20ayuda">Soporte</a>&nbsp;&nbsp;|&nbsp;&nbsp;</td></tr></table></center></td></tr></table></td></tr></table></body></html>';
        $mail->AltBody = "Nuevo Café!";
        $mail->CharSet = 'UTF-8';
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}
$conn->close();
