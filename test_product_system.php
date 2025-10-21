<?php
// Test file to verify product management system
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Product Management System Test</h1>";

try {
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test getCategories function
    $categories = getCategories($db);
    echo "<p style='color: green;'>✓ Categories loaded: " . count($categories) . " categories found</p>";
    
    // Test getProductById function
    $test_product = getProductById($db, 1);
    if ($test_product) {
        echo "<p style='color: green;'>✓ Product retrieval working - Found product: " . htmlspecialchars($test_product['name']) . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No products found in database (this is normal if no products exist yet)</p>";
    }
    
    // Test directory creation
    $upload_dir = 'uploads/products/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        echo "<p style='color: green;'>✓ Created uploads/products directory</p>";
    } else {
        echo "<p style='color: green;'>✓ Uploads directory exists</p>";
    }
    
    // Test admin files existence
    $admin_files = [
        'admin/add_product.php',
        'admin/edit_product.php', 
        'admin/view_product.php',
        'admin/products.php'
    ];
    
    foreach ($admin_files as $file) {
        if (file_exists($file)) {
            echo "<p style='color: green;'>✓ $file exists</p>";
        } else {
            echo "<p style='color: red;'>✗ $file missing</p>";
        }
    }
    
    echo "<h2>System Status: READY</h2>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='admin/login.php'>Login to Admin Panel</a></li>";
    echo "<li><a href='admin/products.php'>Manage Products</a></li>";
    echo "<li><a href='admin/add_product.php'>Add New Product</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?> 