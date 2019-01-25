<?php
$query = "SELECT * FROM `journeys` WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$id = intval(trim(str_replace("/", "", $__id)));
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$query = "SELECT * FROM `images` WHERE journey_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$id = intval(trim(str_replace("/", "", $__id)));
$stmt->execute();
$result = $stmt->get_result();

$images = [];
while ($row = $result->fetch_assoc()) {
    $images[] = [
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
        'size' => $row['size'],
        'comment' => $row['comment']
    ];
}

$result->close();
$stmt->free_result();
$stmt->close();
$mysqli->close();

echo json_encode([
    'success' => true,
    'journey' => [
        'id' => $data["id"],
        'name' => $data['name'],
        'description' => $data['description'],
        'dateCreated' => $data['date_created'],
        'ratings' => array_map('intval', explode(',', $data['ratings'])),
        'totalReviewers' => $data['total_reviewers'],
    ],
    'images' => $images
], JSON_UNESCAPED_UNICODE);