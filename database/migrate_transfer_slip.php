<?php
// Migration script to add transfer_slip column to orders table
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h1>Database Migration: Transfer Slip Column</h1>";
    
    // Check if transfer_slip column already exists
    $check_columns = "SHOW COLUMNS FROM orders LIKE 'transfer_slip'";
    $stmt = $db->prepare($check_columns);
    $stmt->execute();
    $transfer_slip_exists = $stmt->rowCount() > 0;
    
    if (!$transfer_slip_exists) {
        // Add transfer_slip column
        $alter_query = "ALTER TABLE orders ADD COLUMN transfer_slip VARCHAR(255) AFTER shipping_phone";
        $stmt = $db->prepare($alter_query);
        $stmt->execute();
        echo "<p style='color: green;'>✅ Added transfer_slip column to orders table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ transfer_slip column already exists</p>";
    }
    
    // Create uploads directory if it doesn't exist
    $uploads_dir = '../uploads/transfer_slips';
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
        echo "<p style='color: green;'>✅ Created uploads/transfer_slips directory</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ uploads/transfer_slips directory already exists</p>";
    }
    
    // Verify the changes
    $verify_query = "DESCRIBE orders";
    $stmt = $db->prepare($verify_query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('transfer_slip', $columns)) {
        echo "<h2 style='color: green;'>✅ Migration Successful!</h2>";
        echo "<p>Transfer slip functionality is now ready.</p>";
    } else {
        echo "<h2 style='color: red;'>❌ Migration Failed</h2>";
        echo "<p>transfer_slip column was not added successfully.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration Error: " . $e->getMessage() . "</p>";
}
?> 