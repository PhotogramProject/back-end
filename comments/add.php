<?php
$comment_data = json_decode($_POST['comment-data']);
if (trim($comment_data->type) == 'image' || trim($comment_data->type) == 'journey') {
    $query = "INSERT INTO `comments` (`content`, `type`, `property_id`, `author`, `date_added`) VALUES (?,?,?,?,?)";
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param('ssiis', $comment_content, $comment_type, $comment_property_id, $comment_author, $comment_date_added);
        $comment_content = trim($comment_data->content);
        $comment_type = trim($comment_data->type);
        $comment_property_id = intval($comment_data->property_id);
        $comment_author = intval($comment_data->author);
        $comment_date_added = trim($comment_data->date_added);

        $stmt->execute();
        $stmt->free_result();
    }

    $query = "SELECT id FROM `comments` ORDER BY `comments`.`id` DESC LIMIT 1";
    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment_data = $result->fetch_assoc();

    $result->free_result();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'msg' => "Коментарът е добавен.",
        'data' => [
            'id' => $comment_data['id']
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'msg' => "Неуспешен опит за връзка."], JSON_UNESCAPED_UNICODE);
    exit();
}