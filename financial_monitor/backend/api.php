<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'get_transactions') {
    $stmt = $conn->prepare("SELECT id, title, category, amount, type, transaction_date as date FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cast amounts to numbers for JS
    foreach($transactions as &$t) {
        $t['amount'] = floatval($t['amount']);
    }
    
    echo json_encode(['transactions' => $transactions]);
} elseif ($action === 'add_transaction' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    
    $title = $input['title'] ?? '';
    $category = $input['category'] ?? '';
    $amount = floatval($input['amount'] ?? 0);
    $type = $input['type'] ?? '';
    $date = date('Y-m-d'); // Current date

    if ($title && $category && $amount > 0 && in_array($type, ['income', 'expense', 'debt'])) {
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, title, category, amount, type, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $title, $category, $amount, $type, $date])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Database error']);
        }
    } else {
        echo json_encode(['error' => 'Invalid input']);
    }
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>
