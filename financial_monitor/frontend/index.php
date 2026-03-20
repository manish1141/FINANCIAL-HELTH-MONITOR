<?php
require_once '../backend/config.php';
require_login();

$user_id = $_SESSION['user_id'];

// Handle Add Transaction natively in PHP instead of API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_transaction') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $type = trim($_POST['type'] ?? '');
    $date = date('Y-m-d');

    if ($title && $category && $amount > 0 && in_array($type, ['income', 'expense', 'debt'])) {
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, title, category, amount, type, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $category, $amount, $type, $date]);
        
        // Redirect to prevent form resubmission
        header("Location: index.php");
        exit;
    }
}

// Fetch Transactions
$stmt = $conn->prepare("SELECT id, title, category, amount, type, transaction_date as date FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats for PHP rendering
$total_income = 0;
$total_expenses = 0;
$total_debt = 0;

foreach ($transactions as &$t) {
    $t['amount'] = floatval($t['amount']);
    if ($t['type'] === 'income') $total_income += $t['amount'];
    if ($t['type'] === 'expense') $total_expenses += $t['amount'];
    if ($t['type'] === 'debt') $total_debt += $t['amount'];
}
$total_balance = $total_income - $total_expenses - $total_debt;

// Health Score
$score = 100;
if ($total_income == 0) {
    $score = 0;
} else {
    $expenseRatio = (($total_expenses + $total_debt) / $total_income) * 100;
    $score -= $expenseRatio * 0.8;
    if ($score < 0) $score = 0;
    if ($score > 100) $score = 100;
}
$score = round($score);
$health_status = 'Needs Attention';
$health_class = 'text-danger';
if($score > 80) { $health_status = 'Excellent'; $health_class = 'text-success'; }
elseif($score > 50) { $health_status = 'Good'; $health_class = 'text-warning'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinAI - Premium Financial Monitor</title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- html2pdf.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles.css">
</head>
<body class="dark-theme">
    <!-- Dynamic Background Orbs -->
    <div class="bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <div class="app-container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar glass-panel">
            <div class="logo">
                <i class='bx bx-cube-alt'></i>
                <span>Financial Health AI</span>
            </div>
            
            <ul class="nav-links">
                <li class="active" data-target="dashboard">
                    <i class='bx bx-grid-alt'></i>
                    <span>Dashboard</span>
                </li>
                <li data-target="transactions">
                    <i class='bx bx-transfer'></i>
                    <span>Transactions</span>
                </li>
                <li data-target="budget">
                    <i class='bx bx-wallet'></i>
                    <span>Budgets</span>
                </li>
                <li data-target="reports">
                    <i class='bx bx-bar-chart-alt-2'></i>
                    <span>Reports</span>
                </li>
            </ul>

            <div class="sidebar-bottom">
                <div class="financial-health-mini">
                    <div class="score-ring-mini">
                        <svg viewBox="0 0 36 36" class="circular-chart blue">
                          <path class="circle-bg"
                            d="M18 2.0845
                              a 15.9155 15.9155 0 0 1 0 31.831
                              a 15.9155 15.9155 0 0 1 0 -31.831"
                          />
                          <path class="circle"
                            stroke-dasharray="85, 100"
                            d="M18 2.0845
                              a 15.9155 15.9155 0 0 1 0 31.831
                              a 15.9155 15.9155 0 0 1 0 -31.831"
                          />
                            <text x="18" y="20.35" class="percentage"><?= $score ?></text>
                        </svg>
                    </div>
                    <div class="health-info">
                        <span class="label">Health Score</span>
                        <span class="status <?= $health_class ?>"><?= $health_status ?></span>
                    </div>
                </div>
                <div class="user-profile">
                    <div class="avatar">
                        <!-- Removed external Avatar API -->
                        <div style="width:100%; height:100%; border-radius:50%; background:var(--accent-primary); display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:1.2rem;">
                            <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                        </div>
                    </div>
                    <div class="user-info">
                        <span class="name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                        <span class="plan">Pro Member</span>
                    </div>
                    <a href="../backend/logout.php" style="color:var(--text-secondary);"><i class='bx bx-log-out'></i></a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content" id="main-content">
            <!-- Header -->
            <header>
                <div class="header-title">
                    <h1 id="page-title">Dashboard</h1>
                    <p id="page-subtitle">Welcome back! Here's your financial overview.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-outline" id="btn-export-report">
                        <i class='bx bxs-file-pdf'></i> Export Report
                    </button>
                    <button class="btn btn-primary" id="btn-add-transaction">
                        <i class='bx bx-plus'></i> Add Entry
                    </button>
                    <button class="btn btn-danger" id="btn-delete-all" style="background: var(--accent-danger); border: none; color: white;">
                        <i class='bx bx-trash'></i> Delete All
                    </button>
                    <div class="theme-toggle" id="theme-toggle">
                        <i class='bx bx-moon'></i>
                    </div>
                </div>
            </header>

            <!-- Dashboard View -->
            <div class="view-section active" id="view-dashboard">
                
                <!-- Quick Stats -->
                <div class="stats-grid">
                    <div class="stat-card glass-panel">
                        <div class="stat-header">
                            <span>Total Balance</span>
                            <div class="stat-icon bg-primary">
                                <i class='bx bx-wallet-alt'></i>
                            </div>
                        </div>
                        <h2 id="total-balance">₹<?= number_format($total_balance, 2) ?></h2>
                        <div class="stat-trend positive">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span>Updated just now</span>
                        </div>
                    </div>
                    <div class="stat-card glass-panel">
                        <div class="stat-header">
                            <span>Monthly Income</span>
                            <div class="stat-icon bg-success">
                                <i class='bx bx-trending-up'></i>
                            </div>
                        </div>
                        <h2 id="total-income">₹<?= number_format($total_income, 2) ?></h2>
                        <div class="stat-trend positive">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span>Updated just now</span>
                        </div>
                    </div>
                    <div class="stat-card glass-panel">
                        <div class="stat-header">
                            <span>Monthly Expenses</span>
                            <div class="stat-icon bg-danger">
                                <i class='bx bx-trending-down'></i>
                            </div>
                        </div>
                        <h2 id="total-expenses">₹<?= number_format($total_expenses, 2) ?></h2>
                        <div class="stat-trend negative">
                            <i class='bx bx-down-arrow-alt'></i>
                            <span>Updated just now</span>
                        </div>
                    </div>
                    <div class="stat-card glass-panel highlight-border">
                        <div class="stat-header">
                            <span>Total Debt</span>
                            <div class="stat-icon bg-warning">
                                <i class='bx bx-credit-card-alt'></i>
                            </div>
                        </div>
                        <h2 id="total-debt">₹<?= number_format($total_debt, 2) ?></h2>
                        <div class="stat-trend positive">
                            <i class='bx bx-down-arrow-alt'></i>
                            <span>Updated just now</span>
                        </div>
                    </div>
                </div>

                <!-- Charts Area -->
                <div class="charts-grid mt-4">
                    <div class="chart-container glass-panel lg-span">
                        <div class="chart-header">
                            <h3>Analytics Overview</h3>
                            <select class="glass-select">
                                <option>Last 6 Months</option>
                                <option>This Year</option>
                            </select>
                        </div>
                        <div class="canvas-wrapper">
                            <canvas id="cashflowChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-container glass-panel">
                        <div class="chart-header">
                            <h3>Expense Breakdown</h3>
                        </div>
                        <div class="canvas-wrapper pie-wrapper">
                            <canvas id="expenseChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="transactions-section glass-panel mt-4">
                    <div class="section-header">
                        <h3>Recent Transactions</h3>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="recent-transactions-body">
                                <!-- JS Injected Content -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Other Views (Transactions, Budget, Reports) would go here, hidden by default -->
            <div class="view-section" id="view-transactions" style="display: none;">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>All Transactions</h2>
                    <button class="btn btn-danger" id="btn-delete-all-transactions" style="background: var(--accent-danger); border: none; color: white; padding: 10px 20px; border-radius: 8px;">
                        <i class='bx bx-trash'></i> Delete All Transactions
                    </button>
                </div>
                <div class="transactions-section glass-panel mt-4">
                    <div class="table-responsive">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="all-transactions-body">
                                <!-- JS Injected Content -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="view-section" id="view-budget" style="display: none;">
                <div class="section-header">
                    <h2>Budget Planning</h2>
                </div>
                <div class="budget-grid mt-4" id="budget-cards-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <!-- JS Injected Content for Budget Cards -->
                </div>
            </div>
            
            <div class="view-section" id="view-reports" style="display: none;">
                <div class="reports-container glass-panel">
                    <h2>Financial Reports</h2>
                    <p>Generate detailed reports to analyze your financial trajectory.</p>
                    <div class="report-actions mt-4">
                        <button class="btn btn-primary" id="btn-generate-pdf">
                            <i class='bx bxs-file-pdf'></i> Download PDF Report
                        </button>
                        <button class="btn btn-outline" id="btn-export-csv">
                            <i class='bx bxs-file-export'></i> Export as CSV
                        </button>
                        <button class="btn btn-outline" id="btn-export-word">
                            <i class='bx bx-file-blank'></i> Export to Word
                        </button>
                    </div>
                    <div class="preview-report mt-4" id="report-content">
                        <!-- Content to be captured for PDF will be rendered here -->
                        <h3>Financial Summary</h3>
                        <div class="report-stats"></div>
                        <p class="report-advice">Fetching AI Advice...</p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- AI Chatbot Floating Widget -->
    <div class="chatbot-widget">
        <button class="chatbot-toggle shadow-glow" id="chatbot-toggle">
            <i class='bx bx-bot'></i>
            <span class="notification-dot"></span>
        </button>
        <div class="chat-panel glass-panel" id="chat-panel">
            <div class="chat-header">
                <div class="bot-info">
                    <div class="bot-avatar">
                        <i class='bx bx-bot'></i>
                    </div>
                    <div>
                        <h4>FinAI Assistant</h4>
                        <span class="online-status">Online</span>
                    </div>
                </div>
                <button class="close-chat" id="close-chat">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="chat-body" id="chat-messages">
                <div class="message bot">
                    <div class="msg-content">
                        Hello! I'm your FinAI Assistant. Based on your recent dashboard data, your savings rate has improved by 4%. How can I help you today?
                    </div>
                    <span class="msg-time">Just now</span>
                </div>
            </div>
            <div class="chat-footer">
                <input type="text" id="chat-input" placeholder="Ask about your finances...">
                <button id="send-btn" class="btn-primary-icon">
                    <i class='bx bx-send'></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Add Transaction Modal -->
    <div class="modal-overlay" id="add-transaction-modal">
        <div class="modal glass-panel">
            <div class="modal-header">
                <h3>Add New Transaction</h3>
                <i class='bx bx-x close-modal' data-modal="add-transaction-modal"></i>
            </div>
            <div class="modal-body">
                <form id="form-transaction" method="POST" action="index.php">
                    <input type="hidden" name="action" value="add_transaction">
                    <div class="form-group">
                        <label>Type</label>
                        <div class="radio-group row">
                            <label><input type="radio" name="type" value="income" checked> <span>Income</span></label>
                            <label><input type="radio" name="type" value="expense"> <span>Expense</span></label>
                            <label><input type="radio" name="type" value="debt"> <span>Debt Repayment</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <div class="input-with-icon">
                            <i class='bx bx-rupee'></i>
                            <input type="number" name="amount" id="t-amount" required placeholder="0.00" step="0.01">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="title" id="t-desc" required placeholder="e.g. Salary, Groceries">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="t-category" class="glass-select">
                            <option value="salary">Salary</option>
                            <option value="food">Food & Dining</option>
                            <option value="housing">Housing</option>
                            <option value="transport">Transportation</option>
                            <option value="utilities">Utilities</option>
                            <option value="entertainment">Entertainment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-actions mt-4">
                        <button type="button" class="btn btn-outline close-modal" data-modal="add-transaction-modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Transaction Modal -->
    <div class="modal-overlay" id="edit-transaction-modal">
        <div class="modal glass-panel">
            <div class="modal-header">
                <h3>Edit Transaction</h3>
                <i class='bx bx-x close-modal' data-modal="edit-transaction-modal"></i>
            </div>
            <div class="modal-body">
                <form id="form-edit-transaction">
                    <input type="hidden" name="id" id="edit-t-id">
                    <div class="form-group">
                        <label>Type</label>
                        <div class="radio-group row">
                            <label><input type="radio" name="type" id="edit-type-income" value="income"> <span>Income</span></label>
                            <label><input type="radio" name="type" id="edit-type-expense" value="expense"> <span>Expense</span></label>
                            <label><input type="radio" name="type" id="edit-type-debt" value="debt"> <span>Debt Repayment</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <div class="input-with-icon">
                            <i class='bx bx-rupee'></i>
                            <input type="number" name="amount" id="edit-t-amount" required placeholder="0.00" step="0.01">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="title" id="edit-t-desc" required placeholder="e.g. Salary, Groceries">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="edit-t-category" class="glass-select">
                            <option value="salary">Salary</option>
                            <option value="food">Food & Dining</option>
                            <option value="housing">Housing</option>
                            <option value="transport">Transportation</option>
                            <option value="utilities">Utilities</option>
                            <option value="entertainment">Entertainment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-actions mt-4">
                        <button type="button" class="btn btn-outline close-modal" data-modal="edit-transaction-modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pass PHP data to JS -->
    <script>
        window.INITIAL_TRANSACTIONS = <?= json_encode($transactions) ?>;
    </script>
    <!-- Scripts -->
    <script src="app.js"></script>
</body>
</html>
