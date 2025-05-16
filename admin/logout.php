<?php
session_start();
session_destroy();  // Hapus session admin
header('Location: login.php');  // Arahkan kembali ke halaman login
exit();
