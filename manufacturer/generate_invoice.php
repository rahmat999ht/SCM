<?php
require("../includes/config.php");
session_start();

$currentDate = date('Y-m-d');

if (isset($_SESSION['manufacturer_login']) && $_SESSION['manufacturer_login'] == true) {
	if (isset($_GET['id']) && !empty($_GET['id'])) {
		$order_id = $_GET['id'];

		// Query untuk mendapatkan distributor
		$querySelectDistributor = "SELECT dist_id, dist_name FROM distributor";
		$resultDistributor = mysqli_query($con, $querySelectDistributor);

		// Query untuk mendapatkan item pada order
		$query_selectOrderItems = "SELECT products.pro_name, products.pro_price, order_items.quantity AS q
                                   FROM order_items
                                   JOIN products ON order_items.pro_id = products.pro_id
                                   WHERE order_items.order_id = '$order_id'";
		$result_selectOrderItems = mysqli_query($con, $query_selectOrderItems);

		$query_selectOrder = "SELECT date, status, total_amount FROM orders WHERE order_id = '$order_id'";
		$result_selectOrder = mysqli_query($con, $query_selectOrder);
		if (!$result_selectOrder) {
			die("Query failed: " . mysqli_error($con));
		}
		$row_selectOrder = mysqli_fetch_assoc($result_selectOrder);
		if (!$row_selectOrder) {
			die("Order not found for order_id: " . $order_id);
		}

		$query_selectInvoiceId = "SELECT `AUTO_INCREMENT`
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'scm' AND TABLE_NAME = 'invoice'";

		$result_selectInvoiceId = mysqli_query($con, $query_selectInvoiceId);

		// Debug jika query gagal
		if (!$result_selectInvoiceId) {
			die("Query failed: " . mysqli_error($con));
		}

		// Ambil hasil query
		$row_selectInvoiceId = mysqli_fetch_assoc($result_selectInvoiceId);

		// Debug jika hasil kosong
		if (!$row_selectInvoiceId) {
			die("No data found in INFORMATION_SCHEMA for table 'invoice'");
		}

		// Debug untuk menampilkan hasil AUTO_INCREMENT
		// var_dump($row_selectInvoiceId);

		// Ambil nilai AUTO_INCREMENT
		$nextInvoiceId = $row_selectInvoiceId['AUTO_INCREMENT'];

		// Debug nilai AUTO_INCREMENT
		if (!$nextInvoiceId) {
			die("AUTO_INCREMENT value not found");
		}

		echo "Next Invoice ID: " . $nextInvoiceId;


		if (!$row_selectOrder || !$row_selectInvoiceId) {
			echo "Order or Invoice information not found.";
			exit;
		}
	} else {
		echo "Order ID not provided.";
		exit;
	}
} else {
	header('Location: ../index.php');
	exit;
}
?>

<!DOCTYPE html>
<html>

<head>
	<title>View Orders</title>
	<link rel="stylesheet" href="../includes/main_style.css">
</head>

<body>
	<?php
	include("../includes/header.inc.php");
	include("../includes/nav_manufacturer.inc.php");
	include("../includes/aside_manufacturer.inc.php");
	?>
	<section>
		<h1>Invoice Summary</h1>
		<table class="table_infoFormat">
			<tr>
				<td>Invoice No:</td>
				<td><?php echo $row_selectInvoiceId['AUTO_INCREMENT']; ?></td>
			</tr>
			<tr>
				<td>Invoice Date:</td>
				<td><?php echo date('d-m-Y'); ?></td>
			</tr>
			<tr>
				<td>Order No:</td>
				<td><?php echo $order_id; ?></td>
			</tr>
			<tr>
				<td>Order Date:</td>
				<td><?php echo date("d-m-Y", strtotime($row_selectOrder['date'])); ?></td>
			</tr>
		</table>
		<form action="insert_invoice.php" method="POST" class="form">
			<input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
			<table class="table_invoiceFormat">
				<tr>
					<th>Products</th>
					<th>Unit Price</th>
					<th>Quantity</th>
					<th>Amount</th>
				</tr>
				<?php
				$grandTotal = 0;
				while ($row_selectOrderItems = mysqli_fetch_assoc($result_selectOrderItems)) {
					$amount = $row_selectOrderItems['q'] * $row_selectOrderItems['pro_price'];
					$grandTotal += $amount;
				?>
					<tr>
						<td><?php echo $row_selectOrderItems['pro_name']; ?></td>
						<td><?php echo number_format($row_selectOrderItems['pro_price'], 2); ?></td>
						<td><?php echo $row_selectOrderItems['q']; ?></td>
						<td><?php echo number_format($amount, 2); ?></td>
					</tr>
				<?php } ?>
				<tr style="height:40px;vertical-align:bottom;">
					<td colspan="3" style="text-align:right;">Grand Total:</td>
					<td><?php echo number_format($grandTotal, 2); ?></td>
				</tr>
			</table>
			<br />
			Ship via: &nbsp;&nbsp;&nbsp;&nbsp;
			<select name="distributor" required>
				<option value="" disabled selected>--- Select Distributor ---</option>
				<?php while ($rowSelectDistributor = mysqli_fetch_assoc($resultDistributor)) { ?>
					<option value="<?php echo $rowSelectDistributor['dist_id']; ?>">
						<?php echo $rowSelectDistributor['dist_name']; ?>
					</option>
				<?php } ?>
			</select>
			<br /><br />
			Comments:
			<textarea maxlength="400" name="txtComment" rows="5" cols="30"></textarea>
			<br />
			<input type="submit" value="Generate Invoice" class="submit_button">
			<span class="error_message">
				<?php
				if (isset($_SESSION['error'])) {
					echo $_SESSION['error'];
					unset($_SESSION['error']);
				}
				?>
			</span>
		</form>
	</section>
	<?php include("../includes/footer.inc.php"); ?>
</body>

</html>