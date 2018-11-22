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

    if (!isset($_SERVER["PATH_INFO"]) && !is_null($_SERVER["PATH_INFO"])) {
        echo json_encode(['success' => false, 'msg' => "Неуспешен опит за връзка."], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $query = "SELECT * FROM `journeys` WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $id);
    $id = intval(trim(str_replace("/", "", $_SERVER["PATH_INFO"])));
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $query = "SELECT * FROM `images` WHERE journey_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $id);
    $id = intval(trim(str_replace("/", "", $_SERVER["PATH_INFO"])));
    $stmt->execute();
    $result = $stmt->get_result();

    $images = [];
    while($row = $result->fetch_assoc()) {
        $images[] = [
            'id' => $row["id"],
            'make' => $row["make"],
            'model' => $row["model"],
            'dateTaken' => $row["date_taken"],
            'location' => [$row["lat"], $row["lon"]],
            'resolution' => [$row['resolution_w'], $row['resolution_h']],
            'flash' => $row['flash'],
            'iso' => $row["iso"],
            'focalLength' => $row["focal_length"],
            'fileName' => $row['file_name'],
            'size' => $row['size'],
            'comment' => $row['comment']
        ];
    }

    $result->close();
    $stmt->free_result();
    $stmt->close();
    $mysqli->close();

    echo json_encode([
        'success' => true,
        'journey' => [
            'id' => $data["id"],
            'name' => $data['name'],
            'description' => $data['description'],
            'dateCreated' => $data['date_created'],
            'ratings' => array_map('intval', explode(',', $data['ratings'])),
            'totalReviewers' => $data['total_reviewers'],
        ],
        'images' => $images
    ], JSON_UNESCAPED_UNICODE);
}