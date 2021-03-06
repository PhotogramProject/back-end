<?php
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
    while ($row = $result->fetch_assoc()) {
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