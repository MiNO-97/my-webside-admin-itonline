<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    header("Location: login.php");
    exit();
}

$purchase_order_id = $_GET['id'] ?? '';
if (empty($purchase_order_id)) {
    header("Location: purchase_orders.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get purchase order details with supplier info
$query = "SELECT po.*, s.name as supplier_name, s.contact_person, s.phone, s.email, s.address,
          e.first_name as employee_first_name, e.last_name as employee_last_name
          FROM purchase_orders po 
          LEFT JOIN suppliers s ON po.supplier_id = s.id 
          LEFT JOIN employees e ON po.created_by = e.id 
          WHERE po.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$purchase_order_id]);
$purchase_order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase_order) {
    header("Location: purchase_orders.php");
    exit();
}

// Get purchase order items
$items_query = "SELECT * FROM purchase_order_items WHERE purchase_order_id = ?";
$stmt = $db->prepare($items_query);
$stmt->execute([$purchase_order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ໃບສັ່ງຊື້ສິນຄ້າ - #<?php echo $purchase_order_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --text-color: #1f2937;
            --border-color: #e5e7eb;
            --background-color: #ffffff;
            --header-bg: #f3f4f6;
        }

        body {
            font-family: 'Noto Sans Lao', sans-serif;
            margin: 0;
            padding: 20px;
            color: var(--text-color);
            line-height: 1.6;
            background-color: #f9fafb;
        }

        .invoice-container {
            max-width: 800px;
            margin: auto;
            background: var(--background-color);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .invoice-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .invoice-header h1 {
            color: var(--primary-color);
            font-size: 2em;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .invoice-header h2 {
            color: var(--secondary-color);
            font-size: 1.5em;
            margin-top: 0;
        }

        .header-details {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            margin-bottom: 30px;
        }

        .company-details,
        .supplier-details {
            background: var(--header-bg);
            padding: 20px;
            border-radius: 8px;
            flex: 1;
        }

        .company-details h3,
        .supplier-details h3 {
            color: var(--primary-color);
            margin-top: 0;
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .details-row {
            margin-bottom: 8px;
            display: flex;
        }

        .details-label {
            font-weight: 500;
            min-width: 100px;
        }

        .order-details {
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            background: var(--header-bg);
            padding: 20px;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: var(--background-color);
        }

        th,
        td {
            border: 1px solid var(--border-color);
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .totals {
            float: right;
            width: 300px;
            background: var(--header-bg);
            padding: 20px;
            border-radius: 8px;
        }

        .totals table {
            margin-bottom: 0;
        }

        .totals td {
            border: none;
            padding: 8px 4px;
        }

        .footer {
            clear: both;
            margin-top: 50px;
            text-align: center;
            padding-top: 30px;
            border-top: 2px solid var(--border-color);
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }

        .signature-box {
            flex: 0 0 45%;
            text-align: center;
        }

        .signature-line {
            border-top: 2px solid var(--border-color);
            width: 80%;
            margin: 20px auto 10px;
        }

        @media print {
            body {
                background-color: var(--background-color);
            }

            .invoice-container {
                box-shadow: none;
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }

        .no-print button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Noto Sans Lao', sans-serif;
            font-size: 1em;
            margin: 0 10px;
            transition: background-color 0.3s;
        }

        .no-print button:hover {
            background-color: var(--secondary-color);
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <h1>ໃບສັ່ງຊື້ສິນຄ້າ</h1>
            <h2>PO #<?php echo $purchase_order_id; ?></h2>
        </div>

        <div class="header-details">
            <div class="company-details">
                <h3>ບໍລິສັດ IT Online</h3>
                <div class="details-row">
                    <span class="details-label">ທີ່ຢູ່:</span>
                    <span>ນະຄອນຫຼວງວຽງຈັນ, ລາວ</span>
                </div>
                <div class="details-row">
                    <span class="details-label">ໂທ:</span>
                    <span>020 XXXXXXXX</span>
                </div>
                <div class="details-row">
                    <span class="details-label">ອີເມວ:</span>
                    <span>contact@positonline.com</span>
                </div>
            </div>

            <div class="supplier-details">
                <h3>ຜູ້ສະໜອງ</h3>
                <div class="details-row">
                    <span class="details-label">ຊື່:</span>
                    <span><?php echo htmlspecialchars($purchase_order['supplier_name']); ?></span>
                </div>
                <div class="details-row">
                    <span class="details-label">ຜູ້ຕິດຕໍ່:</span>
                    <span><?php echo htmlspecialchars($purchase_order['contact_person']); ?></span>
                </div>
                <div class="details-row">
                    <span class="details-label">ໂທ:</span>
                    <span><?php echo htmlspecialchars($purchase_order['phone']); ?></span>
                </div>
                <div class="details-row">
                    <span class="details-label">ອີເມວ:</span>
                    <span><?php echo htmlspecialchars($purchase_order['email']); ?></span>
                </div>
                <div class="details-row">
                    <span class="details-label">ທີ່ຢູ່:</span>
                    <span><?php echo nl2br(htmlspecialchars($purchase_order['address'])); ?></span>
                </div>
            </div>
        </div>

        <div class="order-details">
            <p><strong>ວັນທີສັ່ງຊື້:</strong> <?php echo date('d/m/Y', strtotime($purchase_order['created_at'])); ?></p>
            <p><strong>ສະຖານະ:</strong>
                <?php
                $status_labels = [
                    'pending' => 'ລໍຖ້າ',
                    'ordered' => 'ສັ່ງຊື້ແລ້ວ',
                    'received' => 'ໄດ້ຮັບແລ້ວ',
                    'cancelled' => 'ຍົກເລີກ'
                ];
                echo $status_labels[$purchase_order['status']] ?? $purchase_order['status'];
                ?>
            </p>
            <p><strong>ຜູ້ສັ່ງຊື້:</strong> <?php echo htmlspecialchars($purchase_order['employee_first_name'] . ' ' . $purchase_order['employee_last_name']); ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ລ/ດ</th>
                    <th>ລາຍການ</th>
                    <th>ຈໍານວນ</th>
                    <th>ລາຄາຕໍ່ໜ່ວຍ</th>
                    <th>ລາຄາລວມ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                foreach ($order_items as $item): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo number_format($item['quantity']); ?></td>
                        <td><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td><?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <table>
                <?php
                $total_items = 0;
                $sub_total = 0;
                foreach ($order_items as $item) {
                    $total_items += $item['quantity'];
                    $sub_total += $item['total_price'];
                }
                ?>
                <tr>
                    <td><strong>ຈໍານວນລາຍການ:</strong></td>
                    <td><?php echo number_format($total_items); ?></td>
                </tr>
                <tr>
                    <td><strong>ລາຄາລວມທັງໝົດ:</strong></td>
                    <td><?php echo number_format($sub_total, 2); ?> ກີບ</td>
                </tr>
            </table>
        </div>
        <div class="footer">
            <p><strong>ໝາຍເຫດ:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($purchase_order['notes'])); ?></p>

            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <p><strong>ຜູ້ສັ່ງຊື້</strong></p>
                    <p>ວັນທີ: ________________</p>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <p><strong>ຜູ້ອະນຸມັດ</strong></p>
                    <p>ວັນທີ: ________________</p>
                </div>
            </div>
        </div>

        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="window.print()">
                <i class="fas fa-print"></i> ພິມໃບສັ່ງຊື້
            </button>
            <button onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i> ກັບຄືນ
            </button>
        </div>
    </div>
</body>

</html>