<?php
// Email notification functions for order updates

function sendOrderConfirmationEmail($db, $order_id) {
    try {
        // Get order details
        $query = "SELECT o.*, c.first_name, c.last_name, c.email 
                  FROM orders o 
                  LEFT JOIN customers c ON o.customer_id = c.id 
                  WHERE o.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return false;
        }
        
        // Get order items
        $items_query = "SELECT oi.*, p.name 
                       FROM order_items oi 
                       LEFT JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = ?";
        $stmt = $db->prepare($items_query);
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $subject = "ຢືນຢັນຄໍາສັ່ງຊື້ #" . $order['order_number'];
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .item { border-bottom: 1px solid #eee; padding: 10px 0; }
                .total { font-weight: bold; font-size: 18px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>ຢືນຢັນຄໍາສັ່ງຊື້</h2>
            </div>
            <div class='content'>
                <p>ສະບາຍດີ " . htmlspecialchars($order['first_name']) . " " . htmlspecialchars($order['last_name']) . ",</p>
                
                <p>ຄໍາສັ່ງຊື້ຂອງທ່ານໄດ້ຮັບການຢືນຢັນແລ້ວ. ລາຍລະອຽດດັ່ງນີ້:</p>
                
                <h3>ຄໍາສັ່ງຊື້ #" . $order['order_number'] . "</h3>
                <p><strong>ວັນທີ:</strong> " . date('d/m/Y H:i', strtotime($order['created_at'])) . "</p>
                <p><strong>ສະຖານະ:</strong> ລໍຖ້າການຈັດການ</p>
                
                <h4>ລາຍການສິນຄ້າ:</h4>";
        
        foreach ($items as $item) {
            $message .= "
                <div class='item'>
                    <strong>" . htmlspecialchars($item['name']) . "</strong><br>
                    ຈໍານວນ: " . $item['quantity'] . " ຊິ້ນ<br>
                    ລາຄາ: " . number_format($item['unit_price'], 0) . " ກີບ
                </div>";
        }
        
        $message .= "
                <div class='total'>
                    <strong>ລວມ: " . number_format($order['total_amount'], 0) . " ກີບ</strong>
                </div>
                
                <h4>ຂໍ້ມູນການຈັດສົ່ງ:</h4>
                <p><strong>ທີ່ຢູ່:</strong> " . htmlspecialchars($order['shipping_address']) . "</p>
                <p><strong>ເມືອງ:</strong> " . htmlspecialchars($order['shipping_city']) . "</p>
                <p><strong>ໂທລະສັບ:</strong> " . htmlspecialchars($order['shipping_phone']) . "</p>
                
                <p>ພວກເຮົາຈະແຈ້ງເຕືອນທ່ານເມື່ອຄໍາສັ່ງຊື້ມີການປ່ຽນແປງສະຖານະ.</p>
                
                <p>ຂອບໃຈທີ່ໃຊ້ບໍລິການຂອງພວກເຮົາ,<br>
                ທີມງານ POS IT Online</p>
            </div>
        </body>
        </html>";
        
        return sendEmail($order['email'], $subject, $message);
        
    } catch (Exception $e) {
        return false;
    }
}

function sendOrderStatusUpdateEmail($db, $order_id, $new_status) {
    try {
        // Get order details
        $query = "SELECT o.*, c.first_name, c.last_name, c.email 
                  FROM orders o 
                  LEFT JOIN customers c ON o.customer_id = c.id 
                  WHERE o.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return false;
        }
        
        $status_text = [
            'confirmed' => 'ຢືນຢັນແລ້ວ',
            'processing' => 'ກໍາລັງຈັດການ',
            'shipped' => 'ສົ່ງແລ້ວ',
            'delivered' => 'ສົ່ງເຖິງແລ້ວ',
            'cancelled' => 'ຍົກເລີກແລ້ວ'
        ];
        
        $subject = "ການປັບປຸງສະຖານະຄໍາສັ່ງຊື້ #" . $order['order_number'];
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .status { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>ການປັບປຸງສະຖານະຄໍາສັ່ງຊື້</h2>
            </div>
            <div class='content'>
                <p>ສະບາຍດີ " . htmlspecialchars($order['first_name']) . " " . htmlspecialchars($order['last_name']) . ",</p>
                
                <div class='status'>
                    <h3>ຄໍາສັ່ງຊື້ #" . $order['order_number'] . "</h3>
                    <p><strong>ສະຖານະໃໝ່:</strong> " . ($status_text[$new_status] ?? $new_status) . "</p>
                    <p><strong>ວັນທີ:</strong> " . date('d/m/Y H:i') . "</p>
                </div>
                
                <p>ຄໍາສັ່ງຊື້ຂອງທ່ານໄດ້ຮັບການປັບປຸງສະຖານະແລ້ວ. ທ່ານສາມາດຕິດຕາມຄໍາສັ່ງຊື້ໄດ້ທີ່ເວັບໄຊທ໌ຂອງພວກເຮົາ.</p>
                
                <p>ຂອບໃຈທີ່ໃຊ້ບໍລິການຂອງພວກເຮົາ,<br>
                ທີມງານ POS IT Online</p>
            </div>
        </body>
        </html>";
        
        return sendEmail($order['email'], $subject, $message);
        
    } catch (Exception $e) {
        return false;
    }
}

function sendEmail($to, $subject, $message) {
    // For now, we'll use PHP's mail() function
    // In production, you might want to use a service like SendGrid, Mailgun, or AWS SES
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: POS IT Online <noreply@positonline.com>" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

function sendOrderCancellationEmail($db, $order_id) {
    try {
        // Get order details
        $query = "SELECT o.*, c.first_name, c.last_name, c.email 
                  FROM orders o 
                  LEFT JOIN customers c ON o.customer_id = c.id 
                  WHERE o.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return false;
        }
        
        $subject = "ຍົກເລີກຄໍາສັ່ງຊື້ #" . $order['order_number'];
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .notice { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>ຍົກເລີກຄໍາສັ່ງຊື້</h2>
            </div>
            <div class='content'>
                <p>ສະບາຍດີ " . htmlspecialchars($order['first_name']) . " " . htmlspecialchars($order['last_name']) . ",</p>
                
                <div class='notice'>
                    <h3>ຄໍາສັ່ງຊື້ #" . $order['order_number'] . " ໄດ້ຮັບການຍົກເລີກແລ້ວ</h3>
                    <p><strong>ວັນທີຍົກເລີກ:</strong> " . date('d/m/Y H:i') . "</p>
                </div>
                
                <p>ຄໍາສັ່ງຊື້ຂອງທ່ານໄດ້ຮັບການຍົກເລີກຕາມຄໍາຮ້ອງຂໍ. ຖ້າທ່ານມີຄໍາຖາມໃດໆ, ກະລຸນາຕິດຕໍ່ພວກເຮົາ.</p>
                
                <p>ຂອບໃຈທີ່ໃຊ້ບໍລິການຂອງພວກເຮົາ,<br>
                ທີມງານ POS IT Online</p>
            </div>
        </body>
        </html>";
        
        return sendEmail($order['email'], $subject, $message);
        
    } catch (Exception $e) {
        return false;
    }
}

function sendRefundRequestEmail($db, $order_id) {
    try {
        // Get order details
        $query = "SELECT o.*, c.first_name, c.last_name, c.email 
                  FROM orders o 
                  LEFT JOIN customers c ON o.customer_id = c.id 
                  WHERE o.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return false;
        }
        
        $subject = "ຄໍາຮ້ອງຂໍຄືນເງິນ #" . $order['order_number'];
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #ffc107; color: #212529; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .notice { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>ຄໍາຮ້ອງຂໍຄືນເງິນ</h2>
            </div>
            <div class='content'>
                <p>ສະບາຍດີ " . htmlspecialchars($order['first_name']) . " " . htmlspecialchars($order['last_name']) . ",</p>
                
                <div class='notice'>
                    <h3>ຄໍາສັ່ງຊື້ #" . $order['order_number'] . " - ຄໍາຮ້ອງຂໍຄືນເງິນ</h3>
                    <p><strong>ວັນທີຂໍຄືນເງິນ:</strong> " . date('d/m/Y H:i') . "</p>
                    <p><strong>ຈໍານວນເງິນ:</strong> " . number_format($order['total_amount'], 0) . " ກີບ</p>
                </div>
                
                <p>ຄໍາຮ້ອງຂໍຄືນເງິນຂອງທ່ານໄດ້ຮັບການບັນທຶກແລ້ວ. ທີມງານຂອງພວກເຮົາຈະກວດສອບແລະຕິດຕໍ່ທ່ານໃນການດໍາເນີນການຕໍ່ໄປ.</p>
                
                <p>ຂອບໃຈທີ່ໃຊ້ບໍລິການຂອງພວກເຮົາ,<br>
                ທີມງານ POS IT Online</p>
            </div>
        </body>
        </html>";
        
        return sendEmail($order['email'], $subject, $message);
        
    } catch (Exception $e) {
        return false;
    }
}
?> 