<?php
require __DIR__ . '/../../../vendor/autoload.php';
include '../config.php';
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
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

    $query = "SELECT file_name FROM `images` WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $id);
    $id = intval(trim(str_replace("/", "", $_SERVER["PATH_INFO"])));
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->free_result();

    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'msg' => "Снимката, която се опитвате да изтриете не съществува!"], JSON_UNESCAPED_UNICODE);
        exit();
    }
    $result->close();

    $dir = __DIR__ . "/../../uploads/users/" . $data["file_name"];
    if (is_file($dir)) {
        unlink($dir);
    }

    $query = "DELETE FROM `images` WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $id);
    $id = intval(trim(str_replace("/", "", $_SERVER["PATH_INFO"])));
    $stmt->execute();
    $stmt->free_result();

    $stmt->close();

    echo json_encode([
        'success' => true,
        'msg' => "Снимката беше успешно изтритa."
    ], JSON_UNESCAPED_UNICODE);
}