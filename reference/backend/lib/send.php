<?php



function sendMessage($chat_id, $text,$keyboard = null, $bot_token = '6805749802:AAGTP42r_D2n-Clk6BOdNcL_YTSWX8Oo7fg') {
    $url = "https://api.telegram.org/bot$bot_token/sendMessage";
    $data = array(
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'html'
    );
    
    if ($keyboard !== null) {
        $data['reply_markup'] = json_encode($keyboard);
    }
    
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
}


?>