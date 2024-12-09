<?php
error_reporting(0);
require("../includes/config.php");
session_start();

if (isset($_SESSION['manufacturer_login'])) {
    $id = $_GET['id'];

    // Inisialisasi array kosong
    $availProId = [];
    $availQuantity = [];
    $orderProId = [];
    $orderQuantity = [];

    // Query untuk mendapatkan stok tersedia
    $queryAvailQuantity = "
        SELECT products.pro_id AS pro_id, products.quantity AS quantity 
        FROM order_items, products 
        WHERE products.pro_id = order_items.pro_id 
          AND order_items.order_id = '$id' 
          AND products.quantity IS NOT NULL";
    $resultAvailQuantity = mysqli_query($con, $queryAvailQuantity);

    // Query untuk mendapatkan jumlah pesanan
    $queryOrderQuantity = "
        SELECT quantity AS q, pro_id AS p 
        FROM order_items 
        WHERE order_id = '$id'";
    $resultOrderQuantity = mysqli_query($con, $queryOrderQuantity);

    // Simpan data stok tersedia ke array
    while ($rowAvailQuantity = mysqli_fetch_array($resultAvailQuantity)) {
        $availProId[] = $rowAvailQuantity['pro_id'];
        $availQuantity[] = $rowAvailQuantity['quantity'];
    }

    // Simpan data jumlah pesanan ke array
    while ($rowOrderQuantity = mysqli_fetch_array($resultOrderQuantity)) {
        $orderProId[] = $rowOrderQuantity['p'];
        $orderQuantity[] = $rowOrderQuantity['q'];
    }

    // Hitung dan update stok produk
    foreach (array_combine($orderProId, $orderQuantity) as $p => $q) {
        foreach (array_combine($availProId, $availQuantity) as $proId => $quantity) {
            if ($p == $proId) {
                $total = $quantity - $q;
                if ($total >= 0) {
                    $queryUpdateQuantity = "UPDATE products SET quantity = '$total' WHERE pro_id = '$proId'";
                    $result = mysqli_query($con, $queryUpdateQuantity);
                }
            }
        }
    }

    // Cek apakah stok cukup
    if (!isset($result) || !$result) {
        echo "<script> alert(\"You don't have enough stock to approve this order\"); </script>";
        header("refresh:0;url=view_orders.php");
    } else {
        // Konfirmasi pesanan
        $queryConfirm = "UPDATE orders SET approved = 1 WHERE order_id = '$id'";
        if (mysqli_query($con, $queryConfirm)) {
            echo "<script> alert(\"Order has been confirmed\"); </script>";
            header("refresh:0;url=view_orders.php");
        } else {
            echo "<script> alert(\"There was some issue in approving order.\"); </script>";
            header("refresh:0;url=view_orders.php");
        }
    }
} else {
    header('Location:../index.php');
}
?>
