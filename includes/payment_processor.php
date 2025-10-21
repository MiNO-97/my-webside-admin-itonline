<?php
// Payment processing functions for POS IT Online System

class PaymentProcessor {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Process payment for an order
     */
    public function processPayment($order_id, $payment_method, $payment_data = []) {
        try {
            // Get order details
            $query = "SELECT * FROM orders WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return ['success' => false, 'message' => 'ຄໍາສັ່ງຊື້ບໍ່ພົບ'];
            }
            
            switch ($payment_method) {
                case 'cod':
                    return $this->processCashOnDelivery($order);
                case 'bank_transfer':
                    return $this->processBankTransfer($order, $payment_data);
                case 'mobile_payment':
                    return $this->processMobilePayment($order, $payment_data);
                default:
                    return ['success' => false, 'message' => 'ວິທີການຊໍາລະບໍ່ຖືກຕ້ອງ'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການຊໍາລະ: ' . $e->getMessage()];
        }
    }
    
    /**
     * Process Cash on Delivery payment
     */
    private function processCashOnDelivery($order) {
        try {
            // Update order payment status
            $query = "UPDATE orders SET payment_status = 'pending', updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$order['id']]);
            
            return [
                'success' => true,
                'message' => 'ການຊໍາລະເງິນປາຍທາງສໍາເລັດແລ້ວ',
                'payment_status' => 'pending',
                'transaction_id' => null
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການຊໍາລະ'];
        }
    }
    
    /**
     * Process Bank Transfer payment
     */
    private function processBankTransfer($order, $payment_data) {
        try {
            // Validate payment data
            if (empty($payment_data['transaction_id']) || empty($payment_data['bank_name'])) {
                return ['success' => false, 'message' => 'ຂໍ້ມູນການໂອນເງິນບໍ່ຄົບຖ້ວນ'];
            }
            
            // Update order payment status
            $query = "UPDATE orders SET 
                     payment_status = 'pending', 
                     payment_method = 'bank_transfer',
                     notes = CONCAT(COALESCE(notes, ''), '\nBank Transfer Details:\nBank: ', ?, '\nTransaction ID: ', ?),
                     updated_at = NOW() 
                     WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$payment_data['bank_name'], $payment_data['transaction_id'], $order['id']]);
            
            return [
                'success' => true,
                'message' => 'ການໂອນເງິນສໍາເລັດແລ້ວ. ກະລຸນາລໍຖ້າການຢືນຢັນ.',
                'payment_status' => 'pending',
                'transaction_id' => $payment_data['transaction_id']
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການຊໍາລະ'];
        }
    }
    
    /**
     * Process Mobile Payment
     */
    private function processMobilePayment($order, $payment_data) {
        try {
            // Validate payment data
            if (empty($payment_data['phone_number']) || empty($payment_data['provider'])) {
                return ['success' => false, 'message' => 'ຂໍ້ມູນການຊໍາລະບໍ່ຄົບຖ້ວນ'];
            }
            
            // Simulate mobile payment processing
            $transaction_id = 'MP' . date('YmdHis') . rand(1000, 9999);
            
            // Update order payment status
            $query = "UPDATE orders SET 
                     payment_status = 'paid', 
                     payment_method = 'mobile_payment',
                     notes = CONCAT(COALESCE(notes, ''), '\nMobile Payment Details:\nProvider: ', ?, '\nPhone: ', ?, '\nTransaction ID: ', ?),
                     updated_at = NOW() 
                     WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$payment_data['provider'], $payment_data['phone_number'], $transaction_id, $order['id']]);
            
            return [
                'success' => true,
                'message' => 'ການຊໍາລະຜ່ານໂທລະສັບສໍາເລັດແລ້ວ',
                'payment_status' => 'paid',
                'transaction_id' => $transaction_id
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການຊໍາລະ'];
        }
    }
    
    /**
     * Verify payment for an order
     */
    public function verifyPayment($order_id) {
        try {
            $query = "SELECT payment_status, payment_method FROM orders WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return ['success' => false, 'message' => 'ຄໍາສັ່ງຊື້ບໍ່ພົບ'];
            }
            
            return [
                'success' => true,
                'payment_status' => $order['payment_status'],
                'payment_method' => $order['payment_method']
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການກວດສອບການຊໍາລະ'];
        }
    }
    
    /**
     * Get payment methods available
     */
    public function getPaymentMethods() {
        return [
            [
                'id' => 'cod',
                'name' => 'ຊໍາລະເງິນປາຍທາງ',
                'description' => 'ຊໍາລະເງິນເມື່ອຮັບສິນຄ້າ',
                'icon' => 'fas fa-money-bill-wave'
            ],
            [
                'id' => 'bank_transfer',
                'name' => 'ໂອນເງິນທະນາຄານ',
                'description' => 'ໂອນເງິນຜ່ານບັນຊີທະນາຄານ',
                'icon' => 'fas fa-university'
            ],
            [
                'id' => 'mobile_payment',
                'name' => 'ຊໍາລະຜ່ານໂທລະສັບ',
                'description' => 'ຊໍາລະຜ່ານໂທລະສັບມືຖື',
                'icon' => 'fas fa-mobile-alt'
            ]
        ];
    }
    
    /**
     * Get bank account information
     */
    public function getBankAccounts() {
        return [
            [
                'bank_name' => 'BCEL',
                'account_name' => 'POS IT Online',
                'account_number' => '010-12-345-6789012',
                'branch' => 'ສະໂມສອນລາວ'
            ],
            [
                'bank_name' => 'LDB',
                'account_name' => 'POS IT Online',
                'account_number' => '020-12-345-6789012',
                'branch' => 'ສະໂມສອນລາວ'
            ]
        ];
    }
    
    /**
     * Get mobile payment providers
     */
    public function getMobilePaymentProviders() {
        return [
            [
                'id' => 'unitel',
                'name' => 'Unitel Money',
                'phone' => '020-12345678',
                'icon' => 'fas fa-mobile-alt'
            ],
            [
                'id' => 'etl',
                'name' => 'ETL Money',
                'phone' => '030-12345678',
                'icon' => 'fas fa-mobile-alt'
            ],
            [
                'id' => 'beeline',
                'name' => 'Beeline Money',
                'phone' => '040-12345678',
                'icon' => 'fas fa-mobile-alt'
            ]
        ];
    }
}
?> 