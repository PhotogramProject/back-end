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
    $stmt->bind_param('s', $id);
    $id = $decoded_jwt->user_id;

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->free_result();

    if ($data["roles"] == 1) {
        $user_id = intval(trim(str_replace("/", "", $_SERVER["PATH_INFO"])));

        $query = "SELECT username, avatar FROM `users` WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $id);
        $id = $user_id;
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->free_result();

        if ($result->num_rows == 0) {
            echo json_encode(['success' => false, 'msg' => "Потребителят, който се опитвате да изтриете не съществува!"], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $result->close();

        $dir = __DIR__ . "/../uploads/avatars/" . $data["avatar"];
        if (is_file($dir) && $data['avatar'] != 'default-avatar.png') {
            unlink($dir);
        }

        $dir = __DIR__ . "/../uploads/users/" . $data["username"];
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir")
                        rmdir($dir . "/" . $object);
                    else unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }

        $query = "SELECT id FROM `journeys` WHERE author = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $author);
        $author = $user_id;
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->free_result();

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $query = "DELETE FROM `images` WHERE journey_id = ?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('i', $journey_id);
                $journey_id = $row['id'];
                $stmt->execute();

                $query = "DELETE FROM `journeys` WHERE id = ?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('i', $journey_id);
                $journey_id = $row['id'];
                $stmt->execute();
            }
        }

        $result->close();

        $query = "DELETE FROM `users` WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $id);
        $id = $user_id;
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'msg' => "Потребителят беше успешно изтрит."
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Нямате права за да изпълните тази заявка'], JSON_UNESCAPED_UNICODE);
    }
}