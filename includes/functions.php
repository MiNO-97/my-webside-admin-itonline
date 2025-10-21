<?php
// Helper functions for POS IT Online System

// Get featured products
function getFeaturedProducts($db, $limit = 8) {
    try {
        $query = "SELECT p.*, pc.name as category_name 
                  FROM products p 
                  LEFT JOIN product_categories pc ON p.category_id = pc.id 
                  WHERE p.status = 'active' 
                  ORDER BY p.created_at DESC 
                  LIMIT :limit";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Get all categories
function getCategories($db) {
    try {
        $query = "SELECT * FROM product_categories WHERE status = 'active' ORDER BY name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Get products by category
function getProductsByCategory($db, $category_id, $limit = 12) {
    try {
        $query = "SELECT p.*, pc.name as category_name 
                  FROM products p 
                  LEFT JOIN product_categories pc ON p.category_id = pc.id 
                  WHERE p.status = 'active' AND p.category_id = :category_id 
                  ORDER BY p.created_at DESC 
                  LIMIT :limit";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Get cart item count
function getCartItemCount($db, $customer_id) {
    try {
        $query = "SELECT COUNT(*) as count FROM cart WHERE customer_id = :customer_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch(PDOException $e) {
        return 0;
    }
}

// Customer authentication
function authenticateCustomer($db, $email, $password) {
    try {
        $query = "SELECT * FROM customers WHERE email = :email AND status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer && password_verify($password, $customer['password'])) {
            return $customer;
        }
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

// Employee authentication
function authenticateEmployee($db, $email, $password) {
    try {
        $query = "SELECT * FROM employees WHERE email = :email AND status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($employee && password_verify($password, $employee['password'])) {
            return $employee;
        }
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

// Generate order number
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
}

// Format currency
function formatCurrency($amount) {
    return number_format($amount, 0, ',', ',') . ' ກີບ';
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['customer_id']) || isset($_SESSION['employee_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['employee_id']) && isset($_SESSION['employee_position']) && $_SESSION['employee_position'] === 'ຜູ້ບໍລິຫານລະບົບ';
}

// Get customer orders
function getCustomerOrders($db, $customer_id) {
    try {
        $query = "SELECT o.*, COUNT(oi.id) as item_count 
                  FROM orders o 
                  LEFT JOIN order_items oi ON o.id = oi.order_id 
                  WHERE o.customer_id = :customer_id 
                  GROUP BY o.id 
                  ORDER BY o.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Get order details
function getOrderDetails($db, $order_id) {
    try {
        $query = "SELECT oi.*, p.name as product_name, p.image_url 
                  FROM order_items oi 
                  LEFT JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = :order_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Get product reviews
function getProductReviews($db, $product_id, $limit = 10) {
    try {
        $query = "SELECT r.*, c.first_name, c.last_name 
                  FROM reviews r 
                  LEFT JOIN customers c ON r.customer_id = c.id 
                  WHERE r.product_id = :product_id AND r.status = 'approved' 
                  ORDER BY r.created_at DESC 
                  LIMIT :limit";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Calculate average rating
function getAverageRating($db, $product_id) {
    try {
        $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                  FROM reviews 
                  WHERE product_id = :product_id AND status = 'approved'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return ['avg_rating' => 0, 'total_reviews' => 0];
    }
}

// Check if customer has purchased product
function hasCustomerPurchasedProduct($db, $customer_id, $product_id) {
    try {
        $query = "SELECT COUNT(*) as count 
                  FROM order_items oi 
                  LEFT JOIN orders o ON oi.order_id = o.id 
                  WHERE oi.product_id = :product_id 
                  AND o.customer_id = :customer_id 
                  AND o.status IN ('delivered', 'shipped')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch(PDOException $e) {
        return false;
    }
}

// Check if customer has already reviewed product
function hasCustomerReviewedProduct($db, $customer_id, $product_id) {
    try {
        $query = "SELECT COUNT(*) as count 
                  FROM reviews 
                  WHERE customer_id = :customer_id 
                  AND product_id = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch(PDOException $e) {
        return false;
    }
}

// Update product stock
function updateProductStock($db, $product_id, $quantity, $operation = 'subtract') {
    try {
        if ($operation === 'subtract') {
            $query = "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id AND stock_quantity >= :quantity";
        } else {
            $query = "UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE id = :product_id";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch(PDOException $e) {
        return false;
    }
}

// Get sales statistics
function getSalesStatistics($db, $period = 'month') {
    try {
        $date_filter = '';
        switch($period) {
            case 'week':
                $date_filter = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $date_filter = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $date_filter = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }
        
        $query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_sales,
                    AVG(total_amount) as avg_order_value
                  FROM orders o 
                  WHERE o.status != 'cancelled' $date_filter";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return ['total_orders' => 0, 'total_sales' => 0, 'avg_order_value' => 0];
    }
}

// Get top selling products
function getTopSellingProducts($db, $limit = 10) {
    try {
        $query = "SELECT p.name, p.price, SUM(oi.quantity) as total_sold, SUM(oi.total_price) as total_revenue
                  FROM order_items oi 
                  LEFT JOIN products p ON oi.product_id = p.id 
                  LEFT JOIN orders o ON oi.order_id = o.id 
                  WHERE o.status != 'cancelled' 
                  GROUP BY p.id 
                  ORDER BY total_sold DESC 
                  LIMIT :limit";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Log activity
function logActivity($db, $user_id, $user_type, $action, $details = '') {
    try {
        $query = "INSERT INTO activity_logs (user_id, user_type, action, details, ip_address) 
                  VALUES (:user_id, :user_type, :action, :details, :ip_address)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':user_type', $user_type);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':details', $details);
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
        return $stmt->execute();
    } catch(PDOException $e) {
        return false;
    }
}

// Send email notification
function sendEmailNotification($to, $subject, $message) {
    // This is a placeholder for email functionality
    // In a real application, you would use PHPMailer or similar library
    $headers = "From: POS IT Online <noreply@positonline.com>\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Validate phone number
function isValidPhone($phone) {
    // Lao phone number validation (basic)
    return preg_match('/^[0-9+\-\s()]{8,15}$/', $phone);
}

// Get customer by ID
function getCustomerById($db, $customer_id) {
    try {
        $query = "SELECT * FROM customers WHERE id = :customer_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return false;
    }
}

// Get product by ID
function getProductById($db, $product_id) {
    try {
        $query = "SELECT p.*, pc.name as category_name 
                  FROM products p 
                  LEFT JOIN product_categories pc ON p.category_id = pc.id 
                  WHERE p.id = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return false;
    }
}

// Get cart items for a customer
function getCartItems($db, $customer_id) {
    try {
        $query = "SELECT c.*, p.name, p.image_url, p.stock_quantity, pc.name as category_name, c.unit_price as price
                  FROM cart c
                  LEFT JOIN products p ON c.product_id = p.id
                  LEFT JOIN product_categories pc ON p.category_id = pc.id
                  WHERE c.customer_id = ? AND p.status = 'active'
                  ORDER BY c.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$customer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Add item to cart
function addToCart($db, $customer_id, $product_id, $quantity = 1) {
    try {
        // Get product price
        $product_query = "SELECT price FROM products WHERE id = ? AND status = 'active'";
        $stmt = $db->prepare($product_query);
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return false;
        }
        
        // Check if item already exists in cart
        $check_query = "SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?";
        $stmt = $db->prepare($check_query);
        $stmt->execute([$customer_id, $product_id]);
        $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_item) {
            // Update quantity
            $update_query = "UPDATE cart SET quantity = quantity + ? WHERE id = ?";
            $stmt = $db->prepare($update_query);
            return $stmt->execute([$quantity, $existing_item['id']]);
        } else {
            // Add new item
            $insert_query = "INSERT INTO cart (customer_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($insert_query);
            return $stmt->execute([$customer_id, $product_id, $quantity, $product['price']]);
        }
    } catch(PDOException $e) {
        return false;
    }
}

// Remove item from cart
function removeFromCart($db, $cart_id, $customer_id) {
    try {
        $query = "DELETE FROM cart WHERE id = ? AND customer_id = ?";
        $stmt = $db->prepare($query);
        return $stmt->execute([$cart_id, $customer_id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Update cart item quantity
function updateCartQuantity($db, $cart_id, $customer_id, $quantity) {
    try {
        if ($quantity <= 0) {
            return removeFromCart($db, $cart_id, $customer_id);
        }
        
        $query = "UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?";
        $stmt = $db->prepare($query);
        return $stmt->execute([$quantity, $cart_id, $customer_id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Clear customer cart
function clearCart($db, $customer_id) {
    try {
        $query = "DELETE FROM cart WHERE customer_id = ?";
        $stmt = $db->prepare($query);
        return $stmt->execute([$customer_id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Get order by ID for customer
function getOrderById($db, $order_id, $customer_id) {
    try {
        $query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone 
                  FROM orders o 
                  LEFT JOIN customers c ON o.customer_id = c.id 
                  WHERE o.id = ? AND o.customer_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$order_id, $customer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return false;
    }
}

// Get order items
function getOrderItems($db, $order_id) {
    try {
        $query = "SELECT oi.*, p.name, p.image_url, p.sku 
                  FROM order_items oi 
                  LEFT JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}
?> 