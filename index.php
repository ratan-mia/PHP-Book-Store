<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';

$imported = false; // Indicates if data is already imported
// Check if sales data exists in the database
$query = "SELECT COUNT(*) as count FROM sales";
$count_result = $mysqli->query($query);
$count_data = $count_result->fetch_assoc();
if ($count_data['count'] > 0) {
    $imported = true;
}


$customers = $mysqli->query("SELECT DISTINCT name FROM customers")->fetch_all(MYSQLI_ASSOC);
$products = $mysqli->query("SELECT DISTINCT title FROM products")->fetch_all(MYSQLI_ASSOC);

// Fetching all sales data
$allSales = $mysqli->query("
    SELECT customers.name as customer_name, products.title as product_title, products.price
    FROM sales
    JOIN customers ON sales.customer_id = customers.id
    JOIN products ON sales.product_id = products.id
")->fetch_all(MYSQLI_ASSOC);

// Fetch all sales data with product prices and calculate total price
$sales = $mysqli->query("
    SELECT customers.name as customer_name, products.title as product_title, products.price as price 
    FROM sales 
    JOIN customers ON sales.customer_id = customers.id 
    JOIN products ON sales.product_id = products.id
")->fetch_all(MYSQLI_ASSOC);

$totalPrice = 0;
foreach ($sales as $sale) {
    $totalPrice += $sale['price'];
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

        <?php if (!$imported) : ?>
            <button id="importBtn" class="btn btn-success mb-4">Import Data</button>
        <?php else : ?>
            <form id="filterForm" method="POST">
                <div class="form-group">
                    <label for="customer">Customer</label>
                    <select name="customer" class="form-control" id="customer">
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $customer) : ?>
                            <option value="<?= $customer['name'] ?>"><?= $customer['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="product">Product</label>
                    <select name="product" class="form-control" id="product">
                        <option value="">-- Select Product --</option>
                        <?php foreach ($products as $product) : ?>
                            <option value="<?= $product['title'] ?>"><?= $product['title'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="price">Max Price</label>
                    <input type="number" class="form-control" name="price" id="price">
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
                <tbody id="resultsBody">
                    <?php foreach ($allSales as $sale) : ?>
                        <tr>
                            <td><?= htmlspecialchars($sale['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($sale['product_title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($sale['price'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="2"><strong>Total Price</strong></td>
                        <td><strong><?= $totalPrice ?></strong></td>
                    </tr>
                </tbody>

            </table>

        <?php endif; ?>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#importBtn").click(function() {
                $.ajax({
                    type: "POST",
                    url: "import_data.php",
                    data: {
                        import: true
                    },
                    beforeSend: function() {
                        $("#importBtn").prop("disabled", true).text("Importing...");
                    },
                    success: function(data) {
                        console.log(data);
                        if (data.status === "success") {
                            location.reload();
                        } else {
                            alert(data.message);
                            $("#importBtn").prop("disabled", false).text("Import Data");
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("Error importing data: " + error);
                        $("#importBtn").prop("disabled", false).text("Import Data");
                    }
                });
            });

            $("#filterForm").submit(function(e) {
                e.preventDefault();
                let formData = $(this).serialize();

                console.log(formData);

                $.ajax({
                    type: "POST",
                    url: "filter_data.php",
                    data: formData,
                    beforeSend: function() {
                        $("button[type='submit']").prop("disabled", true).text("Filtering...");
                    },
                    success: function(data) {
                        console.log(data);
                        if (data.status === "success") {
                            // Inserting the results into the table
                            $("#resultsBody").html(data.results);

                            // Appending the total price to the table
                            //             let totalPriceRow = `<tr>
                            //     <td colspan="2"><strong>Total Price:</strong></td>
                            //     <td><strong>${data.totalPrice}</strong></td>
                            // </tr>`;
                            //             $("#resultsBody").append(totalPriceRow);

                        } else {
                            alert("Server Response: " + JSON.stringify(data));

                        }
                        $("button[type='submit']").prop("disabled", false).text("Filter");
                    },
                    error: function(xhr, status, error) {
                        alert("Error fetching data: " + error + "\n" + xhr.responseText);
                        $("button[type='submit']").prop("disabled", false).text("Filter");
                    }
                });
            });

        });
    </script>
</body>

</html>