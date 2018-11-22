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

$key = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxNSwidXNlcm5hbWUiOiJzdG9taW4iLCJpYXQiOjE1Mzk2MDEyNTB9._QqdiCEfx4Pbd90UOdBWaOxnXTpyGmiydvz5AnoCvNY";

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

    if ($_SERVER["PATH_INFO"] == '/all') {
        $query = "SELECT * FROM journeys";
        $stmt = $mysqli->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        $response = [];
        while ($row = $result->fetch_assoc()) {
            $query = "SELECT username, avatar FROM `users` WHERE id = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('i', $user_id);
            $user_id = $row['author'];
            $stmt->execute();
            $user = $stmt->get_result();
            $user_data = $user->fetch_assoc();
            $user->close();
            $stmt->free_result();

            $query = "SELECT file_name FROM `images` WHERE `journey_id` = ? LIMIT 1";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('i', $journey_id);
            $journey_id = $row['id'];
            $stmt->execute();
            $image = $stmt->get_result();
            $file_name = ($image->fetch_assoc())['file_name'];
            $image->close();
            $stmt->free_result();

            $response[] = [
                'id' => $row["id"],
                'name' => $row['name'],
                'description' => $row['description'],
                "dateCreated" => $row['date_created'],
                'author' => $user_data['username'],
                'avatar' => $user_data['avatar'],
                'featuredImage' => $file_name
            ];
        }

        $stmt->free_result();
    } else {
        if (isset($_GET['username']) && $_GET['username'] != "") {
            $query = "SELECT id FROM `users` WHERE username = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('s', $username);
            $username = trim($_GET['username']);
            $stmt->execute();
            $user = $stmt->get_result();
            $user_data = $user->fetch_assoc();
            $user->close();
            $stmt->free_result();

            $query = "SELECT * FROM `journeys` WHERE author = ? ORDER BY `date_created` DESC LIMIT 5 OFFSET ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('ii', $user_id_select, $offset);
            $user_id_select = $user_data['id'];
            $offset = intval(trim(str_replace("/", "", $_SERVER["PATH_INFO"])));

            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->free_result();
        } else if ((isset($_GET['from']) && $_GET['from'] != "") && (isset($_GET['to']) && $_GET['to'] != "")) {
            $query = "SELECT * FROM `journeys` WHERE date_created >= ? AND date_created <= ? ORDER BY `date_created` DESC LIMIT 5 OFFSET ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('ssi', $from_select, $to_select, $offset);
            $from_select = $_GET['from'] . " 00:00:00";
            $to_select = $_GET['to'] . " 23:59:59";
            $offset = intval(trim(str_replace("/", "", $_SERVER["PATH_INFO"])));

            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->free_result();
        } else {
            $query = "SELECT * FROM `journeys` ORDER BY `date_created` DESC LIMIT 5 OFFSET ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('i', $offset);
            $offset = intval(trim(str_replace("/", "", $_SERVER["PATH_INFO"])));

            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->free_result();
        }

        $response = [];
        while ($row = $result->fetch_assoc()) {
            $query = "SELECT username, avatar FROM `users` WHERE id = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('i', $user_id);
            $user_id = $row['author'];
            $stmt->execute();
            $user = $stmt->get_result();
            $user_data = $user->fetch_assoc();
            $user->close();
            $stmt->free_result();

            $query = "SELECT file_name FROM `images` WHERE `journey_id` = ? LIMIT 1";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('i', $journey_id);
            $journey_id = $row['id'];
            $stmt->execute();
            $image = $stmt->get_result();
            $file_name = ($image->fetch_assoc())['file_name'];
            $image->close();
            $stmt->free_result();

            $response[] = [
                'id' => $row["id"],
                'name' => $row['name'],
                'description' => $row['description'],
                "dateCreated" => $row['date_created'],
                'author' => $user_data['username'],
                'avatar' => $user_data['avatar'],
                'featuredImage' => $file_name
            ];
        }
    }

    $result->close();
    $stmt->close();
    $mysqli->close();

    echo json_encode(['success' => true, 'data' => $response], JSON_UNESCAPED_UNICODE);
}