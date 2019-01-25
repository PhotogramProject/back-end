<?php
$query = "SELECT `author` FROM `journeys` WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $journey_id);
$journey_id = intval(trim(str_replace("/", "", $__id)));;

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->free_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'msg' => 'Пътешествието, което се опитвате да изтриете, вече не съществува.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$mysqli = mysqli_connect("localhost", "stomin", "1q2a3z4", "photogram");
$query = "SELECT roles FROM `users` WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$id = $decoded_jwt->user_id;

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->free_result();

if ($data["roles"] == 0 && $id != $data['author']) {
    echo json_encode(['success' => false, 'msg' => 'Нямате права за да изпълните тази заявка.'], JSON_UNESCAPED_UNICODE);
    exit();
} else {
    $query = "SELECT file_name FROM `images` WHERE journey_id= ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $journey_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->free_result();

    while ($row = $result->fetch_assoc()) {
        $query = "DELETE FROM `images` WHERE file_name = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('s', $file_name);
        $file_name = $row['file_name'];
        $stmt->execute();

        $dir = __DIR__ . "/../../uploads/users/" . $row["file_name"];
        if (is_file($dir)) {
            unlink($dir);
        }
    }

    $query = "DELETE FROM `journeys` WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $journey_id);
    $stmt->execute();

    echo json_encode(['success' => true, 'msg' => 'Успешно изтрихте това пътешествие.'], JSON_UNESCAPED_UNICODE);
    exit();
}