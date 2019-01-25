<?php
$query = "SELECT roles FROM `users` WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$id = $decoded_jwt->user_id;

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data["roles"] == 1) {
    $query = "SELECT * FROM `images` LIMIT 15 OFFSET ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $offset);
    $offset = intval(trim(str_replace("/", "", $__id)));
    $stmt->execute();
    $images = $stmt->get_result();

    $response = [];
    while ($row = $images->fetch_assoc()) {
        $response[] = [
            'id' => $row["id"],
            'make' => $row["make"],
            'model' => $row["model"],
            'dateTaken' => $row["date_taken"],
            'location' => [$row["lat"], $row["lon"]],
            'resolution' => [$row['resolution_w'], $row['resolution_h']],
            'flash' => $row['flash'],
            'iso' => $row["iso"],
            'focalLength' => $row["focal_length"],
            'fileName' => $row['file_name'],
            'size' => $row['size']
        ];
    }

    $images->close();
    $stmt->free_result();

    echo json_encode(['success' => true, 'data' => $response]);
} else {
    echo json_encode(['success' => false, 'msg' => 'Нямате права за да изпълните тази заявка'], JSON_UNESCAPED_UNICODE);
}