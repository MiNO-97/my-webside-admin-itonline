<?php
session_start();
require_once('../../config/database.php');

if (!isset($_SESSION['employee_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $category_id = $_POST['category_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $expense_date = $_POST['expense_date'];
        $payment_method = $_POST['payment_method'];
        $employee_id = $_SESSION['employee_id'];

        // Handle file upload
        $receipt_image = '';
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === 0) {
            $upload_dir = '../../uploads/receipts/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $target_path)) {
                $receipt_image = 'uploads/receipts/' . $file_name;
            }
        }

        // Insert expense record
        $stmt = $conn->prepare("
            INSERT INTO expenses (
                category_id, employee_id, title, description, amount,
                expense_date, payment_method, receipt_image
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $category_id,
            $employee_id,
            $title,
            $description,
            $amount,
            $expense_date,
            $payment_method,
            $receipt_image
        ]);

        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
