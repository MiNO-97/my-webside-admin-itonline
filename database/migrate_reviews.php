<?php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if reviews table exists
$tableExists = $db->query("SHOW TABLES LIKE 'reviews'")->rowCount() > 0;

if (!$tableExists) {
    // Create reviews table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT,
        product_id INT,
        order_id INT,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id),
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (order_id) REFERENCES orders(id)
    )";

    $db->exec($sql);
    echo "Reviews table created successfully\n";
}

// Get some customer IDs
$stmt = $db->query("SELECT id FROM customers LIMIT 5");
$customers = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get some product IDs
$stmt = $db->query("SELECT id FROM products LIMIT 5");
$products = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get some order IDs
$stmt = $db->query("SELECT id FROM orders LIMIT 5");
$orders = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($customers) && !empty($products) && !empty($orders)) {
    // Sample reviews data
    $reviews = [
        [
            'rating' => 5,
            'comment' => 'ສິນຄ້າດີຫຼາຍ, ຄຸນນະພາບດີ',
            'status' => 'approved'
        ],
        [
            'rating' => 4,
            'comment' => 'ການບໍລິການດີ, ແຕ່ການຈັດສົ່ງຊ້າໜ້ອຍໜຶ່ງ',
            'status' => 'approved'
        ],
        [
            'rating' => 3,
            'comment' => 'ສິນຄ້າພໍໃຊ້ໄດ້',
            'status' => 'pending'
        ],
        [
            'rating' => 5,
            'comment' => 'ສິນຄ້າມາຄົບຖ້ວນ, ຈັດສົ່ງໄວ',
            'status' => 'approved'
        ],
        [
            'rating' => 2,
            'comment' => 'ສິນຄ້າບໍ່ຄືກັບຮູບ',
            'status' => 'rejected'
        ]
    ];

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO reviews (customer_id, product_id, order_id, rating, comment, status) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($reviews as $i => $review) {
            $stmt->execute([
                $customers[array_rand($customers)],
                $products[array_rand($products)],
                $orders[array_rand($orders)],
                $review['rating'],
                $review['comment'],
                $review['status']
            ]);
        }

        $db->commit();
        echo "Sample reviews added successfully\n";
    } catch (PDOException $e) {
        $db->rollBack();
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Error: No customers, products, or orders found in the database\n";
}
