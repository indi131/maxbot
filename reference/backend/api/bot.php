<?php

require '../lib/db.php';
require '../lib/send.php';


$update = file_get_contents('php://input');
$update = json_decode($update, true);


if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'];

    
    if ($text == '/start') {
        $response = "Привет! Пожалуйста, отправь мне ваш <b>ключ доступа</b>";
        sendMessage($chat_id, $response);
    } else {
            $userUrl = $text;
    

            $stmt = $db->prepare('SELECT * FROM users WHERE password = ?');
            if (!$stmt) {
                die('Prepare failed: ' . $db->error);
            }
            $stmt->bind_param('s', $userUrl);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                sendMessage($chat_id, "Такой ключ уже зарегистрирован!");
            } else {
                $stmt = $db->prepare('INSERT INTO users (password, chat_id) VALUES (?, ?)');
                if (!$stmt) {
                    die('Prepare failed: ' . $db->error);
                }
                $stmt->bind_param('si', $userUrl, $chat_id);
                $stmt->execute();

                sendMessage($chat_id, "Ключ доступа сохранен");
            }

            $stmt->close();
            $db->close();
            
           
        }
    
}


?>