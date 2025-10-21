<?php
session_start();
require_once('../../config/database.php');

if (!isset($_SESSION['employee_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $expense_id = $_POST['expense_id'];
        $action = $_POST['action'];
        $employee_id = $_SESSION['employee_id'];

        if (!in_array($action, ['approve', 'reject'])) {
            throw new Exception('Invalid action');
        }

        $status = ($action === 'approve') ? 'approved' : 'rejected';

        $stmt = $conn->prepare("
            UPDATE expenses 
            SET status = ?, 
                approved_by = ?,
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");

        $stmt->execute([$status, $employee_id, $expense_id]);

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
