<?php

require_once '../lib/db.php';

if(isset($_GET['detailid'])) {
    
$id = $_GET['detailid'];
    
$query = "SELECT * FROM user_data WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo '<link rel="stylesheet" href="../assets/style.css">';
    echo '<div class="header"><h3>Текст письма</h3></div><div class="wrapper" style="height:100vh">';
            echo '<div class="item">';
            echo '<div class="date" style="text-align:right;backgroud:#eee;padding:5px;"><span style="padding:3px 10px">'.$row['date'] . "</span></div>";
            echo '<div class="content">'.$row['content'] . "</div>";
            echo '</div></div>';
    echo $content;
} else {
    echo "Строка с указанным id не найдена.";
}

$stmt->close();
$db->close();
} 


if(isset($_GET['chat_id'])) {
    $chat_id = $_GET['chat_id'];

$query = "SELECT u.password, d.content, d.date
FROM users u
INNER JOIN user_data d ON u.password = d.user
WHERE u.chat_id = ?
ORDER BY d.id DESC
LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="item">';
            echo '<div class="date" style="text-align:right;backgroud:#eee;padding:5px;"><span style="padding:3px 10px">'.$row['date'] . "</span></div>";
            echo '<div class="content">'.$row['content'] . "</div>";
            echo '</div>';
        }
    } else {
        echo "Нет записей для этого пользователя.";
    }

    $stmt->close();
    $db->close();
} 

?>