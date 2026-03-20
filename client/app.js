/* app.js - Main Application Logic */

document.addEventListener('DOMContentLoaded', () => {
    console.log('FinAI initialized');

    // Handle View Switching
    const navLinks = document.querySelectorAll('.nav-links li');
    const sections = document.querySelectorAll('.view-section');

    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            const target = link.dataset.target;
            
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            sections.forEach(s => {
                if (s.id === `view-${target}`) {
                    s.style.display = 'block';
                    s.classList.add('active');
                } else {
                    s.style.display = 'none';
                    s.classList.remove('active');
                }
            });

            document.getElementById('page-title').textContent = target.charAt(0).toUpperCase() + target.slice(1);
        });
    });

    // Handle Modals
    const modalBtn = document.getElementById('btn-add-transaction');
    const modal = document.getElementById('add-transaction-modal');
    const closeBtns = document.querySelectorAll('.close-modal');

    if (modalBtn) {
        modalBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
        });
    }

    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    });

    // Chatbot Toggle
    const chatbotToggle = document.getElementById('chatbot-toggle');
    const chatPanel = document.getElementById('chat-panel');
    const closeChat = document.getElementById('close-chat');

    if (chatbotToggle) {
        chatbotToggle.addEventListener('click', () => {
            chatPanel.classList.toggle('active');
        });
    }

    if (closeChat) {
        closeChat.addEventListener('click', () => {
            chatPanel.classList.remove('active');
        });
    }

    // Initialize Charts if data exists
    if (window.INITIAL_TRANSACTIONS) {
        initCharts(window.INITIAL_TRANSACTIONS);
        renderTransactions(window.INITIAL_TRANSACTIONS);
    }
});

function initCharts(data) {
    const ctx = document.getElementById('cashflowChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Cash Flow',
                data: [12, 19, 3, 5, 2, 3],
                borderColor: '#38bdf8',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    const pieCtx = document.getElementById('expenseChart');
    if (pieCtx) {
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Food', 'Rent', 'Travel', 'Others'],
                datasets: [{
                    data: [30, 40, 10, 20],
                    backgroundColor: ['#38bdf8', '#818cf8', '#f59e0b', '#ef4444']
                }]
            }
        });
    }
}

function renderTransactions(data) {
    const body = document.getElementById('recent-transactions-body');
    if (!body) return;

    body.innerHTML = data.map(t => `
        <tr>
            <td>${t.title}</td>
            <td>${t.category}</td>
            <td>${t.date}</td>
            <td class="${t.type === 'expense' ? 'text-danger' : 'text-success'}">
                ${t.type === 'expense' ? '-' : '+'}₹${t.amount}
            </td>
            <td><span class="status">Completed</span></td>
        </tr>
    `).join('');
}
