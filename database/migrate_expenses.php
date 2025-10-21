<?php
require_once('../config/database.php');

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed");
}

// Create expense categories table
$sql_expense_categories = "CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Create expenses table
$sql_expenses = "CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    employee_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    receipt_image VARCHAR(500),
    payment_method ENUM('cash', 'bank_transfer', 'credit_card', 'other') DEFAULT 'cash',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (approved_by) REFERENCES employees(id)
)";

try {
    // Execute the SQL queries
    $conn->exec($sql_expense_categories);
    echo "Expense categories table created successfully\n";

    $conn->exec($sql_expenses);
    echo "Expenses table created successfully\n";

    // Insert default expense categories
    $default_categories = [
        ['name' => 'Office Supplies', 'description' => 'Paper, pens, and other office materials'],
        ['name' => 'Utilities', 'description' => 'Electricity, water, internet bills'],
        ['name' => 'Travel', 'description' => 'Business travel expenses'],
        ['name' => 'Equipment', 'description' => 'Hardware and equipment purchases'],
        ['name' => 'Maintenance', 'description' => 'Repairs and maintenance costs'],
        ['name' => 'Others', 'description' => 'Miscellaneous expenses']
    ];

    $stmt = $conn->prepare("INSERT INTO expense_categories (name, description) VALUES (?, ?)");
    foreach ($default_categories as $category) {
        $stmt->execute([$category['name'], $category['description']]);
    }
    echo "Default expense categories inserted successfully\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
