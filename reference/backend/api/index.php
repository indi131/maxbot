<?php


require '../lib/db.php';
require '../lib/send.php';

// Получение данных из входящего потока
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Подготовка запроса для вставки данных
$query = "INSERT INTO user_data (user, content, date) VALUES (?, ?, NOW())";
$stmt = $db->prepare($query);

// Проверка наличия ошибок при подготовке запроса
if (!$stmt) {
    http_response_code(500);
    echo "Ошибка подготовки запроса: " . $db->error;
    exit;
}

// Привязываем параметры к выражению запроса
$stmt->bind_param("ss", $data['password'], $data['data']);

// Выполняем запрос
if ($stmt->execute()) {
    $lastInsertedId = $db->insert_id;
    $keyboard = [
        'inline_keyboard' => [
            [
                [
                    'text' => 'Открыть',
                    'web_app' => [
                        'url' => 'https://unicode24bot.ru/api/details.php?detailid=' . $lastInsertedId
                    ]
                ]
            ]
        ]
    ];

    http_response_code(200);
    $ch = get_chat_id_by_password($data['password'],$db);
    sendMessage($ch,"<b>Новая заявка на сайте!</b>\nНажмите кнопку что бы посмотреть",$keyboard);
    
   // sendMessage('2094784490',"<b>Новая заявка на сайте!</b>\nНажмите кнопку что бы посмотреть",$keyboard);
    
} else {
    http_response_code(500);
    echo "Ошибка записи данных: " . $stmt->error;
}

// Завершение работы с запросом и базой данных
$stmt->close();
$db->close();



function get_chat_id_by_password($password,$db) {
    $sql = "SELECT chat_id FROM users WHERE password = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $chat_id = $row['chat_id'];
    } else {
        $chat_id = null;
    }

    $stmt->close();
    return $chat_id;
}

?>