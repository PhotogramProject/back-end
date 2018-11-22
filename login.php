<?php
require __DIR__ . '/../../vendor/autoload.php';
include '../config.php';
use Firebase\JWT\JWT;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header("Content-Type: application/json", true);

$postdata = file_get_contents("php://input");
$request = json_decode($postdata);

if (is_null($request) || empty($request)) {
    echo json_encode(['success' => false, 'msg' => "Не съществува такъв потребител!"], JSON_UNESCAPED_UNICODE);
    exit();
}

$username_post = $request->username;
$password_post = $request->password;

$query = "SELECT `id`, `password`, `email`, `first_name`, `last_name`, `blocked`, `roles`, `avatar` FROM users WHERE username = ?";
if ($stmt = $mysqli->prepare($query)) {
    $stmt->bind_param('s', $username_post);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        echo json_encode(['success' => false, 'msg' => "Не съществува такъв потребител!"], JSON_UNESCAPED_UNICODE);
    } else {
        $stmt->bind_result($id, $password, $email, $first_name, $last_name, $blocked, $roles, $avatar);
        $stmt->fetch();

        if (password_verify($password_post, $password)) {
            $token = [
                "user_id" => $id,
                "username" =>  $username_post,
                "iat" => time(),
            ];

            $jwt = JWT::encode($token, "photogram_security");

            echo json_encode([
                'success' => true,
                'msg' => "Входът в системата беше успешен!",
                'data' => [
                    'id' => $id,
                    'auth_token' => $jwt,
                    'blocked' => ($blocked == 1) ? true : false,
                    'email' => $email,
                    'firstName' => $first_name,
                    'lastName' => $last_name,
                    'roles' => ($roles == 1) ? ['Admin'] : [],
                    'username' => $username_post
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'msg' => "Въведената парола е грешна!"], JSON_UNESCAPED_UNICODE);
        }
    }

    $stmt->free_result();
    $stmt->close();
} else {
    var_dump($stmt->error);
}

$mysqli->close();