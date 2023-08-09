<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connection.php';
include 'VersionTimezoneAdjuster.php';

$response = ["status" => "error", "message" => ""];

// Ensure $_POST['import'] is set to process the import request
if (isset($_POST['import'])) {

    // Decode the JSON data
    $jsonData = json_decode(file_get_contents('sales_data.json'), true);

    if (!$jsonData) {
        $response["message"] = "Error decoding the JSON data.";
        sendResponse($response);
    }

    // Begin a transaction
    $mysqli->begin_transaction();

    foreach ($jsonData as $data) {
        $saleDate = VersionTimezoneAdjuster::adjustTimestamp($data['sale_date'], $data['version']);

        // Insert or get customer
        $stmt = $mysqli->prepare("INSERT IGNORE INTO customers (name, email) VALUES (?, ?)");
        if (!$stmt) {
            $response["message"] = "Error preparing customer insert: " . $mysqli->error;
            rollbackTransaction();
            sendResponse($response);
        }
        $stmt->bind_param('ss', $data['customer_name'], $data['customer_mail']);
        $stmt->execute();
        $stmt->close();

        // $customerId = $mysqli->query("SELECT id FROM customers WHERE email = '{$data['customer_mail']}'")->fetch_assoc()['id'];


        // Get the Customer ID

        $stmt = $mysqli->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->bind_param("s", $data['customer_mail']);
        $stmt->execute();
        $result = $stmt->get_result();
        $customerId = $result->fetch_assoc()['id'];
        $stmt->close();



        // Insert or get product
        $stmt = $mysqli->prepare("INSERT IGNORE INTO products (title, price) VALUES (?, ?)");
        if (!$stmt) {
            $response["message"] = "Error preparing product insert: " . $mysqli->error;
            rollbackTransaction();
            sendResponse($response);
        }
        $stmt->bind_param('sd', $data['product_name'], $data['product_price']);
        $stmt->execute();
        $stmt->close();

        // $productId = $mysqli->query("SELECT id FROM products WHERE title = '{$data['product_name']}'")->fetch_assoc()['id'];


        // Get the Product ID
        $stmt = $mysqli->prepare("SELECT id FROM products WHERE title = ?");
        $stmt->bind_param("s", $data['product_name']);
        $stmt->execute();
        $result = $stmt->get_result();
        $productId = $result->fetch_assoc()['id'];
        $stmt->close();


        // Insert sale
        $stmt = $mysqli->prepare("INSERT INTO sales (customer_id, product_id, sale_date, version) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $response["message"] = "Error preparing sale insert: " . $mysqli->error;
            rollbackTransaction();
            sendResponse($response);
        }
        $stmt->bind_param('iiss', $customerId, $productId, $saleDate, $data['version']);
        $stmt->execute();
        $stmt->close();
    }

    // If we reach here, everything went well. Commit the transaction.
    $mysqli->commit();

    $response["status"] = "success";
    $response["message"] = "Data imported successfully.";
}

// Send response and exit
sendResponse($response);

// Functions
function rollbackTransaction()
{
    global $mysqli;
    $mysqli->rollback();
}

function sendResponse($response)
{
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
