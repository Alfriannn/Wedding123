<?php
session_start();
require '../firebase/firebase.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit();
}

// Get export parameters
$format = $_GET['format'] ?? 'csv';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Get orders data from Firebase
$ordersRef = $firebase->getReference('orders');
$orders = $ordersRef->getValue();

// Filter by date if provided
if (!empty($dateFrom) || !empty($dateTo)) {
    $orders = array_filter($orders, function($order) use ($dateFrom, $dateTo) {
        $orderDate = strtotime($order['order_date']);
        
        $fromCondition = true;
        if (!empty($dateFrom)) {
            $fromTimestamp = strtotime($dateFrom);
            $fromCondition = $orderDate >= $fromTimestamp;
        }
        
        $toCondition = true;
        if (!empty($dateTo)) {
            $toTimestamp = strtotime($dateTo . ' 23:59:59');
            $toCondition = $orderDate <= $toTimestamp;
        }
        
        return $fromCondition && $toCondition;
    });
}

// Prepare data for export
$exportData = [];
foreach ($orders as $id => $order) {
    $exportData[] = [
        'order_id' => $id,
        'theme_name' => $order['theme_name'],
        'groom_name' => $order['groom_name'],
        'bride_name' => $order['bride_name'],
        'wedding_date' => $order['wedding_date'],
        'wedding_time' => $order['wedding_time'],
        'wedding_venue' => $order['wedding_venue'],
        'venue_address' => $order['venue_address'],
        'phone_number' => $order['phone_number'],
        'email' => $order['email'],
        'order_date' => $order['order_date'],
        'status' => $order['status'] ?? 'pending'
    ];
}

// Export data based on format
if ($format === 'csv') {
    // CSV export
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=orders_export_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel opening
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add header row
    fputcsv($output, array_keys($exportData[0]));
    
    // Add data rows
    foreach ($exportData as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
} elseif ($format === 'excel') {
    // For Excel, we'll use a simple XML format that Excel can open
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=orders_export_' . date('Y-m-d') . '.xls');
    
    echo '<!DOCTYPE html>';
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '</head>';
    echo '<body>';
    echo '<table border="1">';
    
    // Header row
    echo '<tr>';
    foreach (array_keys($exportData[0]) as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';
    
    // Data rows
    foreach ($exportData as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit;
}

// Default redirect if format not supported
header('Location: orders.php');
exit;