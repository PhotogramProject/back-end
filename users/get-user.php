<?php
$query = " SELECT *, (SELECT COUNT(*) FROM `journeys` WHERE author = users.id) as cnt FROM `users` WHERE username = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('s', $username);
$username = trim(str_replace("/", "", $__id));
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