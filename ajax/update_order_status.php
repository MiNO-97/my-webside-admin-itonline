<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if customer is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'ກະລຸນາເຂົ້າສູ່ລະບົບກ່ອນ']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'ວິທີການທີ່ບໍ່ຖືກຕ້ອງ']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'ຂໍ້ມູນບໍ່ຖືກຕ້ອງ']);
    exit();
}

$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$action = isset($input['action']) ? $input['action'] : '';
$customer_id = $_SESSION['customer_id'];

// Validate input
if ($order_id <= 0 || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'ຂໍ້ມູນບໍ່ຖືກຕ້ອງ']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if order belongs to customer
    $query = "SELECT id, status, total_amount FROM orders WHERE id = :order_id AND customer_id = :customer_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'ຄໍາສັ່ງຊື້ບໍ່ພົບ']);
        exit();
    }
    
    // Handle different actions
    switch ($action) {
        case 'cancel':
            // Only allow cancellation if order is pending or confirmed
            if (!in_array($order['status'], ['pending', 'confirmed'])) {
                echo json_encode(['success' => false, 'message' => 'ບໍ່ສາມາດຍົກເລີກຄໍາສັ່ງຊື້ໄດ້']);
                exit();
            }
            
            $db->beginTransaction();
            
            // Update order status
            $update_query = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = :order_id";
            $stmt = $db->prepare($update_query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Restore product stock
            $items_query = "SELECT oi.product_id, oi.quantity, p.stock_quantity 
                           FROM order_items oi 
                           LEFT JOIN products p ON oi.product_id = p.id 
                           WHERE oi.order_id = :order_id";
            $stmt = $db->prepare($items_query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($items as $item) {
                $new_stock = $item['stock_quantity'] + $item['quantity'];
                $stock_query = "UPDATE products SET stock_quantity = :stock WHERE id = :product_id";
                $stmt2 = $db->prepare($stock_query);
                $stmt2->bindParam(':stock', $new_stock, PDO::PARAM_INT);
                $stmt2->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
                $stmt2->execute();
            }
            
            // Log activity
            logActivity($db, $customer_id, 'customer', 'cancelled_order', "Cancelled order #$order_id");
            
            // Send cancellation email
            require_once '../includes/email_notifications.php';
            sendOrderCancellationEmail($db, $order_id);
            
            $db->commit();
            
            echo json_encode(['success' => true, 'message' => 'ຍົກເລີກຄໍາສັ່ງຊື້ສໍາເລັດແລ້ວ']);
            break;
            
        case 'request_refund':
            // Only allow refund request if order is delivered
            if ($order['status'] !== 'delivered') {
                echo json_encode(['success' => false, 'message' => 'ບໍ່ສາມາດຂໍຄືນເງິນໄດ້']);
                exit();
            }
            
            // Update order status to refund_requested
            $update_query = "UPDATE orders SET status = 'refund_requested', updated_at = NOW() WHERE id = :order_id";
            $stmt = $db->prepare($update_query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Log activity
            logActivity($db, $customer_id, 'customer', 'requested_refund', "Requested refund for order #$order_id");
            
            // Send refund request email
            require_once '../includes/email_notifications.php';
            sendRefundRequestEmail($db, $order_id);
            
            echo json_encode(['success' => true, 'message' => 'ສົ່ງຄໍາຮ້ອງຂໍຄືນເງິນສໍາເລັດແລ້ວ']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'ການດໍາເນີນການບໍ່ຖືກຕ້ອງ']);
            break;
    }
    
} catch(PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນລະບົບ']);
}
?> 