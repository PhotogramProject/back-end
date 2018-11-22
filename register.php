<?php
require __DIR__ . '/../../vendor/autoload.php';
include '../config.php';
use Firebase\JWT\JWT;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header("Content-Type: application/json", true);

$request = json_decode($_POST['register_data']);

$username_post = $request->username;
$password_post = $request->password;
$first_name_post = $request->firstName;
$last_name_post = $request->lastName;
$email_post = $request->email;

$query = "SELECT `id`, `password`, `email`, `first_name`, `last_name`, `blocked`, `roles`, `avatar` FROM users WHERE username = ?";
if ($stmt = $mysqli->prepare($query)) {
    $stmt->bind_param('s', $username_post);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        if ($username_post === '' || $username_post === NULL || strlen($username_post) < 6 || strpos($username_post, '/') !== false || strpos($username_post, '\\') !== false) {
            // check username
            echo json_encode(['success' => false, 'msg' => "'Невалидни данни."], JSON_UNESCAPED_UNICODE);
        } else if ($password_post === '' || $password_post === NULL || strlen($password_post) < 6) {
            // check password
            echo json_encode(['success' => false, 'msg' => "'Невалидни данни."], JSON_UNESCAPED_UNICODE);
        } else if (!preg_match("/^(([^<>()\[\]\\.,;:\s@\"]+(\.[^<>()\[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/", $email_post) || $email_post === '' || $email_post === NULL) {
            // check email
            echo json_encode(['success' => false, 'msg' => "'Невалидни данни."], JSON_UNESCAPED_UNICODE);
        } else if ($first_name_post === '' || $first_name_post === NULL) {
            // check first name
            echo json_encode(['success' => false, 'msg' => "'Невалидни данни."], JSON_UNESCAPED_UNICODE);
        } else if ($last_name_post === '' || $last_name_post === NULL) {
            // check last name
            echo json_encode(['success' => false, 'msg' => "'Невалидни данни."], JSON_UNESCAPED_UNICODE);
        } else {
            if (isset($_FILES['user_avatar'])) {
                $avatar_image = $_FILES['user_avatar'];
                $suffix = explode("/", $avatar_image['type'])[1];
                $avatar = $username_post . "." . $suffix;
                move_uploaded_file($avatar_image['tmp_name'], __DIR__ . "/../uploads/avatars/" . $avatar);
            } else {
                $avatar = "default-avatar.png";
            }

            mkdir(__DIR__ . "/../uploads/users/" . $username_post);

            $query = "INSERT INTO users (`username`, `password`, `email`, `first_name`, `last_name`, `avatar`, `blocked`, `roles`) VALUES (?, ?, ?, ?, ?, ?, 0, 0)";
            $stmt->free_result();
            $stmt->prepare($query);
            $stmt->bind_param("ssssss", $username_post, password_hash($password_post, PASSWORD_BCRYPT), $email_post, $first_name_post, $last_name_post, $avatar);
            $stmt->execute();

            $stmt->free_result();

            $query = "SELECT `id`, `email`, `first_name`, `last_name`, `blocked`, `roles`, `avatar` FROM users WHERE username = ?";
            $stmt->prepare($query);
            $stmt->bind_param("s", $username_post);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($id, $email, $first_name, $last_name, $blocked, $roles, $avatar);
            $stmt->fetch();
            $stmt->free_result();

            $token = [
                "user_id" => $id,
                "username" =>  $username_post,
                "iat" => time(),
            ];

            echo json_encode([
                'success' => true,
                'msg' => "Регистрацията бе успешна!",
                'data' => [
                    "id" => $id,
                    'auth_token' => JWT::encode($token, "photogram_security"),
                    "username" => $username_post,
                    "avatar" => $avatar,
                    "blocked" => ($blocked == 1) ? true : false,
                    "email" => $email,
                    "firstName" => $first_name,
                    "lastName" => $last_name,
                    "roles" => ($roles == 1) ? ['Admin'] : []
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['success' => false, 'msg' => "Вече съществува потребител с това потребителско име!"], JSON_UNESCAPED_UNICODE);
    }

    $stmt->free_result();
    $stmt->close();
}

$mysqli->close();