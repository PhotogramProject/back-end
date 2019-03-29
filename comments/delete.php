<?php
if (str_replace("/", "", $__id) == "") {
    echo json_encode(['success' => false, 'msg' => "Неуспешен опит за връзка."], JSON_UNESCAPED_UNICODE);
    exit();
}

$query = "SELECT * FROM `comments` WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$id = intval(trim(str_replace("/", "", $__id)));
$stmt->execute();
$result = $stmt->get_result();
$comment_data = $result->fetch_assoc();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'msg' => "Коментарът, която се опитвате да изтриете не съществува!"], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt->free_result();
$result->close();

$query = "SELECT roles FROM `users` WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$id = $decoded_jwt->user_id;

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->free_result();

if ($data["roles"] == 0 && $id != $comment_data['author']) {
    echo json_encode(['success' => false, 'msg' => 'Нямате права за да изпълните тази заявка.'], JSON_UNESCAPED_UNICODE);
    exit();
} else {
    $query = "DELETE FROM `comments` WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $id);
    $id = intval(trim(str_replace("/", "", $__id)));

    $stmt->execute();
    $stmt->free_result();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'msg' => "Коментарът беше успешно изтрит."
    ], JSON_UNESCAPED_UNICODE);
}