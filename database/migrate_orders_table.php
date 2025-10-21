<?php
// Migration script to add missing columns to orders table
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h1>Database Migration: Orders Table</h1>";
    
    // Check if columns already exist
    $check_columns = "SHOW COLUMNS FROM orders LIKE 'shipping_city'";
    $stmt = $db->prepare($check_columns);
    $stmt->execute();
    $shipping_city_exists = $stmt->rowCount() > 0;
    
    $check_columns = "SHOW COLUMNS FROM orders LIKE 'shipping_phone'";
    $stmt = $db->prepare($check_columns);
    $stmt->execute();
    $shipping_phone_exists = $stmt->rowCount() > 0;
    
    if (!$shipping_city_exists) {
        // Add shipping_city column
        $alter_query = "ALTER TABLE orders ADD COLUMN shipping_city VARCHAR(100) AFTER shipping_address";
        $stmt = $db->prepare($alter_query);
        $stmt->execute();
        echo "<p style='color: green;'>✅ Added shipping_city column to orders table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ shipping_city column already exists</p>";
    }
    
    if (!$shipping_phone_exists) {
        // Add shipping_phone column
        $alter_query = "ALTER TABLE orders ADD COLUMN shipping_phone VARCHAR(20) AFTER shipping_city";
        $stmt = $db->prepare($alter_query);
        $stmt->execute();
        echo "<p style='color: green;'>✅ Added shipping_phone column to orders table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ shipping_phone column already exists</p>";
    }
    
    // Verify the changes
    $verify_query = "DESCRIBE orders";
    $stmt = $db->prepare($verify_query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = ['shipping_city', 'shipping_phone'];
    $missing_columns = [];
    
    foreach ($required_columns as $column) {
        if (!in_array($column, $columns)) {
            $missing_columns[] = $column;
        }
    }
    
    if (empty($missing_columns)) {
        echo "<h2 style='color: green;'>✅ Migration Successful!</h2>";
        echo "<p>All required columns are now present in the orders table.</p>";
    } else {
        echo "<h2 style='color: red;'>❌ Migration Incomplete</h2>";
        echo "<p>Missing columns: " . implode(', ', $missing_columns) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration Error: " . $e->getMessage() . "</p>";
}
?> 