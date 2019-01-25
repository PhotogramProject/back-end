<?php
$query = "SELECT * FROM `users` WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$id = $decoded_jwt->user_id;

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$result->close();
$stmt->free_result();
$stmt->close();
$mysqli->close();

echo json_encode([
    'success' => true,
    'data' => [
        'id' => $data["id"],
        'blocked' => ($data["blocked"] == 1) ? true : false,
        'email' => $data["email"],
        'firstName' => $data["first_name"],
        'lastName' => $data["last_name"],
        'roles' => ($data["roles"] == 1) ? ['Admin'] : [],
        'username' => $data["id"],
        'avatar' => $data["avatar"]
    ]
], JSON_UNESCAPED_UNICODE);