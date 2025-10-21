<?php
// Comprehensive database setup script for POS IT Online System
require_once 'config/database.php';

echo "<h1>POS IT Online Database Setup</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check and add missing columns to orders table
    echo "<h2>Checking Orders Table...</h2>";
    
    $check_columns = "SHOW COLUMNS FROM orders LIKE 'shipping_city'";
    $stmt = $db->prepare($check_columns);
    $stmt->execute();
    $shipping_city_exists = $stmt->rowCount() > 0;
    
    $check_columns = "SHOW COLUMNS FROM orders LIKE 'shipping_phone'";
    $stmt = $db->prepare($check_columns);
    $stmt->execute();
    $shipping_phone_exists = $stmt->rowCount() > 0;
    
    if (!$shipping_city_exists) {
        $alter_query = "ALTER TABLE orders ADD COLUMN shipping_city VARCHAR(100) AFTER shipping_address";
        $stmt = $db->prepare($alter_query);
        $stmt->execute();
        echo "<p style='color: green;'>✅ Added shipping_city column</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ shipping_city column already exists</p>";
    }
    
    if (!$shipping_phone_exists) {
        $alter_query = "ALTER TABLE orders ADD COLUMN shipping_phone VARCHAR(20) AFTER shipping_city";
        $stmt = $db->prepare($alter_query);
        $stmt->execute();
        echo "<p style='color: green;'>✅ Added shipping_phone column</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ shipping_phone column already exists</p>";
    }
    
    // Check if sample data exists
    echo "<h2>Checking Sample Data...</h2>";
    
    $check_categories = "SELECT COUNT(*) as count FROM product_categories";
    $stmt = $db->prepare($check_categories);
    $stmt->execute();
    $category_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($category_count == 0) {
        echo "<p style='color: orange;'>⚠️ No categories found. Inserting sample data...</p>";
        
        // Insert sample categories
        $categories = [
            ['ຄອມພິວເຕີ', 'ຄອມພິວເຕີແລະອຸປະກອນທີ່ກ່ຽວຂ້ອງ'],
            ['ໂທລະສັບ', 'ໂທລະສັບມືຖືແລະອຸປະກອນທີ່ກ່ຽວຂ້ອງ'],
            ['ອຸປະກອນເຄືອຂ່າຍ', 'ອຸປະກອນເຄືອຂ່າຍແລະການເຊື່ອມຕໍ່'],
            ['ອຸປະກອນພິມ', 'ອຸປະກອນພິມແລະສະແກນ']
        ];
        
        $insert_category = "INSERT INTO product_categories (name, description) VALUES (?, ?)";
        $stmt = $db->prepare($insert_category);
        
        foreach ($categories as $category) {
            $stmt->execute($category);
        }
        
        echo "<p style='color: green;'>✅ Sample categories inserted</p>";
        
        // Insert sample products
        $products = [
            [1, 'Laptop Dell Inspiron 15', 'ຄອມພິວເຕີພົກພາ Dell Inspiron 15 ຈໍ 15.6 ນິ້ວ', 2500000, 10],
            [1, 'Desktop HP Pavilion', 'ຄອມພິວເຕີໂຕະງານ HP Pavilion', 1800000, 5],
            [2, 'iPhone 15 Pro', 'ໂທລະສັບມືຖື iPhone 15 Pro 128GB', 4500000, 15],
            [2, 'Samsung Galaxy S24', 'ໂທລະສັບມືຖື Samsung Galaxy S24 256GB', 3800000, 12],
            [3, 'Router TP-Link Archer', 'ເຄືອຂ່າຍ WiFi Router TP-Link Archer C6', 450000, 20],
            [4, 'Printer HP LaserJet', 'ອຸປະກອນພິມ HP LaserJet Pro M404n', 1200000, 8]
        ];
        
        $insert_product = "INSERT INTO products (category_id, name, description, price, stock_quantity) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($insert_product);
        
        foreach ($products as $product) {
            $stmt->execute($product);
        }
        
        echo "<p style='color: green;'>✅ Sample products inserted</p>";
        
        // Insert sample admin
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_admin = "INSERT INTO employees (first_name, last_name, email, phone, position, password) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($insert_admin);
        $stmt->execute(['ອະດີມິນ', 'ລະບົບ', 'admin@positonline.com', '02012345678', 'ຜູ້ບໍລິຫານລະບົບ', $admin_password]);
        
        echo "<p style='color: green;'>✅ Sample admin account created</p>";
        echo "<p><strong>Admin Login:</strong> admin@positonline.com / admin123</p>";
        
    } else {
        echo "<p style='color: blue;'>ℹ️ Sample data already exists</p>";
    }
    
    // Verify system components
    echo "<h2>System Verification...</h2>";
    
    $components = [
        'includes/email_notifications.php' => 'Email Notifications',
        'includes/payment_processor.php' => 'Payment Processor',
        'ajax/update_order_status.php' => 'Order Status AJAX',
        'order_tracking.php' => 'Order Tracking',
        'order_confirmation.php' => 'Order Confirmation'
    ];
    
    foreach ($components as $file => $name) {
        if (file_exists($file)) {
            echo "<p style='color: green;'>✅ $name component exists</p>";
        } else {
            echo "<p style='color: red;'>❌ $name component missing</p>";
        }
    }
    
    // Final verification
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
        echo "<h2 style='color: green;'>🎉 Setup Complete!</h2>";
        echo "<p>Your POS IT Online system is now ready to use.</p>";
        echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Homepage</a></p>";
    } else {
        echo "<h2 style='color: red;'>❌ Setup Incomplete</h2>";
        echo "<p>Missing columns: " . implode(', ', $missing_columns) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Setup Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h1, h2, h3 {
    color: #333;
}
p {
    margin: 10px 0;
}
ul {
    margin: 10px 0;
    padding-left: 20px;
}
a {
    color: #007bff;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style> 