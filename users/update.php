<?php
$request = json_decode(file_get_contents("php://input"));

$query = "UPDATE `users` SET `email` = ?,`first_name` = ?,`last_name` = ?,`blocked` = ? WHERE id = ?";
$email = $request->email;
$first_name = $request->firstName;
$last_name = $request->lastName;
$blocked = ($request->blocked == true) ? 1 : 0;
$id = $request->id;

$stmt = $mysqli->prepare($query);
$stmt->bind_param('sssii', $email, $first_name, $last_name, $blocked, $id);
$stmt->execute();
$stmt->free_result();
$stmt->close();

$query = "SELECT * FROM `users` WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
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
        'username' => $data['username'],
        'avatar' => $data["avatar"]
    ],
], JSON_UNESCAPED_UNICODE);