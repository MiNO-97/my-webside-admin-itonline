<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create suppliers table
    $db->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255),
        phone VARCHAR(50),
        email VARCHAR(255),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Migrate existing supplier names from purchase_orders to suppliers
    $db->exec("INSERT IGNORE INTO suppliers (name)
               SELECT DISTINCT supplier_name 
               FROM purchase_orders 
               WHERE supplier_name IS NOT NULL");

    // Add supplier_id column to purchase_orders
    $db->exec("ALTER TABLE purchase_orders 
               ADD COLUMN supplier_id INT AFTER id,
               ADD FOREIGN KEY (supplier_id) REFERENCES suppliers(id)");

    // Update purchase_orders with supplier_id
    $db->exec("UPDATE purchase_orders po
               JOIN suppliers s ON po.supplier_name = s.name
               SET po.supplier_id = s.id");

    // Remove the old supplier_name column
    $db->exec("ALTER TABLE purchase_orders DROP COLUMN supplier_name");

    echo "Migration completed successfully!";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
