<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Add product_id column to purchase_order_items table
    $db->exec("ALTER TABLE purchase_order_items 
               ADD COLUMN product_id INT AFTER purchase_order_id,
               ADD FOREIGN KEY (product_id) REFERENCES products(id)");

    echo "Migration completed successfully!";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
