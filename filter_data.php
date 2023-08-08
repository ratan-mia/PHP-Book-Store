<?php
include 'db_connection.php';
// include 'VersionTimezoneAdjuster.php';

$customer = $_POST['customer'] ?? '';
$product = $_POST['product'] ?? '';
$price = $_POST['price'] ?? '';

$response = ['success' => true, 'data' => [], 'error' => ''];

try {
    $query = "SELECT c.name as customer_name, p.title as product_name, p.price as product_price 
    FROM sales s 
    JOIN customers c ON s.customer_id = c.id
    JOIN products p ON s.product_id = p.id
    WHERE c.name LIKE ? AND p.title LIKE ? AND p.price <= ?";

    $stmt = $mysqli->prepare($query);
    $likeCustomer = '%' . $customer . '%';
    $likeProduct = '%' . $product . '%';

    $stmt->bind_param('ssd', $likeCustomer, $likeProduct, $price);
    $stmt->execute();
    $result = $stmt->get_result();

    $totalPrice = 0;
    while ($row = $result->fetch_assoc()) {
        $totalPrice += $row['product_price'];
        echo "<tr>";
        echo "<td>" . $row['customer_name'] . "</td>";
        echo "<td>" . $row['product_name'] . "</td>";
        echo "<td>" . $row['product_price'] . "</td>";
        echo "</tr>";
    }
    echo "<tr>";
    echo "<td colspan='2'>Total Price</td>";
    echo "<td>" . $totalPrice . "</td>";
    echo "</tr>";

    $stmt->close();
    $mysqli->close();

    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = "Error: " . $e->getMessage();
}

echo json_encode($response);
