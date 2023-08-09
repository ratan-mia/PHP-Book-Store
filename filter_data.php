<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connection.php';

$response = [
    "status" => "error",
    "message" => "",
    "results" => "",
    "totalPrice" => 0
];

try {


    // if (isset($_POST['filter'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $whereClauses = [];
        $params = [];
        $types = '';

        if (!empty($_POST['customer'])) {
            $whereClauses[] = "customers.name = ?";
            $params[] = $_POST['customer'];
            $types .= 's';
        }

        if (!empty($_POST['product'])) {
            $whereClauses[] = "products.title = ?";
            $params[] = $_POST['product'];
            $types .= 's';
        }

        if (!empty($_POST['price'])) {
            $whereClauses[] = "products.price <= ?";
            $params[] = (float)$_POST['price'];
            $types .= 'd';
        }

        $query = "SELECT customers.name as customer_name, products.title as product_title, products.price 
                  FROM sales 
                  JOIN customers ON sales.customer_id = customers.id 
                  JOIN products ON sales.product_id = products.id";

        if (count($whereClauses) > 0) {
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $stmt = $mysqli->prepare($query);

        if (!$stmt) {
            throw new Exception("Error preparing the query: " . $mysqli->error);
        }

        if (!empty($types) && !empty($params)) {
            if (!$stmt->bind_param($types, ...$params)) {
                throw new Exception("Error binding parameters: " . $stmt->error);
            }
        }

        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $totalPrice = 0;
        ob_start();
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['customer_name']}</td>
                <td>{$row['product_title']}</td>
                <td>{$row['price']}</td>
            </tr>";
            $totalPrice += $row['price'];
        }
        echo "<tr>
            <td colspan='2'><strong>Total Price</strong></td>
            <td><strong>" . number_format($totalPrice, 2) . "</strong></td>
        </tr>";

        $response["results"] = ob_get_clean();
        $response["totalPrice"] = number_format($totalPrice, 2);
        $response["status"] = "success";

        $stmt->close();
    }
} catch (Exception $e) {
    $response["message"] = "An error occurred: " . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
