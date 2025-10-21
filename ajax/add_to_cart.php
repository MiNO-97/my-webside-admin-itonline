<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'ກະລຸນາເຂົ້າສູ່ລະບົບກ່ອນ']);
    exit();
}

// Check if request is POST
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'ວິທີການທີ່ບໍ່ຖືກຕ້ອງ']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if(!$input) {
    echo json_encode(['success' => false, 'message' => 'ຂໍ້ມູນບໍ່ຖືກຕ້ອງ']);
    exit();
}

$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
$customer_id = $_SESSION['customer_id'];

// Validate input
if($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'ຂໍ້ມູນບໍ່ຖືກຕ້ອງ']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if product exists and has enough stock
    $query = "SELECT id, name, price, stock_quantity FROM products WHERE id = :product_id AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$product) {
        echo json_encode(['success' => false, 'message' => 'ສິນຄ້າບໍ່ພົບ']);
        exit();
    }
    
    if($product['stock_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'ສິນຄ້າໃນສິນຄ້າບໍ່ພຽງພໍ']);
        exit();
    }
    
    // Check if item already exists in cart
    $query = "SELECT id, quantity FROM cart WHERE customer_id = :customer_id AND product_id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($cart_item) {
        // Update existing cart item
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        if($product['stock_quantity'] < $new_quantity) {
            echo json_encode(['success' => false, 'message' => 'ສິນຄ້າໃນສິນຄ້າບໍ່ພຽງພໍ']);
            exit();
        }
        
        $query = "UPDATE cart SET quantity = :quantity, updated_at = NOW() WHERE id = :cart_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
        $stmt->bindParam(':cart_id', $cart_item['id'], PDO::PARAM_INT);
        
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'ອັບເດດກະຕ່າສໍາເລັດແລ້ວ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການອັບເດດກະຕ່າ']);
        }
    } else {
        // Add new item to cart
        $query = "INSERT INTO cart (customer_id, product_id, quantity, unit_price) VALUES (:customer_id, :product_id, :quantity, :unit_price)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':unit_price', $product['price']);
        
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'ເພີ່ມສິນຄ້າລົງໃນກະຕ່າສໍາເລັດແລ້ວ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການເພີ່ມສິນຄ້າ']);
        }
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນລະບົບ']);
}
?> 