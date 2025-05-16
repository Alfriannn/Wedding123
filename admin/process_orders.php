<?php
require '../firebase/firebase.php';

// Validate if the form was submitted
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ../index.php');
    exit();
}

// Get form data
$themeId = $_POST['theme_id'] ?? '';
$themeName = $_POST['theme_name'] ?? '';
$groomName = $_POST['groom_name'] ?? '';
$brideName = $_POST['bride_name'] ?? '';
$weddingDate = $_POST['wedding_date'] ?? '';
$weddingTime = $_POST['wedding_time'] ?? '';
$weddingVenue = $_POST['wedding_venue'] ?? '';
$venueAddress = $_POST['venue_address'] ?? '';
$phoneNumber = $_POST['phone_number'] ?? '';
$email = $_POST['email'] ?? '';
$additionalInfo = $_POST['additional_info'] ?? '';

// Validate required fields
if (empty($themeId) || empty($themeName) || empty($groomName) || empty($brideName) 
    || empty($weddingDate) || empty($weddingTime) || empty($weddingVenue) 
    || empty($venueAddress) || empty($phoneNumber) || empty($email)) {
    
    header('Location: ../index.php?error=incomplete_data');
    exit();
}

// Prepare order data
$orderData = [
    'theme_id' => $themeId,
    'theme_name' => $themeName,
    'groom_name' => $groomName,
    'bride_name' => $brideName,
    'wedding_date' => $weddingDate,
    'wedding_time' => $weddingTime,
    'wedding_venue' => $weddingVenue,
    'venue_address' => $venueAddress,
    'phone_number' => $phoneNumber,
    'email' => $email,
    'additional_info' => $additionalInfo,
    'order_date' => date('Y-m-d H:i:s'),
    'status' => 'pending'
];

try {
    // Save order data to Firebase
    $ordersRef = $firebase->getReference('orders');
    $newOrderRef = $ordersRef->push($orderData);
    
    if ($newOrderRef->getKey()) {
        header('Location: thank_you.php?order_id=' . $newOrderRef->getKey());
        exit();
    } else {
        throw new Exception("Failed to save order data");
    }
} catch (Exception $e) {
    header('Location: ../index.php?error=save_failed&message=' . urlencode($e->getMessage()));
    exit();
}