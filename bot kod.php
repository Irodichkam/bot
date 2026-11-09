<?php

// Bot ma'lumotlari
$botToken = "9207194444:AAFbuZLm_iH6Cn-oaosmsuCW8gAujDgdazI";
$adminId  = "1872175086";
$apiUrl   = "https://api.telegram.org/bot{$botToken}/";

// Tort turlari (Rasmi, narxi va tavsifi bilan)
$cakes = [
    'red_velvet' => [
        'name'        => "🔴 Red Velvet",
        'price'       => "150,000 so'm",
        'photo'       => "https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?w=600",
        'description' => "Mayin biskvit va pishloqli cream-cheese krem aralashmasi."
    ],
    'medovik' => [
        'name'        => "🍯 Medovik",
        'price'       => "120,000 so'm",
        'photo'       => "https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600",
        'description' => "Tabiiy asal va sariyog'li krem bilan tayyorlangan klassik tort."
    ],
    'napoleon' => [
        'name'        => "🥐 Napoleon",
        'price'       => "130,000 so'm",
        'photo'       => "https://images.unsplash.com/photo-1535141192574-5d4897c13136?w=600",
        'description' => "Qat-qat xamir va zavarnoy krem uyg'unligi."
    ],
    'snickers' => [
        'name'        => "🥜 Snickers",
        'price'       => "160,000 so'm",
        'photo'       => "https://images.unsplash.com/photo-1606890737304-57a1ca8a5b62?w=600",
        'description' => "Shokoladli biskvit, arab yong'og'i va karamel."
    ]
];

// Telegram API ga so'rov yuborish funksiyasi (cURL orqali)
function sendRequest($method, $params = []) {
    global $apiUrl;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . $method);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Asosiy menyu tugmalarini yaratish
function getMainMenu() {
    global $cakes;
    $keyboard = [];
    foreach ($cakes as $key => $cake) {
        $keyboard[] = [['text' => $cake['name'], 'callback_data' => 'cake_' . $key]];
    }
    return json_encode(['inline_keyboard' => $keyboard]);
}

// Ortga qaytish tugmasi
function getBackMenu() {
    return json_encode([
        'inline_keyboard' => [
            [['text' => "⬅️ Menyuga qaytish", 'callback_data' => 'back_to_menu']]
        ]
    ]);
}

echo "Bot ishga tushdi... (Long Polling)\n";

$offset = 0;

// Veb-saytsiz (Server-less / Polling) cheksiz sikl
while (true) {
    $updates = sendRequest('getUpdates', [
        'offset'  => $offset,
        'timeout' => 30
    ]);

    if (!empty($updates['result'])) {
        foreach ($updates['result'] as $update) {
            $offset = $update['update_id'] + 1;

            // /start buyrug'i kelganda
            if (isset($update['message']['text'])) {
                $chatId = $update['message']['chat']['id'];
                $text   = $update['message']['text'];

                if ($text === '/start') {
                    sendRequest('sendMessage', [
                        'chat_id'      => $chatId,
                        'text'         => "Assalomu alaykum! Tortlardan birini tanlang:",
                        'reply_markup' => getMainMenu()
                    ]);
                }
            }

            // Tugma bosilganda (Callback Query)
            if (isset($update['callback_query'])) {
                $cq       = $update['callback_query'];
                $chatId   = $cq['message']['chat']['id'];
                $messageId= $cq['message']['message_id'];
                $data     = $cq['data'];

                // Tort tanlanganda rasmi va ma'lumotini yuborish
                if (strpos($data, 'cake_') === 0) {
                    $cakeKey = str_replace('cake_', '', $data);
                    if (isset($cakes[$cakeKey])) {
                        $cake = $cakes[$cakeKey];
                        
                        // Eski menyu xabarini o'chirish
                        sendRequest('deleteMessage', [
                            'chat_id'    => $chatId,
                            'message_id' => $messageId
                        ]);

                        // Rasm va ma'lumot yuborish
                        $caption = "<b>{$cake['name']}</b>\n\n"
                                 . "📝 <b>Tavsif:</b> {$cake['description']}\n"
                                 . "💰 <b>Narxi:</b> {$cake['price']}";

                        sendRequest('sendPhoto', [
                            'chat_id'      => $chatId,
                            'photo'        => $cake['photo'],
                            'caption'      => $caption,
                            'parse_mode'   => 'HTML',
                            'reply_markup' => getBackMenu()
                        ]);
                    }
                }

                // Menyuga qaytish tugmasi bosilganda
                if ($data === 'back_to_menu') {
                    sendRequest('deleteMessage', [
                        'chat_id'    => $chatId,
                        'message_id' => $messageId
                    ]);

                    sendRequest('sendMessage', [
                        'chat_id'      => $chatId,
                        'text'         => "Tortlardan birini tanlang:",
                        'reply_markup' => getMainMenu()
                    ]);
                }

                // Callback so'rovini yopish
                sendRequest('answerCallbackQuery', ['callback_query_id' => $cq['id']]);
            }
        }
    }
    sleep(1);
}