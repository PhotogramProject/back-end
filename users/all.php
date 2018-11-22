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

    $query = "SELECT roles FROM `users` WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $id);
    $id = $decoded_jwt->user_id;

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data["roles"] == 1) {
        $query = "SELECT * FROM `users`";
        $stmt = $mysqli->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        $response = [];
        while($row = $result->fetch_assoc()) {
            $response[] = [
                'id' => $row["id"],
                'blocked' => ($row["blocked"] == 1) ? true : false,
                'email' => $row["email"],
                'firstName' => $row["first_name"],
                'lastName' => $row["last_name"],
                'roles' => ($row["roles"] == 1) ? ['Admin'] : [],
                'username' => $row["username"],
                'avatar' => $row["avatar"]
            ];
        }

        $result->close();
        $stmt->close();
        $mysqli->close();

        echo json_encode([
            'success' => true,
            'data' => $response
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Нямате права за да изпълните тази заявка'], JSON_UNESCAPED_UNICODE);
    }
}