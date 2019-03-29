<?php
if (!isset($_GET['type']) && ($_GET['type'] != 'journey' || $_GET['type'] != 'image')) {
    echo json_encode(['success' => false, 'msg' => "Неуспешен опит за връзка."], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!isset($_GET['skip'])) {
    echo json_encode(['success' => false, 'msg' => "Неуспешен опит за връзка."], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!isset($_GET['property'])) {
    echo json_encode(['success' => false, 'msg' => "Неуспешен опит за връзка."], JSON_UNESCAPED_UNICODE);
    exit();
}

$query = "SELECT * FROM `comments` WHERE `type` = ? AND `property_id` = ? ORDER BY `date_added` DESC LIMIT 8 OFFSET ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('sii', $type, $property, $offset);
$type = trim($_GET['type']);
$property = intval($_GET['property']);
$offset = intval($_GET['skip']);

$stmt->execute();
$result = $stmt->get_result();
$stmt->free_result();

$response = [];
while ($row = $result->fetch_assoc()) {
    $query = "SELECT username, avatar, first_name, last_name FROM `users` WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $user_id);
    $user_id = $row['author'];
    $stmt->execute();
    $user = $stmt->get_result();
    $user_data = $user->fetch_assoc();
    $user->close();
    $stmt->free_result();

    $response[] = [
        'id' => $row["id"],
        'content' => $row['content'],
        "dateAdded" => $row['date_added'],
        'username' => $user_data['username'],
        'author' => $user_data['first_name'] . ' ' . $user_data['last_name'],
        'avatar' => $user_data['avatar'],
    ];
}

echo json_encode(['success' => true, 'data' => $response]);