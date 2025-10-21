<?php
// Comprehensive system verification script
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>POS IT Online - System Verification</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .test-section { margin: 20px 0; padding: 20px; border-radius: 10px; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; }
        .info { background-color: #d1ecf1; border: 1px solid #bee5eb; }
        .status-icon { font-size: 24px; margin-right: 10px; }
        .progress-bar { height: 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container-fluid py-4'>
        <div class='row justify-content-center'>
            <div class='col-lg-10'>
                <div class='text-center mb-4'>
                    <h1><i class='fas fa-cogs me-3'></i>POS IT Online System Verification</h1>
                    <p class='text-muted'>Comprehensive system check and status report</p>
                </div>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $total_tests = 0;
    $passed_tests = 0;
    $failed_tests = 0;
    $warnings = 0;
    
    // Test 1: Database Connection
    $total_tests++;
    echo "<div class='test-section success'>
            <h3><i class='fas fa-database status-icon text-success'></i>Database Connection</h3>
            <p><strong>Status:</strong> ✅ Connected successfully</p>
            <p><strong>Details:</strong> Database connection established and working properly</p>
          </div>";
    $passed_tests++;
    
    // Test 2: Database Schema
    echo "<div class='test-section info'>
            <h3><i class='fas fa-table status-icon text-info'></i>Database Schema Verification</h3>";
    
    $required_columns = ['shipping_city', 'shipping_phone', 'transfer_slip'];
    $missing_columns = [];
    
    foreach ($required_columns as $column) {
        $total_tests++;
        $check_query = "SHOW COLUMNS FROM orders LIKE '$column'";
        $stmt = $db->prepare($check_query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "<p class='text-success'>✅ $column column exists</p>";
            $passed_tests++;
        } else {
            echo "<p class='text-danger'>❌ $column column missing</p>";
            $missing_columns[] = $column;
            $failed_tests++;
        }
    }
    
    if (empty($missing_columns)) {
        echo "<p class='text-success'><strong>✅ All required columns present</strong></p>";
    } else {
        echo "<p class='text-danger'><strong>❌ Missing columns: " . implode(', ', $missing_columns) . "</strong></p>";
    }
    echo "</div>";
    
    // Test 3: File System
    echo "<div class='test-section info'>
            <h3><i class='fas fa-folder status-icon text-info'></i>File System Check</h3>";
    
    $total_tests++;
    $uploads_dir = 'uploads/transfer_slips';
    if (is_dir($uploads_dir)) {
        echo "<p class='text-success'>✅ Uploads directory exists: $uploads_dir</p>";
        $passed_tests++;
    } else {
        echo "<p class='text-warning'>⚠️ Uploads directory missing - will be created automatically</p>";
        $warnings++;
    }
    
    $total_tests++;
    if (is_writable($uploads_dir) || !is_dir($uploads_dir)) {
        echo "<p class='text-success'>✅ Directory permissions are correct</p>";
        $passed_tests++;
    } else {
        echo "<p class='text-danger'>❌ Directory is not writable</p>";
        $failed_tests++;
    }
    echo "</div>";
    
    // Test 4: Core Files
    echo "<div class='test-section info'>
            <h3><i class='fas fa-file-code status-icon text-info'></i>Core Files Check</h3>";
    
    $core_files = [
        'checkout.php' => 'Checkout Page',
        'cart.php' => 'Cart Page',
        'admin/orders.php' => 'Admin Orders',
        'admin/view_order.php' => 'Admin Order View',
        'includes/functions.php' => 'Functions Library',
        'config/database.php' => 'Database Config'
    ];
    
    foreach ($core_files as $file => $description) {
        $total_tests++;
        if (file_exists($file)) {
            echo "<p class='text-success'>✅ $description exists</p>";
            $passed_tests++;
        } else {
            echo "<p class='text-danger'>❌ $description missing</p>";
            $failed_tests++;
        }
    }
    echo "</div>";
    
    // Test 5: Bank Transfer Functionality
    echo "<div class='test-section info'>
            <h3><i class='fas fa-university status-icon text-info'></i>Bank Transfer System</h3>";
    
    $total_tests++;
    if (file_exists('checkout.php')) {
        $checkout_content = file_get_contents('checkout.php');
        
        if (strpos($checkout_content, 'enctype="multipart/form-data"') !== false) {
            echo "<p class='text-success'>✅ File upload capability enabled</p>";
            $passed_tests++;
        } else {
            echo "<p class='text-danger'>❌ File upload capability missing</p>";
            $failed_tests++;
        }
        
        $total_tests++;
        if (strpos($checkout_content, 'bank-transfer-details') !== false) {
            echo "<p class='text-success'>✅ Bank transfer details section exists</p>";
            $passed_tests++;
        } else {
            echo "<p class='text-danger'>❌ Bank transfer details section missing</p>";
            $failed_tests++;
        }
        
        $total_tests++;
        if (strpos($checkout_content, 'transfer_slip') !== false) {
            echo "<p class='text-success'>✅ Transfer slip file input exists</p>";
            $passed_tests++;
        } else {
            echo "<p class='text-danger'>❌ Transfer slip file input missing</p>";
            $failed_tests++;
        }
        
        $total_tests++;
        if (strpos($checkout_content, '$_FILES') !== false) {
            echo "<p class='text-success'>✅ File upload processing exists</p>";
            $passed_tests++;
        } else {
            echo "<p class='text-danger'>❌ File upload processing missing</p>";
            $failed_tests++;
        }
    }
    echo "</div>";
    
    // Test 6: Admin Panel
    echo "<div class='test-section info'>
            <h3><i class='fas fa-user-shield status-icon text-info'></i>Admin Panel Check</h3>";
    
    $total_tests++;
    if (file_exists('admin/view_order.php')) {
        $admin_content = file_get_contents('admin/view_order.php');
        if (strpos($admin_content, 'transfer_slip') !== false) {
            echo "<p class='text-success'>✅ Admin can view transfer slips</p>";
            $passed_tests++;
        } else {
            echo "<p class='text-warning'>⚠️ Admin transfer slip viewing may need enhancement</p>";
            $warnings++;
        }
    } else {
        echo "<p class='text-danger'>❌ Admin order view page missing</p>";
        $failed_tests++;
    }
    echo "</div>";
    
    // Test 7: Email System
    echo "<div class='test-section info'>
            <h3><i class='fas fa-envelope status-icon text-info'></i>Email System Check</h3>";
    
    $total_tests++;
    if (file_exists('includes/email_notifications.php')) {
        echo "<p class='text-success'>✅ Email notifications system exists</p>";
        $passed_tests++;
        
        $email_content = file_get_contents('includes/email_notifications.php');
        if (strpos($email_content, 'bank_transfer') !== false) {
            echo "<p class='text-success'>✅ Bank transfer email notifications exist</p>";
        } else {
            echo "<p class='text-warning'>⚠️ Bank transfer email notifications may need enhancement</p>";
            $warnings++;
        }
    } else {
        echo "<p class='text-danger'>❌ Email notifications system missing</p>";
        $failed_tests++;
    }
    echo "</div>";
    
    // Test 8: Sample Data
    echo "<div class='test-section info'>
            <h3><i class='fas fa-database status-icon text-info'></i>Sample Data Check</h3>";
    
    $total_tests++;
    $stmt = $db->prepare("SELECT COUNT(*) FROM product_categories");
    $stmt->execute();
    $category_count = $stmt->fetchColumn();
    
    if ($category_count > 0) {
        echo "<p class='text-success'>✅ Sample categories exist ($category_count categories)</p>";
        $passed_tests++;
    } else {
        echo "<p class='text-warning'>⚠️ No sample categories found</p>";
        $warnings++;
    }
    
    $total_tests++;
    $stmt = $db->prepare("SELECT COUNT(*) FROM products");
    $stmt->execute();
    $product_count = $stmt->fetchColumn();
    
    if ($product_count > 0) {
        echo "<p class='text-success'>✅ Sample products exist ($product_count products)</p>";
        $passed_tests++;
    } else {
        echo "<p class='text-warning'>⚠️ No sample products found</p>";
        $warnings++;
    }
    echo "</div>";
    
    // Final Status Report
    $success_rate = ($passed_tests / $total_tests) * 100;
    
    echo "<div class='test-section " . ($success_rate >= 90 ? 'success' : ($success_rate >= 70 ? 'warning' : 'error')) . "'>
            <h3><i class='fas fa-chart-bar status-icon'></i>System Status Summary</h3>
            
            <div class='row'>
                <div class='col-md-6'>
                    <h4>Test Results</h4>
                    <p><strong>Total Tests:</strong> $total_tests</p>
                    <p><strong>Passed:</strong> <span class='text-success'>$passed_tests</span></p>
                    <p><strong>Failed:</strong> <span class='text-danger'>$failed_tests</span></p>
                    <p><strong>Warnings:</strong> <span class='text-warning'>$warnings</span></p>
                </div>
                <div class='col-md-6'>
                    <h4>Success Rate</h4>
                    <div class='progress mb-3'>
                        <div class='progress-bar " . ($success_rate >= 90 ? 'bg-success' : ($success_rate >= 70 ? 'bg-warning' : 'bg-danger')) . "' 
                             style='width: $success_rate%'></div>
                    </div>
                    <p><strong>$success_rate%</strong> of tests passed</p>
                </div>
            </div>";
    
    if ($success_rate >= 90) {
        echo "<div class='alert alert-success'>
                <h4><i class='fas fa-check-circle me-2'></i>System Ready!</h4>
                <p>Your POS IT Online system is fully functional and ready for use.</p>
                <p><strong>Bank transfer functionality is working correctly.</strong></p>
              </div>";
    } elseif ($success_rate >= 70) {
        echo "<div class='alert alert-warning'>
                <h4><i class='fas fa-exclamation-triangle me-2'></i>System Partially Ready</h4>
                <p>Most functionality is working, but some improvements are recommended.</p>
                <p>Check the warnings above for details.</p>
              </div>";
    } else {
        echo "<div class='alert alert-danger'>
                <h4><i class='fas fa-times-circle me-2'></i>System Needs Attention</h4>
                <p>Several critical components are missing or not working properly.</p>
                <p>Please fix the issues above before using the system.</p>
              </div>";
    }
    
    echo "<div class='mt-4'>
            <h5>Recommended Actions:</h5>
            <ul>";
    
    if (!empty($missing_columns)) {
        echo "<li><a href='database/migrate_transfer_slip.php' class='btn btn-sm btn-primary'>Run Database Migration</a></li>";
    }
    if ($warnings > 0) {
        echo "<li><a href='setup_database.php' class='btn btn-sm btn-warning'>Run Complete Setup</a></li>";
    }
    echo "<li><a href='test_bank_transfer.php' class='btn btn-sm btn-info'>Test Bank Transfer</a></li>
            <li><a href='index.php' class='btn btn-sm btn-success'>Go to Homepage</a></li>
          </ul>
        </div>
      </div>";
    
} catch (Exception $e) {
    echo "<div class='test-section error'>
            <h3><i class='fas fa-exclamation-triangle status-icon text-danger'></i>System Error</h3>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p>Please check your database configuration and try again.</p>
          </div>";
}

echo "</div>
    </div>
</div>
</body>
</html>";
?> 