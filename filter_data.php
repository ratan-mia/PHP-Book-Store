<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
file_put_contents("log.txt", print_r($_POST, true), FILE_APPEND);


include 'db_connection.php';

$response = ["status" => "error", "message" => "", "results" => "", "totalPrice" => 0];

if (isset($_POST['filter'])) {
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

    if ($stmt = $mysqli->prepare($query)) {
        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if ($stmt->execute()) {
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
                <td><strong>{$totalPrice}</strong></td>
            </tr>";

            $response["results"] = ob_get_clean();
            $response["totalPrice"] = $totalPrice;
            $response["status"] = "success";
        } else {
            $response["message"] = "Error executing query: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $response["message"] = "Error preparing the query: " . $mysqli->error;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
