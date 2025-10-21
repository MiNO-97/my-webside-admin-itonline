<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Check if admin is logged in
session_start();
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ບໍ່ໄດ້ຮັບອະນຸຍາດ']);
    exit();
}

// Get and validate input
$input = json_decode(file_get_contents('php://input'), true);
$product_id = $input['product_id'] ?? null;
$quantity = $input['quantity'] ?? null;

if (!$product_id || !$quantity || !is_numeric($quantity) || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ຂໍ້ມູນບໍ່ຖືກຕ້ອງ']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Start transaction
    $db->beginTransaction();

    // Update stock quantity
    $stmt = $db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
    $stmt->execute([$quantity, $product_id]);

    // Log the stock update
    logActivity(
        $db,
        $_SESSION['admin_id'],
        'employee',
        'Updated product stock',
        "Product ID: $product_id, Added quantity: $quantity"
    );

    // Commit transaction
    $db->commit();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການອັບເດດສະຕ໋ອກ']);
}
