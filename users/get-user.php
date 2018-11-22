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

    $query = " SELECT *, (SELECT COUNT(*) FROM `journeys` WHERE author = users.id) as cnt FROM `users` WHERE username = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $username);
    $username = trim(str_replace("/", "", $_SERVER["PATH_INFO"]));
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'msg' => "Потребителят не съществува."], JSON_UNESCAPED_UNICODE);
        exit();
    } else {
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $data["id"],
                'blocked' => ($data["blocked"] == 1) ? true : false,
                'email' => $data["email"],
                'firstName' => $data["first_name"],
                'lastName' => $data["last_name"],
                'roles' => ($data["roles"] == 1) ? ['Admin'] : [],
                'username' => $username,
                'avatar' => $data["avatar"],
                'totalJourneys' => $data['cnt']
            ]
        ], JSON_UNESCAPED_UNICODE);
    }

    $result->close();
    $stmt->close();
    $mysqli->close();
}