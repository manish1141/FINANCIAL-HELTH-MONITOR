# 🚀 FinAI - Premium Financial Health Monitor

[![GitHub license](https://img.shields.io/github/license/manish1141/FINANCIAL-HELTH-MONITOR)](https://github.com/manish1141/FINANCIAL-HELTH-MONITOR/blob/main/LICENSE)
[![GitHub stars](https://img.shields.io/github/stars/manish1141/FINANCIAL-HELTH-MONITOR)](https://github.com/manish1141/FINANCIAL-HELTH-MONITOR/stargazers)

**FinAI** is a state-of-the-art, AI-powered financial monitoring application designed with a premium glassmorphism interface. It helps users track their income, expenses, and debts while providing intelligent, personalized financial advice.

---

## ✨ Key Features

- **💎 Premium Glassmorphism UI**: A stunning, modern interface with dynamic background orbs and smooth micro-animations.
- **📊 Interactive Dashboard**: Real-time stats for Balance, Income, Expenses, and Debt with Chart.js analytics.
- **🤖 FinAI Assistant**: A built-in AI chatbot that analyzes your spending habits and gives personalized tips.
- **💰 Budget Tracking**: Set monthly limits per category and monitor progress with visual indicators.
- **📄 Exportable Reports**: Generate professional PDF reports or export your data as CSV for external use.
- **🔒 Secure Authentication**: Robust login and registration system with session management.

## 🛠️ Technology Stack

- **Frontend**: HTML5, Vanilla CSS3 (Glassmorphism), JavaScript (ES6+), Chart.js
- **Backend**: PHP 8.x, MySQL (via XAMPP/PDO)
- **AI Integration**: OpenAI GPT-3.5 API
- **Icons & Fonts**: Boxicons, Google Fonts (Inter)

## 📁 Project Structure

```bash
financial_monitor/
├── frontend/          # UI Components, CSS, and JS
│   ├── index.php      # Main Dashboard
│   ├── login.php      # Authentication
│   ├── app.js         # Frontend Logic
│   └── styles.css     # Design System
└── backend/           # Server-side Logic & API
    ├── config.php     # DB & App Configuration
    ├── api.php        # Transaction APIs
    └── chat_api.php   # AI Chatbot Logic
    └── setup_db.php   # Database Initialization
```

## 🚀 Getting Started

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (Apache & MySQL)
- PHP 8.0 or higher
- OpenAI API Key (optional, for AI features)

### Installation

1. **Clone the project**:
   ```bash
   git clone https://github.com/manish1141/FINANCIAL-HELTH-MONITOR.git
   ```
2. **Setup Database**:
   - Start Apache and MySQL in XAMPP.
   - Open your browser and visit: `http://localhost/FINANCIAL-HELTH-MONITOR/financial_monitor/backend/setup_db.php`
3. **Configure API Key**:
   - Open `backend/config.php` and replace `'YOUR_OPENAI_API_KEY'` with your actual key.
4. **Run the App**:
   - Navigate to: `http://localhost/FINANCIAL-HELTH-MONITOR/financial_monitor/frontend/login.php`

---

Developed with ❤️ by [Manish](https://github.com/manish1141)
