<?php
$query = "SELECT file_name FROM `images` WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$id = intval(trim(str_replace("/", "", $__id)));
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'msg' => "Снимката, която се опитвате да изтриете не съществува!"], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt->free_result();
$result->close();

$dir = __DIR__ . "/../../uploads/images/" . $data["file_name"];
$last_ch = strrpos($dir, '/', -1);
$folder = substr($dir, 0, $last_ch);
if (is_file($dir . "_o.jpg")) {
    unlink($dir . "_o.jpg");
    unlink($dir . "_s.jpg");
    unlink($dir . "_m.jpg");
    unlink($dir . "_l.jpg");
}
rmdir($folder);

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