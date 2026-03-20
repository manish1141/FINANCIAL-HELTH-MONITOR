<?php
require_once 'config.php';
header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// Handle saving new transaction data into the database
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    
    $title = $input['title'] ?? '';
    $category = $input['category'] ?? '';
    $amount = floatval($input['amount'] ?? 0);
    $type = $input['type'] ?? '';
    $date = date('Y-m-d'); // Uses current date

    // Validation
    if ($title && $category && $amount > 0 && in_array($type, ['income', 'expense', 'debt'])) {
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, title, category, amount, type, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $title, $category, $amount, $type, $date])) {
            echo json_encode(['success' => true, 'message' => 'Transaction saved successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
    }
} 
// Handle fetching transactions for the dashboard
elseif ($action === 'get') {
    $stmt = $conn->prepare("SELECT id, title, category, amount, type, transaction_date as date FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($transactions as &$t) {
        $t['amount'] = floatval($t['amount']);
    }
    
    echo json_encode(['success' => true, 'transactions' => $transactions]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
