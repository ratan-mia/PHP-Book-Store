<?php
include 'db_connection.php';

class VersionTimezoneAdjuster
{
    public static function adjustTimestamp($timestamp, $version)
    {
        if (version_compare($version, '1.0.17+60', '<')) {
            $datetime = new DateTime($timestamp, new DateTimeZone('UTC'));
            $datetime->setTimezone(new DateTimeZone('Europe/Berlin'));
            return $datetime->format('Y-m-d H:i:s');
        }
        return $timestamp;
    }
}

$results = [];
$imported = false;
$errorMsg = '';

// Check if sales data exists in the database
$query = "SELECT COUNT(*) as count FROM sales";
if ($result = $mysqli->query($query)) {
    $data = $result->fetch_assoc();
    if ($data['count'] > 0) {
        $imported = true;
    }
} else {
    $errorMsg = "Database query failed: " . $mysqli->error;
}

// Import data logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$imported && isset($_POST['import'])) {
        $jsonData = json_decode(file_get_contents('/data/sales_data.json'), true);
        foreach ($jsonData as $data) {
            $saleDate = VersionTimezoneAdjuster::adjustTimestamp($data['sale_date'], $data['version']);

            // Insert or get customer
            $stmt = $mysqli->prepare("INSERT IGNORE INTO customers (name, email) VALUES (?, ?)");
            if (!$stmt) {
                $errorMsg = "Error preparing customer insert: " . $mysqli->error;
                break;
            }
            $stmt->bind_param('ss', $data['customer_name'], $data['customer_mail']);
            $stmt->execute();
            $stmt->close();

            $customerId = $mysqli->query("SELECT id FROM customers WHERE email = '{$data['customer_mail']}'")->fetch_assoc()['id'];

            // Insert or get product
            $stmt = $mysqli->prepare("INSERT IGNORE INTO products (title, price) VALUES (?, ?)");
            if (!$stmt) {
                $errorMsg = "Error preparing product insert: " . $mysqli->error;
                break;
            }
            $stmt->bind_param('sd', $data['product_name'], $data['product_price']);
            $stmt->execute();
            $stmt->close();

            $productId = $mysqli->query("SELECT id FROM products WHERE title = '{$data['product_name']}'")->fetch_assoc()['id'];

            // Insert sale
            $stmt = $mysqli->prepare("INSERT INTO sales (customer_id, product_id, sale_date, version) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                $errorMsg = "Error preparing sale insert: " . $mysqli->error;
                break;
            }
            $stmt->bind_param('iiss', $customerId, $productId, $saleDate, $data['version']);
            $stmt->execute();
            $stmt->close();
        }

        if (empty($errorMsg)) {
            $imported = true;
        }
    }

    // Filtering logic
    if ($imported && isset($_POST['filter'])) {
        $customer = $_POST['customer'] ?? '';
        $product = $_POST['product'] ?? '';
        $price = $_POST['price'] ?? '';

        $query = "SELECT c.name as customer_name, p.title as product_title, p.price
                  FROM sales s
                  JOIN customers c ON s.customer_id = c.id
                  JOIN products p ON s.product_id = p.id
                  WHERE c.name LIKE ? AND p.title LIKE ? AND p.price <= ?";

        $stmt = $mysqli->prepare($query);
        $customerParam = '%' . $customer . '%';
        $productParam = '%' . $product . '%';
        $priceParam = $price ? (float) $price : PHP_INT_MAX;

        $stmt->bind_param('ssd', $customerParam, $productParam, $priceParam);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        } else {
            $errorMsg = "Error filtering results: " . $stmt->error;
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Bookstore Sales</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-5">
        <?php if (!empty($errorMsg)) : ?>
            <div class="alert alert-danger" role="alert">
                <?= $errorMsg ?>
            </div>
        <?php endif; ?>

        <?php if (!$imported) : ?>
            <form method="POST">
                <input type="submit" name="import" value="Import Data" class="btn btn-success mb-4">
            </form>
        <?php else : ?>
            <form method="POST">
                <div class="form-group">
                    <label for="customer">Customer</label>
                    <input type="text" class="form-control" name="customer" id="customer" value="<?php echo $_POST['customer'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="product">Product</label>
                    <input type="text" class="form-control" name="product" id="product" value="<?php echo $_POST['product'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="price">Max Price</label>
                    <input type="number" class="form-control" name="price" id="price" value="<?php echo $_POST['price'] ?? ''; ?>">
                </div>
                <button type="submit" name="filter" class="btn btn-primary">Filter</button>
            </form>

            <table class="table mt-4">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    foreach ($results as $row) {
                        $total += $row['price'];
                        echo "<tr>
                        <td>{$row['customer_name']}</td>
                        <td>{$row['product_title']}</td>
                        <td>{$row['price']}</td>
                    </tr>";
                    }
                    ?>
                    <tr>
                        <td colspan="2"><strong>Total</strong></td>
                        <td><strong><?php echo number_format($total, 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
            // AJAX for importing data
            $("#importBtn").click(function() {
                $.ajax({
                    type: "POST",
                    url: "index.php",
                    data: {
                        import: true
                    },
                    beforeSend: function() {
                        $("#importBtn").prop("disabled", true).text("Importing...");
                    },
                    success: function(data) {
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert("Error importing data: " + error);
                        $("#importBtn").prop("disabled", false).text("Import Data");
                    }
                });
            });

            // AJAX for filtering data
            $("#filterForm").submit(function(e) {
                e.preventDefault();
                let formData = $(this).serialize();
                $.ajax({
                    type: "POST",
                    url: "filter_data.php",
                    data: formData,
                    beforeSend: function() {
                        // You can add a loader here, for now just disabling the submit button
                        $("button[type='submit']").prop("disabled", true).text("Filtering...");
                    },
                    success: function(data) {
                        if (data.includes("Error")) {
                            alert(data);
                        } else {
                            $("#resultsBody").html(data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("Error fetching data: " + error);
                    },
                    complete: function() {
                        $("button[type='submit']").prop("disabled", false).text("Filter");
                    }
                });
            });
        });






        //         $.ajax({
        //     url: 'filter_data.php',
        //     method: 'POST',
        //     data: {
        //         customer: $("#customer").val(),
        //         product: $("#product").val(),
        //         price: $("#price").val()
        //     },
        //     dataType: 'json',
        //     success: function(response) {
        //         if (response.success) {
        //             // ... existing code to populate table ...
        //         } else {
        //             alert(response.error);
        //         }
        //     },
        //     error: function(jqXHR, textStatus, errorThrown) {
        //         alert("AJAX error: " + textStatus + ' : ' + errorThrown);
        //     }
        // });
    </script>

</body>

</html>