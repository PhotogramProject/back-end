<?php
require __DIR__ . '/../../../vendor/autoload.php';
include '../config.php';
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authentication');
header("Content-Type: application/json", true);

$key = "";
$authorisation_bearer = $_SERVER['HTTP_AUTHENTICATION'];
if (strpos($authorisation_bearer, 'Bearer ') !== false) {
    $key = substr($authorisation_bearer, 7);
}

if ($key == "") {
    echo json_encode(['success' => false, 'msg' => "Неуспешен опит за връзка."], JSON_UNESCAPED_UNICODE);
    exit();
} else {
    try {
        $decoded_jwt = JWT::decode($key, 'photogram_security', array('HS256'));
    } catch (SignatureInvalidException $e) {
        echo json_encode(['success' => false, 'msg' => "Неуспешен опит за връзка."], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $images_cnt = count($_FILES);
    if (!isset($_POST['journey-data'])) {
        echo json_encode(['success' => false, 'msg' => "Неуспешен опит за връзка."], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $query = "SELECT blocked FROM `users` WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $id);
    $id = $decoded_jwt->user_id;
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data["blocked"] == 1) {
        echo json_encode(['success' => false, 'msg' => "Профилът е блокиран!"], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $stmt->free_result();
    $stmt->close();

    $journey_data = json_decode($_POST['journey-data']);
    $journey_name = $journey_data->name;
    $journey_description = $journey_data->description;

    $query = "INSERT INTO `journeys`(`name`, `description`, `author`, `ratings`, `total_reviewers`, `date_created`) VALUES (?,?,?,\"0,0,0,0,0\",0,NOW())";
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param('ssi', $journey_name, $journey_description, $id);
        $stmt->execute();
        $stmt->free_result();
    }

    $query = "SELECT id FROM `journeys` ORDER BY date_created DESC LIMIT 1";
    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($journey_id);
    $stmt->fetch();
    $stmt->free_result();

    $query = "INSERT INTO `images`(`journey_id`, `make`, `model`, `date_taken`, `lat`, `lon`, `resolution_w`, `resolution_h`, `flash`, `iso`, `focal_length`, `file_name`, `size`, `comment`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param('isssddiisidsds', $journey_id, $make, $model, $date_taken, $lat, $lon, $res_w, $res_h, $flash, $iso, $focal_length, $image_filename, $size, $image_comment);

        for ($i = 0; $i < $images_cnt; $i++) {
            $image = $_FILES[$i];
            $suffix = explode("/", $image['type'])[1];
            $filename = time() . $i . "." . $suffix;
            move_uploaded_file($image['tmp_name'], __DIR__ . "/../../uploads/users/" . $decoded_jwt->username . "/" . $filename);

            $image_data = json_decode($_POST['images'][$i]);
            $make = (isset($image_data->make)) ? $image_data->make : "";
            $model = (isset($image_data->model)) ? $image_data->model : "";
            $date_taken = (isset($image_data->dateTaken)) ? $image_data->dateTaken : "";
            $location = (isset($image_data->location)) ? $image_data->location : NULL;
            if (is_null($location)) {
                $lat = 0;
                $lon = 0;
            } else {
                $lat = floatval($location[0]);
                $lon = floatval($location[1]);
            }
            $resolution = (isset($image_data->resolution)) ? $image_data->resolution : NULL;
            if (is_null($resolution)) {
                $res_w = 0;
                $res_h = 0;
            } else {
                $res_w = floatval($resolution[0]);
                $res_h = floatval($resolution[1]);
            }
            $flash = (isset($image_data->flash)) ? $image_data->flash : "";
            $iso = (isset($image_data->iso)) ? intval($image_data->iso) : 0;
            $focal_length = (isset($image_data->focalLength)) ? floatval($image_data->focalLength) : 0;
            $size = (isset($image_data->size)) ? floatval($image_data->size) : 0;
            $image_filename = $decoded_jwt->username . "/" . $filename;
            $image_comment = (isset($image_data->comment)) ? $image_data->comment : "";

            $stmt->execute();
        }
        $stmt->free_result();

        echo json_encode(['success' => true, 'msg' => "Успешно създадохте пътешествието $journey_name."], JSON_UNESCAPED_UNICODE);
    }
}