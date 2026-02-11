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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT,
    
    -- Indexes for better performance
    INDEX idx_user_date (user_id, expense_date),
    INDEX idx_category (category_id)
);

CREATE TABLE income (
    income_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL CHECK (amount > 0),
    income_date DATE NOT NULL,
    source VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT,
    
    -- Indexes for better performance
    INDEX idx_user_date (user_id, income_date),
    INDEX idx_category (category_id)
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