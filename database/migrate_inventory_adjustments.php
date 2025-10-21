<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating inventory adjustments tables...\n";
    
    // Create inventory_adjustments table
    $db->exec("CREATE TABLE IF NOT EXISTS inventory_adjustments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        adjustment_type ENUM('increase', 'decrease', 'recount') NOT NULL,
        quantity_before INT NOT NULL DEFAULT 0,
        quantity_adjusted INT NOT NULL,
        quantity_after INT NOT NULL,
        reason VARCHAR(255),
        reference_number VARCHAR(100),
        notes TEXT,
        adjusted_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (adjusted_by) REFERENCES employees(id)
    )");
    
    // Create inventory_adjustment_batches table for bulk imports
    $db->exec("CREATE TABLE IF NOT EXISTS inventory_adjustment_batches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_name VARCHAR(255) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
        total_records INT DEFAULT 0,
        processed_records INT DEFAULT 0,
        successful_records INT DEFAULT 0,
        failed_records INT DEFAULT 0,
        error_log TEXT,
        imported_by INT,
        started_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (imported_by) REFERENCES employees(id)
    )");
    
    // Create inventory_adjustment_records table for detailed tracking
    $db->exec("CREATE TABLE IF NOT EXISTS inventory_adjustment_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT,
        row_number INT NOT NULL,
        product_code VARCHAR(100),
        product_name VARCHAR(255),
        adjustment_type VARCHAR(20),
        quantity_adjusted INT,
        reason VARCHAR(255),
        status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
        error_message TEXT,
        adjustment_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (batch_id) REFERENCES inventory_adjustment_batches(id) ON DELETE CASCADE,
        FOREIGN KEY (adjustment_id) REFERENCES inventory_adjustments(id)
    )");
    
    // Create inventory_adjustment_templates table
    $db->exec("CREATE TABLE IF NOT EXISTS inventory_adjustment_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        template_fields JSON NOT NULL,
        sample_data JSON,
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES employees(id)
    )");
    
    echo "Inventory adjustments tables created successfully!\n";
    
    // Insert default template
    echo "Creating default inventory adjustment template...\n";
    
    $template_fields = [
        'product_code' => 'optional|string',
        'product_name' => 'required|string',
        'adjustment_type' => 'required|enum:increase,decrease,recount',
        'quantity_adjusted' => 'required|numeric',
        'reason' => 'required|string',
        'reference_number' => 'optional|string',
        'notes' => 'optional|string'
    ];
    
    $sample_data = [
        [
            'product_code' => 'LAP001',
            'product_name' => 'Laptop Dell Inspiron 15',
            'adjustment_type' => 'increase',
            'quantity_adjusted' => '5',
            'reason' => 'New stock arrival',
            'reference_number' => 'PO-2024-001',
            'notes' => 'Received from supplier'
        ],
        [
            'product_code' => 'IPH001',
            'product_name' => 'iPhone 15 Pro',
            'adjustment_type' => 'decrease',
            'quantity_adjusted' => '2',
            'reason' => 'Damaged items',
            'reference_number' => 'ADJ-2024-001',
            'notes' => 'Water damage during transport'
        ],
        [
            'product_code' => 'ROU001',
            'product_name' => 'Router TP-Link Archer',
            'adjustment_type' => 'recount',
            'quantity_adjusted' => '18',
            'reason' => 'Physical count',
            'reference_number' => 'COUNT-2024-001',
            'notes' => 'Monthly inventory count'
        ]
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO inventory_adjustment_templates (name, template_fields, sample_data, description, created_by) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([
        'Inventory Adjustments Template',
        json_encode($template_fields),
        json_encode($sample_data),
        'Default template for importing inventory adjustments with product information and adjustment details'
    ]);
    
    echo "Default template created successfully!\n";
    echo "Inventory adjustments system setup completed!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
