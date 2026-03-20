document.addEventListener('DOMContentLoaded', () => {
    // State
    const state = {
        transactions: window.INITIAL_TRANSACTIONS || [],
        theme: 'dark',
        budgets: {
            food: 600,
            housing: 1500,
            transport: 400,
            utilities: 300,
            entertainment: 200,
            salary: 0,
            other: 500
        }
    };

    // DOM Elements
    const navLinks = document.querySelectorAll('.nav-links li');
    const viewSections = document.querySelectorAll('.view-section');
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = themeToggle.querySelector('i');
    const titleEl = document.getElementById('page-title');

    // Theme Toggle
    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('light-theme');
        if (document.body.classList.contains('light-theme')) {
            themeIcon.classList.replace('bx-moon', 'bx-sun');
            state.theme = 'light';
        } else {
            themeIcon.classList.replace('bx-sun', 'bx-moon');
            state.theme = 'dark';
        }
        renderCharts(); // Re-render charts for colors
    });

    // Navigation Switching
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            // Update active state
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            // Switch views
            const targetId = `view-${link.dataset.target}`;
            viewSections.forEach(section => {
                section.style.display = 'none';
                section.classList.remove('active');
            });
            const targetEl = document.getElementById(targetId);
            targetEl.style.display = 'block';
            setTimeout(() => targetEl.classList.add('active'), 10); // Trigger animation

            // Update Header
            titleEl.textContent = link.querySelector('span').textContent;
        });
    });

    // Format Currency
    const formatCurrency = (amt) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amt);

    // Update Dashboard Stats natively in JS only for transactions list formatting
    // Values are already updated via PHP
    const updateDashboard = () => {
        renderTransactions();
        renderBudgets();
        renderCharts();
    };

    // Render Recent Transactions
    const renderTransactions = () => {
        const tbodyRecent = document.getElementById('recent-transactions-body');
        const tbodyAll = document.getElementById('all-transactions-body');

        const renderRow = (t) => {
            const tr = document.createElement('tr');
            const dateStr = new Date(t.date).toLocaleDateString();
            const badgeClass = t.type === 'income' ? 'badge-success' : 'badge-danger';

            tr.innerHTML = `
                <td><strong>${t.title}</strong></td>
                <td><span style="text-transform: capitalize;">${t.category}</span></td>
                <td>${dateStr}</td>
                <td style="font-weight:600;" class="${t.type === 'income' ? 'text-success' : ''}">
                    ${t.type === 'income' ? '+' : '-'}${formatCurrency(t.amount)}
                </td>
                <td><span class="badge ${badgeClass}">Completed</span></td>
            `;
            return tr;
        };

        if (tbodyRecent) {
            tbodyRecent.innerHTML = '';
            state.transactions.slice().reverse().slice(0, 5).forEach(t => {
                tbodyRecent.appendChild(renderRow(t));
            });
        }

        if (tbodyAll) {
            tbodyAll.innerHTML = '';
            state.transactions.slice().reverse().forEach(t => {
                tbodyAll.appendChild(renderRow(t));
            });
        }
    };

    const renderBudgets = () => {
        const container = document.getElementById('budget-cards-container');
        if (!container) return;

        const expensesByCategory = {};
        Object.keys(state.budgets).forEach(k => expensesByCategory[k] = 0);

        state.transactions.filter(t => t.type === 'expense').forEach(t => {
            const cat = t.category.toLowerCase();
            expensesByCategory[cat] = (expensesByCategory[cat] || 0) + t.amount;
        });

        container.innerHTML = '';

        for (const [category, limit] of Object.entries(state.budgets)) {
            if (limit <= 0) continue; // Skip if no budget set
            const spent = expensesByCategory[category] || 0;
            const progress = Math.min((spent / limit) * 100, 100);
            const isWarning = progress >= 80;
            const isDanger = progress >= 100;

            let colorClass = 'bg-primary';
            if (isDanger) colorClass = 'bg-danger';
            else if (isWarning) colorClass = 'bg-warning';

            const card = document.createElement('div');
            card.className = 'glass-panel p-4 rounded';
            card.innerHTML = `
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <strong style="text-transform: capitalize;">${category}</strong>
                    <span>${formatCurrency(spent)} / ${formatCurrency(limit)}</span>
                </div>
                <div style="width: 100%; background: rgba(255,255,255,0.1); border-radius: 4px; height: 8px; overflow: hidden;">
                    <div class="${colorClass}" style="width: ${progress}%; height: 100%; border-radius: 4px;"></div>
                </div>
                <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--text-secondary);">
                    ${isDanger ? 'Budget Exceeded!' : isWarning ? 'Nearing Limit' : 'On Track'}
                </div>
            `;
            container.appendChild(card);
        }
    };

    // Chart Options & Instances
    let expenseChartInstance = null;
    let cashflowChartInstance = null;

    const renderCharts = () => {
        const isDark = state.theme === 'dark';
        const textColor = isDark ? '#ffffff' : '#0f172a';

        Chart.defaults.color = textColor;
        Chart.defaults.font.family = "'Inter', sans-serif";

        // Aggregate expense data
        const catMap = {};
        state.transactions.filter(t => t.type === 'expense').forEach(t => {
            catMap[t.category] = (catMap[t.category] || 0) + t.amount;
        });

        // Setup Expense Pie Chart
        const ctxPie = document.getElementById('expenseChart').getContext('2d');
        if (expenseChartInstance) expenseChartInstance.destroy();
        expenseChartInstance = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: Object.keys(catMap).map(k => k.charAt(0).toUpperCase() + k.slice(1)),
                datasets: [{
                    data: Object.values(catMap),
                    backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                cutout: '70%'
            }
        });

        // Setup Cashflow Chart (Mock Data for Months)
        const ctxLine = document.getElementById('cashflowChart').getContext('2d');
        if (cashflowChartInstance) cashflowChartInstance.destroy();
        cashflowChartInstance = new Chart(ctxLine, {
            type: 'bar',
            data: {
                labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
                datasets: [
                    {
                        label: 'Income',
                        data: [4200, 4300, 5000, 4800, 5100, 6000],
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    },
                    {
                        label: 'Expenses',
                        data: [3100, 3200, 3900, 2800, 3100, 3500],
                        backgroundColor: '#ef4444',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: - false } }
                }
            }
        });
    };

    // UI Add Transaction Modal Logic
    const btnAdd = document.getElementById('btn-add-transaction');
    const modalAdd = document.getElementById('add-transaction-modal');

    btnAdd.addEventListener('click', () => modalAdd.classList.add('active'));
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById(btn.dataset.modal).classList.remove('active');
        });
    });

    // Handle AJAX form submission for a perfect SPA web experience
    const formTransaction = document.getElementById('form-transaction');
    if (formTransaction) {
        formTransaction.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = formTransaction.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = "<i class='bx bx-loader bx-spin'></i> Saving...";

            const formData = new FormData(formTransaction);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('../backend/transaction.php?action=add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    // Fetch updated transactions
                    const res = await fetch('../backend/transaction.php?action=get');
                    const getResult = await res.json();
                    if (getResult.transactions) {
                        state.transactions = getResult.transactions;

                        // Recalculate Dashboard Stats natively in JS to avoid page reload
                        let totalInc = 0; let totalExp = 0; let totalDebt = 0;
                        state.transactions.forEach(t => {
                            if (t.type === 'income') totalInc += t.amount;
                            if (t.type === 'expense') totalExp += t.amount;
                            if (t.type === 'debt') totalDebt += t.amount;
                        });
                        const totalBal = totalInc - totalExp - totalDebt;

                        document.getElementById('total-balance').textContent = formatCurrency(totalBal);
                        document.getElementById('total-income').textContent = formatCurrency(totalInc);
                        document.getElementById('total-expenses').textContent = formatCurrency(totalExp);
                        document.getElementById('total-debt').textContent = formatCurrency(totalDebt);

                        updateDashboard();
                    }
                    formTransaction.reset();
                    modalAdd.classList.remove('active');
                } else {
                    alert('Error: ' + (result.error || 'Failed to add transaction'));
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while adding the transaction.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }

    // Chatbot Logic
    const btnToggleChat = document.getElementById('chatbot-toggle');
    const chatPanel = document.getElementById('chat-panel');
    const btnCloseChat = document.getElementById('close-chat');
    const chatInput = document.getElementById('chat-input');
    const btnSendMsg = document.getElementById('send-btn');
    const chatBody = document.getElementById('chat-messages');

    const openChat = () => { chatPanel.classList.add('open'); btnToggleChat.style.transform = 'scale(0)'; };
    const closeChat = () => { chatPanel.classList.remove('open'); btnToggleChat.style.transform = 'scale(1)'; };

    btnToggleChat.addEventListener('click', openChat);
    btnCloseChat.addEventListener('click', closeChat);

    const addBotMessage = (text) => {
        const msg = document.createElement('div');
        msg.className = 'message bot';
        msg.innerHTML = `<div class="msg-content">${text}</div><span class="msg-time">Just now</span>`;
        chatBody.appendChild(msg);
        chatBody.scrollTop = chatBody.scrollHeight;
    };

    const processUserMessage = (text) => {
        const msg = document.createElement('div');
        msg.className = 'message user';
        msg.innerHTML = `<div class="msg-content">${text}</div><span class="msg-time">Just now</span>`;
        chatBody.appendChild(msg);
        chatBody.scrollTop = chatBody.scrollHeight;
        chatInput.value = '';

        // Mock AI Response Delay
        setTimeout(() => {
            const lowerText = text.toLowerCase();
            let reply = "I'm analyzing your data to give you the best advice. Hang on!";

            let totalInc = 0; let totalExp = 0; let totalDebt = 0;
            state.transactions.forEach(t => {
                if (t.type === 'income') totalInc += t.amount;
                if (t.type === 'expense') totalExp += t.amount;
                if (t.type === 'debt') totalDebt += t.amount;
            });
            const totalBal = totalInc - totalExp - totalDebt;

            if (lowerText.includes('balance') || lowerText.includes('how much money')) {
                reply = `Your current total balance is **${formatCurrency(totalBal)}**.`;
            } else if (lowerText.includes('budget') || lowerText.includes('save') || lowerText.includes('advice')) {
                // Find highest expense category
                const expensesByCategory = {};
                state.transactions.filter(t => t.type === 'expense').forEach(t => {
                    expensesByCategory[t.category] = (expensesByCategory[t.category] || 0) + t.amount;
                });
                let highestCat = '';
                let highestAmt = 0;
                for (const [cat, amt] of Object.entries(expensesByCategory)) {
                    if (amt > highestAmt) { highestAmt = amt; highestCat = cat; }
                }

                if (highestCat) {
                    reply = `Based on your spending, your highest expense is **${highestCat}** (${formatCurrency(highestAmt)}). Reducing this by 15% could save you an extra ${formatCurrency(highestAmt * 0.15)} this month!`;
                } else {
                    reply = "You don't have enough expense data yet. Try adding some transactions to get personalized advice!";
                }
            } else if (lowerText.includes('debt') || lowerText.includes('loan')) {
                if (totalDebt > 0) {
                    reply = `You have ${formatCurrency(totalDebt)} in total debt payments recently. Did you know allocating 5% more to the principal monthly can significantly shorten the term?`;
                } else {
                    reply = "Great news! I don't see any recent debt payments. Keep it up!";
                }
            } else if (lowerText.includes('report') || lowerText.includes('score')) {
                reply = "Your health score is currently excellent! You can download the full detailed PDF report from the Reports section.";
            } else {
                reply = "I'm your FinAI assistant! Ask me about your **balance**, **budget** advice, **debt**, or **report**!";
            }

            addBotMessage(reply);
        }, 1200);
    };

    btnSendMsg.addEventListener('click', () => { if (chatInput.value.trim()) processUserMessage(chatInput.value.trim()); });
    chatInput.addEventListener('keypress', (e) => { if (e.key === 'Enter' && chatInput.value.trim()) processUserMessage(chatInput.value.trim()); });

    // Report Logic
    document.getElementById('btn-export-report').addEventListener('click', () => document.querySelector('[data-target="reports"]').click());

    document.getElementById('btn-generate-pdf').addEventListener('click', () => {
        const element = document.getElementById('report-content');
        element.innerHTML = `
            <h2>Personalized Financial Health Report</h2>
            <p>Generated on: ${new Date().toLocaleDateString()}</p>
            <hr style="margin:20px 0;">
            <div style="font-size:18px;">
                <p><strong>Total Income:</strong> ${document.getElementById('total-income').innerText}</p>
                <p><strong>Total Expenses:</strong> ${document.getElementById('total-expenses').innerText}</p>
                <p><strong>Total Debt:</strong> ${document.getElementById('total-debt').innerText}</p>
                <h3 style="color:#10b981;">Financial Health Score: ${document.querySelector('.percentage').textContent}/100</h3>
            </div>
            <div style="margin-top:20px; padding:15px; background:rgba(0,0,0,0.05); border-radius:10px;">
                <strong>AI Insight:</strong> Based on the data points, your spending habits are well within constraints. We recommend shifting surplus balance towards high-yield savings to combat inflation. 
            </div>
        `;

        const opt = {
            margin: 1,
            filename: 'finhealth_report.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            // Revert content after render
            element.innerHTML = '<h3>Financial Summary</h3><p>Report has been generated and downloaded successfully.</p>';
        });
    });

    // CSV Export
    document.getElementById('btn-export-csv').addEventListener('click', () => {
        let csvContent = "data:text/csv;charset=utf-8,Title,Category,Amount,Type\n";
        state.transactions.forEach(t => {
            csvContent += `${t.title},${t.category},${t.amount},${t.type}\n`;
        });
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "transactions_export.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Init data load from PHP
    updateDashboard();

});
