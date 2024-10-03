<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    // Check if the form was submitted
    if (isset($_POST['id_extra']) && isset($_POST['about']) && isset($_POST['confirmation']) && isset($_POST['price'])) {
        $id_extra = $_POST['id_extra'];
        $about = $_POST['about'];
        $confirmation = $_POST['confirmation'];
        $price = $_POST['price'];

        $updatePicture = false;
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] == UPLOAD_ERR_OK) {
            $updatePicture = true;
            // Handle the file upload
            $target_dir = "../assets/images/creatives/";
            $unique_name = uniqid() . '-' . basename($_FILES["picture"]["name"]);
            $target_file = $target_dir . $unique_name;
            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if image file is a actual image or fake image
            $check = getimagesize($_FILES["picture"]["tmp_name"]);
            if ($check !== false) {
                $uploadOk = 1;
            } else {
                echo "File is not an image.";
                $uploadOk = 0;
            }

            // Check if file already exists
            if (file_exists($target_file)) {
                echo "Sorry, file already exists.";
                $uploadOk = 0;
            }

            // Check file size
            if ($_FILES["picture"]["size"] > 500000) {
                echo "Sorry, your file is too large.";
                $uploadOk = 0;
            }

            // Allow certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                $uploadOk = 0;
            }

            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                echo "Sorry, your file was not uploaded.";
                $updatePicture = false;
            } else {
                // Ensure the target directory exists
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                // Move the uploaded file to the target directory
                if (move_uploaded_file($_FILES["picture"]["tmp_name"], $target_file)) {
                    $updatePicture = true;
                } else {
                    echo "Sorry, there was an error uploading your file.";
                    $updatePicture = false;
                }
            }
        }

        // Update the extra details in the database
        if ($updatePicture) {
            $sql = "UPDATE extras SET picture='$target_file', about='$about', confirmation='$confirmation', price='$price' WHERE id_extra=$id_extra";
        } else {
            $sql = "UPDATE extras SET about='$about', confirmation='$confirmation', price='$price' WHERE id_extra=$id_extra";
        }

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