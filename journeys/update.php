<?php
$journey_id = intval(trim(str_replace("/", "", $__id)));
$journey_data = json_decode($_POST['journey-data']);
$query = "UPDATE `journeys` SET `name` = ?,`description` = ?, `ratings` = ?, `total_reviewers` = ? WHERE id = ?";
$name = $journey_data->name;
$description = $journey_data->description;
$ratings = implode(",", $journey_data->ratings);
$total_reviewers = $journey_data->totalReviewers;
$id = $journey_id;

$stmt = $mysqli->prepare($query);
$stmt->bind_param('ssssi', $name, $description, $ratings, $total_reviewers, $id);
$stmt->execute();
$stmt->free_result();
$stmt->close();

$query = "UPDATE `images` SET `comment`= ? WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('si', $image_comment, $id);

$images_cnt = count($_POST['imagesForUpdate']);
for ($i = 0; $i < $images_cnt; $i++) {
    $image_data = json_decode($_POST['imagesForUpdate'][$i]);
    $image_comment = (isset($image_data->comment)) ? $image_data->comment : "";
    $id = (isset($image_data->id)) ? intval($image_data->id) : 0;

    $stmt->execute();
}
$stmt->free_result();

echo json_encode([
    'success' => true,
    'msg' => 'Успешно редактирахте пътешествието.',
], JSON_UNESCAPED_UNICODE);
