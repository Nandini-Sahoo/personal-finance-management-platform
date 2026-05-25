# 💰 Personal Finance Management Platform

## 📋 Table of Contents
- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation Guide](#installation-guide)
- [Database Setup](#database-setup)
- [Project Structure](#project-structure)
- [Usage Guide](#usage-guide)
- [API Endpoints](#api-endpoints)
- [ER Diagram](#er-diagram)
- [Troubleshooting](#troubleshooting)
- [Security Features](#security-features)
- [Screenshots](#screenshots)
- [Future Enhancements](#future-enhancements)

## 📌 Overview

The **Personal Finance Management Platform** is a comprehensive web-based application designed to help individuals track their income, expenses, manage budgets, and generate financial reports. The platform provides an intuitive interface for monitoring personal finances, setting budget goals, and gaining insights into spending patterns.

## ✨ Features

### Core Features
- **User Authentication**: Secure registration and login system
- **Income Tracking**: Record and categorize all income sources
- **Expense Management**: Track daily expenses with categories and payment methods
- **Budget Planning**: Set monthly budgets for different expense categories
- **Financial Reports**: Generate detailed reports with charts and analytics
- **Data Export**: Export transactions to CSV and PDF formats

### Advanced Features
- **Interactive Charts**: Visual representation of spending patterns using Chart.js
- **Category-wise Analysis**: Breakdown of expenses by category
- **Monthly Comparison**: Compare spending between different months
- **Search & Filter**: Advanced filtering options for transactions
- **Pagination**: Efficient handling of large transaction lists
- **Responsive Design**: Mobile-friendly interface with burger menu

## 🧰 Technology Stack

### Frontend
- **HTML5** - Structure and content
- **CSS3** - Styling and animations
- **JavaScript** - Client-side interactivity
- **Bootstrap 5** - Responsive framework
- **jQuery** - DOM manipulation and AJAX
- **Chart.js** - Data visualization
- **Font Awesome** - Icons

### Backend
- **PHP 7.4+** - Server-side logic
- **MySQL** - Database management
- **PDO/MySQLi** - Database connectivity

### Libraries & Tools
- **html2pdf.js** - PDF generation
- **Bootstrap Icons** - Additional icons
- **Google Fonts** - Typography

## 🛠️ System Requirements

### Server Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP/MAMP (for local development)

### Client Requirements
- Modern web browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- Internet connection (for CDN resources)

## ⚙️ Installation Guide

### Step 1: Download and Install XAMPP
1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP in default location (C:\xampp)

### Step 2: Set Up the Project
1. Navigate to XAMPP's htdocs folder:
   ```
   C:\xampp\htdocs\
   ```
2. Create project folder:
   ```
   personal-finance-management-platform
   ```
3. Copy all project files to this folder

### Step 3: Start XAMPP Services
1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service
4. Ensure both services show "Running" status

### Step 4: Database Setup
1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click on "New" to create database
3. Database name: `personal_finance_db`
4. Click "Create"
5. Import the database schema:
   - Click on "Import" tab
   - Choose file: `database/schema.sql`
   - Click "Go"

### Step 5: Configure Database Connection
Update database credentials in `backend/config/dbcon.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'personal_finance_db');
```

### Step 6: Access the Application
Open browser and navigate to:
```
https://localhost/personal-finance-management-platform/frontend/pages/user/landing.php
```

## 🗄️ Database Setup

### Database Schema Structure

```sql
CREATE DATABASE IF NOT EXISTS personal_finance_db;
USE personal_finance_db;

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone_no VARCHAR(15),
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL,
    category_type ENUM('income', 'expense') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Adding unique constraint to prevent duplicate category names for same type
    UNIQUE KEY unique_category_type (category_name, category_type)
);

CREATE TABLE expenses (
    expense_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL CHECK (amount > 0),
    expense_date DATE NOT NULL,
    notes TEXT,
    payment_method VARCHAR(20) DEFAULT 'Cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT,
    
    -- Indexes for better performance
    INDEX idx_user_date (user_id, expense_date),
    INDEX idx_category (category_id),
    INDEX idx_payment_method (payment_method)
);

CREATE TABLE income (
    income_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL CHECK (amount > 0),
    income_date DATE NOT NULL,
    source VARCHAR(100),
    payment_method VARCHAR(20) DEFAULT 'Cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT,
    
    -- Indexes for better performance
    INDEX idx_user_date (user_id, income_date),
    INDEX idx_category (category_id),
    INDEX idx_payment_method (payment_method)
);

CREATE TABLE budget (
    budget_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    target_amount DECIMAL(10,2) NOT NULL CHECK (target_amount > 0),
    budget_month DATE NOT NULL, -- Store first day of month, e.g., '2024-03-01'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
    
    -- Ensure one budget per category per month per user
    UNIQUE KEY unique_user_category_month (user_id, category_id, budget_month),
    
    -- Index for performance
    INDEX idx_user_month (user_id, budget_month)
);

-- Insert Default Expense Categories
INSERT INTO categories (category_name, category_type) VALUES
('Food & Dining', 'expense'),
('Transportation', 'expense'),
('Shopping', 'expense'),
('Entertainment', 'expense'),
('Bills & Utilities', 'expense'),
('Healthcare', 'expense'),
('Education', 'expense'),
('Travel', 'expense'),
('Rent', 'expense'),
('Groceries', 'expense'),
('Insurance', 'expense'),
('Personal Care', 'expense'),
('Gifts & Donations', 'expense'),
('Others', 'expense');

-- Insert Default Income Categories
INSERT INTO categories (category_name, category_type) VALUES
('Salary', 'income'),
('Freelance', 'income'),
('Business', 'income'),
('Investment', 'income'),
('Rental Income', 'income'),
('Gifts', 'income'),
('Bonus', 'income'),
('Commission', 'income'),
('Others', 'income');
```

## 🗃️ Project Structure

```
personal-finance-management-platform/
│
├── backend/
│   ├── config/
│   │   └── dbcon.php           # Database connection
│   ├── budget-func.php         # Budget functions
│   ├── report-func.php         # Report functions
│   └── session.php             # Session management
│
├── frontend/
│   ├── assets/
│   │   ├── css/                # Stylesheets
│   │   │   ├── bootstrap.min.css
│   │   │   ├── dashboard.css
│   │   │   ├── budget.css
│   │   │   ├── reports.css
│   │   │   └── transaction.css
│   │   ├── js/                 # JavaScript files
│   │   │   ├── bootstrap.bundle.min.js
│   │   │   ├── budget.js
│   │   │   └── reports.js
│   │   ├── chart/              # Chart.js library
│   │   └── jquery/             # jQuery library
│   │
│   └── pages/
│       ├── dashboard/
│       │   ├── dashboard.php   # Main dashboard
│       │   ├── add_expense.php # Add expense form
│       │   ├── add_income.php  # Add income form
│       │   ├── transactions.php # Transaction listing
│       │   ├── profile.php     # User profile
│       │   └── footer.php      # Page footer
│       │
│       ├── budget/
│       │   ├── set-budget.php  # Set budget limits
│       │   └── view-budget.php # View all budgets
│       │
│       ├── report/
│       │   ├── report.php      # Monthly analysis
│       │   ├── category.php    # Category-wise report
│       │   ├── comparison.php  # Month comparison
│       │   ├── spending-trends.php # Yearly trends
│       │   └── export-data.php # Data export
│       │
│       ├── user/
│       │   ├── landing.php     # Landing page
│       │   ├── login.php       # Login page
│       │   ├── register.php    # Registration
│       │   └── logout.php      # Logout handler
│       │
│       ├── sidebar.php         # Navigation sidebar
│       └── add-asset.html      # CSS/JS includes
│
└── database/
    └── schema.sql              # Database initialization script
```

## 📖 Usage Guide

### 1. User Registration & Login

**Register New Account:**
1. Click "Get Started" or "Register" on landing page
2. Fill in:
   - Full Name
   - Email Address
   - Phone Number
   - Password (min 6 characters)
3. Click "Register"
4. Login with email and password

### 2. Adding Transactions

**Add Income:**
1. Navigate to "Add Income" from sidebar
2. Select income category (Salary, Freelance, etc.)
3. Enter amount and date
4. Select payment method
5. Add optional description
6. Click "ADD INCOME"

**Add Expense:**
1. Navigate to "Add Expense" from sidebar
2. Select expense category
3. Enter amount and date
4. Select payment method
5. Add optional description
6. Click "ADD EXPENSE"

### 3. Managing Budgets

**Set Monthly Budget:**
1. Go to "Budget" → "Set Budget"
2. Select month from dropdown
3. Enter budget amounts for each category
4. Click "Save Budgets"

**View Budget Status:**
- Progress bars show spending vs budget
- Color codes indicate status:
  - Green: Within budget (<90%)
  - Yellow: Approaching limit (90-100%)
  - Red: Over budget (>100%)

### 4. Viewing Reports

**Monthly Analysis:**
1. Go to "Reports"
2. Select two months to compare
3. View:
   - Category comparison chart
   - Spending trends
   - Insights and suggestions

**Category-wise Report:**
1. Go to "Reports" → Category Report
2. Select month
3. View spending breakdown by category

**Spending Trends:**
1. Go to "Reports" → Spending Trends
2. Select year
3. View monthly income/expense trends

### 5. Exporting Data

**CSV Export:**
1. Go to "Export Data"
2. Select export type (Transactions, Category Summary, Monthly Summary)
3. Choose CSV format
4. Select date range
5. Click "Export Data"

**PDF Export:**
1. Follow same steps as CSV
2. Choose PDF format
3. Generates formatted report with charts

### 6. Managing Transactions

**Filter Transactions:**
- By month
- By type (Income/Expense)
- By category
- Search by description

**View Details:**
- All transactions appear in table format
- Color-coded amounts (green for income, red for expense)
- Pagination for easy navigation

## 🌐 API Endpoints

### Budget Operations

| Endpoint | Method | Description |
|----------|--------|-------------|
| `budget-process.php` | POST | Get budget status, update budgets |
| `budget-func.php` | - | Core budget functions |

### Report Operations

| Endpoint | Method | Description |
|----------|--------|-------------|
| `get-report-data.php` | GET/POST | Fetch report data |
| `export-data.php` | POST | Export data to CSV/PDF |

### Transaction Operations

| Endpoint | Method | Description |
|----------|--------|-------------|
| `transaction-process.php` | POST | Add new expense |
| `income-process.php` | POST | Add new income |

## 🗂️ ER Diagram

![ER Diagram](https://github.com/user-attachments/assets/711e6a0b-6330-4d2d-8797-adce60e71c4c)

---

## 🛠️ Troubleshooting

### Common Issues and Solutions

**1. Database Connection Error**
```
Error: Connection failed: Access denied for user
```
**Solution:**
- Check credentials in `backend/config/dbcon.php`
- Verify MySQL is running in XAMPP
- Ensure database name is correct

**2. Session Start Error**
```
Warning: session_start(): Cannot start session after headers have been sent
```
**Solution:**
- Ensure no whitespace before `<?php` tags
- Move `Session::startSession()` to top of page

**3. Chart Not Showing**
```
Error: Chart is not defined
```
**Solution:**
- Check Chart.js library is loaded
- Verify console for JavaScript errors
- Ensure canvas element exists

**4. 404 Page Not Found**
```
Error: Requested URL not found
```
**Solution:**
- Check file paths are correct
- Verify .htaccess settings (if using)
- Ensure Apache is configured correctly

**5. Export Not Working**
```
Error: Cannot modify header information
```
**Solution:**
- Remove any output before export
- Check for spaces before `<?php`
- Verify file write permissions

### Debugging Tips

**Enable Error Reporting:**
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

**Check Logs:**
- Apache logs: `C:\xampp\apache\logs\error.log`
- PHP logs: `C:\xampp\php\logs\php_error_log`

**Test Database Connection:**
```php
$conn = getConnection();
if ($conn) {
    echo "Connected successfully";
} else {
    echo "Connection failed";
}
```

## 🔐 Security Features

### Implemented Security Measures

1. **Password Hashing**
   - Uses `password_hash()` with bcrypt
   - Passwords never stored in plain text

2. **Session Management**
   - Secure session handling
   - Session timeout implemented
   - Login required for protected pages

3. **SQL Injection Prevention**
   - Prepared statements for all queries
   - Input validation and sanitization

4. **XSS Prevention**
   - `htmlspecialchars()` for output escaping
   - Content Security Policy headers

5. **CSRF Protection** (Recommended Addition)
   - CSRF tokens for forms
   - SameSite cookie attributes

### Security Best Practices

```php
// Password hashing example
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Prepared statement example
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);

// Output escaping example
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```
## 🖼️ Screenshots

![Home Page](https://github.com/user-attachments/assets/858bb704-5c38-42db-9b4f-ae5e199d0fd3)

![Dashboard Page](https://github.com/user-attachments/assets/f3957b56-37d2-4db1-a352-0068b63279d8)

![Graphs](https://github.com/user-attachments/assets/8948360e-49dd-4172-866c-1f6c62f2e9aa)

![Register](https://github.com/user-attachments/assets/6e91bfbc-7663-4b07-9ffc-e9184937a939)

![Login](https://github.com/user-attachments/assets/81cb8e4f-a2a0-43af-8ca0-11d4b39ad296)

![Expense](https://github.com/user-attachments/assets/df1792ad-f5c4-4e02-b058-23d5bc751f29)

![Income](https://github.com/user-attachments/assets/1705ad75-23d9-4b1f-b35f-f11184a728c9)

![Transaction](https://github.com/user-attachments/assets/549c88a9-272a-452c-a970-28fa91ee5ad4)

![Budget](https://github.com/user-attachments/assets/5440c609-711e-4302-b131-dbb5a0f168d3)

![Report](https://github.com/user-attachments/assets/c2b65f40-3cd3-4f82-a919-c0ce21bbe656)

![Compare Budget](https://github.com/user-attachments/assets/2899a3b2-aff3-4845-84f1-d70e9f6d474c)

---

## 🔮 Future Enhancements

### Planned Features

1. **Email Notifications**
   - Budget alerts via email
   - Monthly report summaries

2. **Multi-Currency Support**
   - Currency conversion
   - Exchange rate API integration

3. **Recurring Transactions**
   - Automated monthly bills
   - Scheduled income entries

4. **Advanced Analytics**
   - Predictive spending patterns
   - Financial goal tracking

5. **Mobile App**
   - React Native version
   - Offline support

6. **Data Import**
   - Bank statement uploads
   - CSV/Excel import

7. **Two-Factor Authentication**
   - Enhanced login security
   - SMS/Email verification

8. **API Development**
   - RESTful API for third-party integration
   - Mobile app backend

## 💼 Support

For issues, questions, or contributions:
- **Documentation**: Refer to this README
- **Issues**: Report bugs through issue tracker
- **Contributions**: Pull requests welcome

## 📄 License

This project is for educational purposes as part of academic curriculum.

## ✅ Acknowledgments

- Bootstrap team for CSS framework
- Chart.js contributors for visualization library
- Font Awesome for icons
- All open-source libraries used

---

**© 2024 Personal Finance Management Platform | Developed for Academic Project**
