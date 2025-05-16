<?php
require __DIR__ . '/../rdb-firebase/vendor/autoload.php';

use Kreait\Firebase\Factory;

// Path ke file kredensial JSON
$firebase = (new Factory)
    ->withServiceAccount(__DIR__ . '/../firebase/firebase-credentials.json')  // Menggunakan file JSON langsung
    ->withDatabaseUri('https://wedding2-ffa73-default-rtdb.asia-southeast1.firebasedatabase.app/')
    ->createDatabase();
?>
