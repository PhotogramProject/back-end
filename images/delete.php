<?php
$query = "SELECT file_name FROM `images` WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$id = intval(trim(str_replace("/", "", $__id)));
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->free_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'msg' => "Снимката, която се опитвате да изтриете не съществува!"], JSON_UNESCAPED_UNICODE);
    exit();
}
$result->close();

$dir = __DIR__ . "/../../uploads/users/" . $data["file_name"];
if (is_file($dir)) {
    unlink($dir);
}

$query = "DELETE FROM `images` WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$id = intval(trim(str_replace("/", "", $__id)));
$stmt->execute();
$stmt->free_result();

$stmt->close();

echo json_encode([
    'success' => true,
    'msg' => "Снимката беше успешно изтритa."
], JSON_UNESCAPED_UNICODE);