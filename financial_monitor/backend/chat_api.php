<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'history') {
    // Fetch chat history
    $stmt = $conn->prepare("SELECT message as text, sender as type, created_at FROM chat_messages WHERE user_id = ? ORDER BY id ASC");
    $stmt->execute([$user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Map to frontend expected format
    $formatted_messages = [];
    foreach ($messages as $msg) {
        $formatted_messages[] = [
            'text' => $msg['text'],
            'type' => $msg['type'],
            'time' => date('h:i A', strtotime($msg['created_at']))
        ];
    }
    
    echo json_encode(['messages' => $formatted_messages]);
} elseif ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_message = $input['message'] ?? '';
    
    if (empty($user_message)) {
        echo json_encode(['error' => 'Empty message']);
        exit;
    }
    
    // 1. Save user message to database
    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, sender) VALUES (?, ?, 'user')");
    $stmt->execute([$user_id, $user_message]);
    
    // 2. Fetch recent context (optional, let's fetch last 5 messages for context)
    $stmt = $conn->prepare("SELECT message, sender FROM chat_messages WHERE user_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // Build messages array for OpenAI
    $messages_payload = [
        [
            "role" => "system",
            "content" => "You are FinAI, a helpful, intelligent, and concise financial assistant. You help the user manage their budget, track expenses, and reduce debt. If asked about their data, tell them you don't have access to their live database unless they provide explicit details. Keep responses short and helpful."
        ]
    ];
    
    foreach ($recent_messages as $msg) {
        $messages_payload[] = [
            "role" => $msg['sender'] === 'user' ? "user" : "assistant",
            "content" => $msg['message']
        ];
    }
    
    // We already added the latest message in the recent_messages query, so no need to append it again.
    
    // 3. Call OpenAI API
    $bot_reply = "I'm sorry, I couldn't connect to OpenAI. Please check the API key configuration.";
    
    if (!empty($OPENAI_API_KEY) && $OPENAI_API_KEY !== 'YOUR_OPENAI_API_KEY') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $OPENAI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages_payload,
            'max_tokens' => 150,
            'temperature' => 0.7
        ]));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            $data = json_decode($response, true);
            if (isset($data['choices'][0]['message']['content'])) {
                $bot_reply = trim($data['choices'][0]['message']['content']);
            }
        } else {
            // Include error details for debugging
            $error_data = json_decode($response, true);
            if (isset($error_data['error']['message'])) {
                $bot_reply = "OpenAI API Error: " . $error_data['error']['message'];
            }
        }
    } else {
        $bot_reply = "API key not configured. I am running in offline mode. Please add your OpenAI API key in `config.php` to enable real AI responses. You asked: " . htmlspecialchars($user_message);
    }
    
    // 4. Save bot message to database
    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, sender) VALUES (?, ?, 'bot')");
    $stmt->execute([$user_id, $bot_reply]);
    
    // 5. Return bot message
    echo json_encode([
        'success' => true,
        'reply' => $bot_reply,
        'time' => date('h:i A')
    ]);

} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>
