<?php
require __DIR__ . '/../../../vendor/autoload.php';
include '../config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

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

    if (!isset($_SERVER["PATH_INFO"]) && is_null($_SERVER["PATH_INFO"])) {
        echo json_encode(['success' => false, 'msg' => "Неуспешен опит за връзка."], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if (!isset($_POST['journey-data'])) {
        echo json_encode(['success' => false, 'msg' => "Неуспешен опит за връзка."], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $journey_id = intval(trim(str_replace("/", "", $_SERVER["PATH_INFO"])));
    $journey_data = json_decode($_POST['journey-data']);
    $query = "UPDATE `journeys` SET `name` = ?,`description` = ?, `ratings` = ?, `total_reviewers` = ? WHERE id = ?";
    $name = $journey_data->name;
    $description = $journey_data->description;
    $ratings = implode(",", $journey_data->ratings);
    $total_reviewers = $journey_data->totalReviewers;
    $id = $journey_id;

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssssi', $name, $description, $ratings, $total_reviewers, $id);
    $stmt->execute();
    $stmt->free_result();
    $stmt->close();

    $query = "INSERT INTO `images`(`journey_id`, `make`, `model`, `date_taken`, `lat`, `lon`, `resolution_w`, `resolution_h`, `flash`, `iso`, `focal_length`, `file_name`, `size`, `comment`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('isssddiisidsds', $id, $make, $model, $date_taken, $lat, $lon, $res_w, $res_h, $flash, $iso, $focal_length, $image_filename, $size, $images_comment);

    $images_cnt = count($_FILES);
    for ($i = 0; $i < $images_cnt; $i++) {
        $image = $_FILES[$i];
        $suffix = explode("/", $image['type'])[1];
        $filename = time() . $i . "." . $suffix;
        move_uploaded_file($image['tmp_name'], __DIR__ . "/../../uploads/users/" . $decoded_jwt->username . "/" . $filename);

        $image_data = json_decode($_POST['imagesForUpload'][$i]);
        $id = $journey_id;
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

    $query = "UPDATE `images` SET `comment`= ? WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('si', $image_comment, $id);

    $images_cnt = count($_POST['imagesForUpdate']);
    for ($i = 0; $i < $images_cnt; $i++) {
        $image_data = json_decode($_POST['imagesForUpdate'][$i]);
        $image_comment = (isset($image_data->comment)) ? $image_data->comment : "";
        $id = (isset($image_data->id)) ? intval($image_data->id) : 0;

        $stmt->execute();
    }
    $stmt->free_result();

    echo json_encode([
        'success' => true,
        'msg' => 'Успешно редактирахте пътешествието.',
    ], JSON_UNESCAPED_UNICODE);
}