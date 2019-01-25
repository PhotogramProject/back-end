<?php
$query = "SELECT * FROM `images`";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$images = $stmt->get_result();

$response = [];
while ($row = $images->fetch_assoc()) {
    $response[] = [
        'journeyId' => $row["journey_id"],
        'location' => [$row["lat"], $row["lon"]],
        'fileName' => $row['file_name'],
        'make' => $row["make"],
        'model' => $row["model"],
        'comment' => $row['comment']
    ];
}

$images->close();
$stmt->free_result();

echo json_encode(['success' => true, 'data' => $response]);