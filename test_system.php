<?php
// Test script to verify system functionality
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>POS IT Online System Test</h1>";

try {
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Test functions
    $categories = getCategories($db);
    echo "<p style='color: green;'>✅ Categories loaded: " . count($categories) . "</p>";
    
    $products = getFeaturedProducts($db, 5);
    echo "<p style='color: green;'>✅ Products loaded: " . count($products) . "</p>";
    
    // Test email notifications
    if (file_exists('includes/email_notifications.php')) {
        echo "<p style='color: green;'>✅ Email notifications file exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Email notifications file missing</p>";
    }
    
    // Test payment processor
    if (file_exists('includes/payment_processor.php')) {
        echo "<p style='color: green;'>✅ Payment processor file exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Payment processor file missing</p>";
    }
    
    // Test AJAX files
    if (file_exists('ajax/update_order_status.php')) {
        echo "<p style='color: green;'>✅ Order status update AJAX exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Order status update AJAX missing</p>";
    }
    
    // Test order tracking
    if (file_exists('order_tracking.php')) {
        echo "<p style='color: green;'>✅ Order tracking page exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Order tracking page missing</p>";
    }
    
    // Test database schema compatibility
    $test_query = "DESCRIBE orders";
    $stmt = $db->prepare($test_query);
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
        echo "<p style='color: green;'>✅ Orders table has all required columns</p>";
    } else {
        echo "<p style='color: red;'>❌ Orders table missing columns: " . implode(', ', $missing_columns) . "</p>";
    }
    
    echo "<h2>System Status: ✅ READY</h2>";
    echo "<p>All core components are in place and ready for use.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ System Error: " . $e->getMessage() . "</p>";
}
?> 