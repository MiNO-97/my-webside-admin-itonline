<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    header("Location: login.php");
    exit();
}

$order_id = $_GET['id'] ?? '';
if (empty($order_id)) {
    header("Location: orders.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get order details with customer info
$query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone, c.address
          FROM orders o 
          LEFT JOIN customers c ON o.customer_id = c.id 
          WHERE o.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Get order items
$items_query = "SELECT oi.*, p.name 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$stmt = $db->prepare($items_query);
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ໃບບິນ - <?php echo htmlspecialchars($order['order_number']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                margin: 1cm;
            }

            .no-print {
                display: none !important;
            }
        }

        body {
            font-family: 'Noto Sans Lao', sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 2rem;
            background: #fff;
        }

        .invoice-header {
            border-bottom: 2px solid #333;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .company-info {
            text-align: right;
        }

        .invoice-details {
            margin-bottom: 2rem;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .invoice-table th,
        .invoice-table td {
            border: 1px solid #ddd;
            padding: 0.5rem;
            text-align: left;
        }

        .invoice-table th {
            background: #f8f9fa;
        }

        .totals {
            float: right;
            width: 300px;
        }

        .totals table {
            width: 100%;
        }

        .totals td {
            padding: 0.5rem;
        }

        .totals .grand-total {
            font-size: 1.2em;
            font-weight: bold;
            border-top: 2px solid #333;
        }

        .footer {
            margin-top: 4rem;
            text-align: center;
            color: #666;
        }

        .signature-section {
            margin-top: 3rem;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            text-align: center;
            flex: 1;
            margin: 0 1rem;
            padding-top: 2rem;
            border-top: 1px solid #333;
        }

        .qr-code {
            text-align: center;
            margin-top: 2rem;
        }

        .action-buttons {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0.2rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            background: #007bff;
        }

        .btn-print {
            background: #28a745;
        }

        .btn-back {
            background: #6c757d;
        }
    </style>
</head>

<body>
    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn btn-print">
            <i class="fas fa-print"></i> ພິມໃບບິນ
        </button>
        <a href="view_order.php?id=<?php echo $order_id; ?>" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> ກັບຄືນ
        </a>
    </div>

    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="row">
                <div style="float: left; width: 50%;">
                    <h1 style="margin: 0;">IT Online Store</h1>
                    <p>ລະບົບຂາຍສິນຄ້າ IT</p>
                </div>
                <div class="company-info" style="float: right; width: 50%;">
                    <p>
                        123 ຖະໜົນ IT Avenue<br>
                        ເມືອງ ຈັນທະບູລີ, ນະຄອນຫຼວງວຽງຈັນ<br>
                        ໂທ: 020-XXXXXXXX<br>
                        Email: contact@itonlinestore.la
                    </p>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <!-- Invoice Details -->
        <div class="invoice-details">
            <div style="float: left; width: 50%;">
                <h3>ຂໍ້ມູນລູກຄ້າ:</h3>
                <p>
                    ຊື່ລູກຄ້າ: <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?><br>
                    ທີ່ຢູ່: <?php echo htmlspecialchars($order['shipping_city']); ?><br>
                    ສະຖານທີ່ຈັດສົ່ງ: <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?><br>
                    ໂທ: <?php echo htmlspecialchars($order['shipping_phone']); ?><br>
                    Email: <?php echo htmlspecialchars($order['email']); ?>
                </p>
            </div>
            <div style="float: right; width: 50%; text-align: right;">
                <h3>ຂໍ້ມູນໃບບິນ:</h3>
                <p>
                    <strong>ເລກທີໃບບິນ:</strong> <?php echo htmlspecialchars($order['order_number']); ?><br>
                    <strong>ວັນທີ:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?><br>
                    <strong>ສະຖານະ:</strong> <?php
                                                $status_text = [
                                                    'pending' => 'ລໍຖ້າ',
                                                    'processing' => 'ກໍາລັງດໍາເນີນການ',
                                                    'shipped' => 'ສົ່ງແລ້ວ',
                                                    'delivered' => 'ຈັດສົ່ງແລ້ວ'
                                                ];
                                                echo $status_text[$order['status']] ?? $order['status'];
                                                ?><br>
                    <strong>ການຊໍາລະ:</strong> <?php echo $order['payment_method'] === 'cod' ? 'ຊໍາລະເງິນປາຍທາງ' : 'ໂອນເງິນທະນາຄານ'; ?>
                </p>
            </div>
            <div style="clear: both;"></div>
        </div>

        <!-- Invoice Items -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ລໍາດັບ</th>
                    <th>ລາຍການ</th>
                    <!-- <th>ລະຫັດສິນຄ້າ</th> -->
                    <th style="width: 100px;">ຈໍານວນ</th>
                    <th style="width: 120px;">ລາຄາຕໍ່ໜ່ວຍ</th>
                    <th style="width: 120px;">ລວມ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                foreach ($order_items as $item):
                ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <!-- <td><?php echo htmlspecialchars($item['name']); ?></td> -->
                        <td><?php echo $item['quantity']; ?></td>
                        <td style="text-align: right"><?php echo formatCurrency($item['unit_price']); ?></td>
                        <td style="text-align: right"><?php echo formatCurrency($item['unit_price'] * $item['quantity']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td>ລວມຍອດ:</td>
                    <td style="text-align: right"><?php echo formatCurrency($order['total_amount']); ?></td>
                </tr>
                <tr>
                    <td>ຄ່າຂົນສົ່ງ:</td>
                    <td style="text-align: right">ຟຣີ</td>
                </tr>
                <tr class="grand-total">
                    <td>ລວມທັງໝົດ:</td>
                    <td style="text-align: right"><?php echo formatCurrency($order['total_amount']); ?></td>
                </tr>
            </table>
        </div>
        <div style="clear: both;"></div>

        <!-- Terms and Notes -->
        <?php if (!empty($order['notes'])): ?>
            <div style="margin-top: 2rem;">
                <h4>ໝາຍເຫດ:</h4>
                <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
            </div>
        <?php endif; ?>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <p>ຜູ້ຮັບເງິນ</p>
                <div style="height: 60px;"></div>
                <p>___________________</p>
                <p>ວັນທີ: ___/___/____</p>
            </div>
            <div class="signature-box">
                <p>ຜູ້ຈ່າຍເງິນ</p>
                <div style="height: 60px;"></div>
                <p>___________________</p>
                <p>ວັນທີ: ___/___/____</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>ຂອບໃຈທີ່ໃຊ້ບໍລິການ IT Online Store</p>
            
        </div>

        
    </div>
</body>

</html>