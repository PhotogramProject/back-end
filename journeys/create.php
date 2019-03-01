<?php
$images_cnt = count($_FILES);
if (!isset($_POST['journey-data'])) {
    echo json_encode(['success' => false, 'msg' => "Неуспешен опит за връзка."], JSON_UNESCAPED_UNICODE);
    exit();
}

$query = "SELECT blocked FROM `users` WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$id = $decoded_jwt->user_id;
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data["blocked"] == 1) {
    echo json_encode(['success' => false, 'msg' => "Профилът е блокиран!"], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt->free_result();
$stmt->close();

$journey_data = json_decode($_POST['journey-data']);
$journey_name = $journey_data->name;
$journey_description = $journey_data->description;

$query = "INSERT INTO `journeys`(`name`, `description`, `author`, `ratings`, `total_reviewers`, `date_created`) VALUES (?,?,?,\"0,0,0,0,0\",0,NOW())";
if ($stmt = $mysqli->prepare($query)) {
    $stmt->bind_param('ssi', $journey_name, $journey_description, $id);
    $stmt->execute();
    $stmt->free_result();
}

$query = "SELECT id FROM `journeys` ORDER BY date_created DESC LIMIT 1";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($journey_id);
$stmt->fetch();
$stmt->free_result();

echo json_encode([
    'success' => true,
    'msg' => "Успешно създадохте пътешествието $journey_name.",
    'data' => [
        'journeyId' => $journey_id
    ]
], JSON_UNESCAPED_UNICODE);