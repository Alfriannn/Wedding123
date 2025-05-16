<?php
session_start();
require '../firebase/firebase.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit();
}

// Get orders data from Firebase
$ordersRef = $firebase->getReference('orders');
$orders = $ordersRef->getValue();

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = strtolower($_GET['search']);
    $orders = array_filter($orders, function($order) use ($searchTerm) {
        return strpos(strtolower($order['groom_name']), $searchTerm) !== false ||
               strpos(strtolower($order['bride_name']), $searchTerm) !== false ||
               strpos(strtolower($order['theme_name']), $searchTerm) !== false;
    });
}

// Handle status update
if (isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];
    
    try {
        $orderRef = $firebase->getReference('orders/' . $orderId);
        $orderRef->update([
            'status' => $newStatus
        ]);
        header('Location: orders.php?status=updated');
        exit();
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// Handle order deletion
if (isset($_POST['delete_order'])) {
    $orderId = $_POST['order_id'];
    
    try {
        $orderRef = $firebase->getReference('orders/' . $orderId);
        $orderRef->remove();
        header('Location: orders.php?status=deleted');
        exit();
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// Pagination
$itemsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

if (is_array($orders)) {
    $totalItems = count($orders);
    $orders = array_slice($orders, $offset, $itemsPerPage);
} else {
    $orders = [];
    $totalItems = 0;
}

$totalPages = ceil($totalItems / $itemsPerPage);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan | Wedding Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Gunakan style yang sama seperti di index.php -->
    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #0b5ed7;
            --primary-light: #cfe2ff;
            --secondary: #6c757d;
            --dark: #212529;
            --light: #f8f9fa;
            --info: #0dcaf0;
            --white: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --blue-gradient: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            --border-radius: 10px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
        }
        
        /* Sidebar */
        .sidebar {
            background: var(--blue-gradient);
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 0;
            transition: all 0.3s;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand h3 {
            margin: 0;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .sidebar-brand i {
            margin-right: 10px;
            color: white;
            font-size: 1.75rem;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
            list-style: none;
            margin: 0;
        }
        
        .sidebar-menu li {
            position: relative;
            margin: 0;
            padding: 0;
        }
        
        .sidebar-menu li a {
            padding: 1rem 1.5rem;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 1rem;
            font-weight: 400;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu li a:hover, 
        .sidebar-menu li a.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: white;
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
            font-size: 1.2rem;
            vertical-align: middle;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: all 0.3s;
        }
        
        .top-bar {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .top-bar .breadcrumb {
            margin: 0;
            background: transparent;
            padding: 0;
        }
        
        .top-bar .breadcrumb-item,
        .top-bar .breadcrumb-item a {
            color: var(--secondary);
            font-size: 0.9rem;
        }
        
        .top-bar .breadcrumb-item.active {
            color: var(--primary);
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            color: var(--primary);
        }
        
        /* Theme Info Card */
        .theme-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .theme-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .theme-card .card-body {
            padding: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .theme-card .icon-box {
            width: 60px;
            height: 60px;
            background: var(--primary-light);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .theme-card .bi {
            color: var(--primary);
            font-size: 1.8rem;
        }
        
        .theme-card .theme-content h2 {
            margin-bottom: 0.2rem;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: capitalize;
        }
        
        .theme-card .theme-content p {
            margin: 0;
            color: var(--secondary);
            font-size: 0.9rem;
        }
        
        .theme-card .overlay-icon {
            position: absolute;
            top: -15px;
            right: -15px;
            font-size: 5rem;
            color: rgba(13, 110, 253, 0.05);
            transform: rotate(-15deg);
        }
        
        /* Data Cards */
        .data-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .data-card .card-header {
            background-color: var(--white);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .data-card .card-header h4 {
            margin: 0;
            color: var(--primary);
            font-weight: 600;
            font-size: 1.35rem;
        }
        
        .data-card .card-header .header-icon {
            width: 35px;
            height: 35px;
            background-color: var(--primary-light);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }
        
        .data-card .card-header .header-icon i {
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .data-card .card-header .title-wrapper {
            display: flex;
            align-items: center;
        }
        
        .data-card .card-body {
            padding: 1.5rem;
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 2px 5px rgba(13, 110, 253, 0.2);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
        }
        
        .btn-outline-primary {
            border: 1px solid var(--primary);
            color: var(--primary);
            background-color: transparent;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }
        
        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
        }
        
        /* Table */
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .custom-table thead th {
            background-color: rgba(13, 110, 253, 0.05);
            color: var(--primary);
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border: none;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .custom-table thead th:first-child {
            border-top-left-radius: var(--border-radius);
        }
        
        .custom-table thead th:last-child {
            border-top-right-radius: var(--border-radius);
        }
        
        .custom-table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            font-size: 0.95rem;
        }
        
        .custom-table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.03);
        }
        
        /* Badges */
        .badge-primary {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 500;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
        }
        
        .badge-success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
            font-weight: 500;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
        }
        
        /* Alert */
        .custom-alert {
            border-radius: var(--border-radius);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: none;
            display: flex;
            align-items: center;
            background-color: rgba(13, 110, 253, 0.1);
            color: var(--primary);
        }
        
        .custom-alert i {
            font-size: 1.2rem;
            margin-right: 0.75rem;
        }
        
        /* Search */
        .search-container {
            position: relative;
            max-width: 400px;
            margin-bottom: 1.5rem;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 50px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
        }
        
        .search-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        
        /* Media Queries */
        @media (max-width: 991.98px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar-brand h3 {
                display: none;
            }
            
            .sidebar-brand i {
                margin-right: 0;
            }
            
            .sidebar-menu li a span {
                display: none;
            }
            
            .sidebar-menu li a i {
                margin-right: 0;
                font-size: 1.5rem;
            }
            
            .sidebar-footer {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
        
        @media (max-width: 767.98px) {
            .sidebar {
                width: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .sidebar.active {
                width: 250px;
            }
        }
        
        /* Toggle button for mobile */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary);
            cursor: pointer;
        }
        
        @media (max-width: 767.98px) {
            .mobile-toggle {
                display: block;
            }
        }
        
        /* Mobile overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Sidebar (sama seperti di index.php) -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-person-circle me-2 text-white"></i>
            <h3>Wedding Admin</h3>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="index.php">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="orders.php" class="active">
                    <i class="bi bi-cart-fill"></i>
                    <span>Pesanan</span>
                </a>
            </li>
            <li>
                <a href="rsvp.php">
                    <i class="bi bi-envelope-fill"></i>
                    <span>Kehadiran Tamu</span>
                </a>
            </li>
            <li>
                <a href="guestbook.php">
                    <i class="bi bi-book-fill"></i>
                    <span>Ucapan Tamu</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            &copy; <?= date('Y') ?> Wedding Admin
        </div>
    </div>
    
    <!-- Overlay for mobile -->
    <div class="overlay"></div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="d-flex align-items-center">
                <button class="mobile-toggle me-3">
                    <i class="bi bi-list"></i>
                </button>
                <h1>Manajemen Pesanan</h1>
            </div>
            <div>
                <form method="GET" action="orders.php" class="search-container mb-0">
                    <input type="text" name="search" class="search-input" placeholder="Cari pesanan..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <i class="bi bi-search search-icon"></i>
                </form>
            </div>
        </div>
        <?php if (isset($_GET['status'])): ?>
    <div class="custom-alert">
        <i class="bi bi-check-circle-fill"></i>
        <div>
            <?php
            switch($_GET['status']) {
                case 'updated':
                    echo 'Status pesanan berhasil diperbarui.';
                    break;
                case 'deleted':
                    echo 'Pesanan berhasil dihapus.';
                    break;
            }
            ?>
        </div>
    </div>
<?php endif; ?>
        
        <!-- Orders Card -->
        <div class="data-card">
            <div class="card-header">
                <div class="title-wrapper">
                    <div class="header-icon">
                        <i class="bi bi-cart"></i>
                    </div>
                    <h4>Daftar Pesanan</h4>
                </div>
                <div>
                    <a href="#" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="bi bi-download me-1"></i>Export
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x" style="font-size: 3rem; color: #ccc;"></i>
                        <h5 class="mt-3">Belum ada pesanan</h5>
                        <p class="text-muted">Pesanan baru akan muncul di sini ketika pelanggan melakukan pemesanan.</p>
                    </div>
                <?php else: ?>
                    <!-- Orders Table -->
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ID Pesanan</th>
                                    <th>Tema</th>
                                    <th>Mempelai</th>
                                    <th>Tanggal Pesan</th>
                                    <th>Tanggal Pernikahan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $id => $order): ?>
                                    <tr>
                                        <td>#<?= substr($id, 0, 8) ?></td>
                                        <td><span class="badge-primary"><?= htmlspecialchars($order['theme_name']) ?></span></td>
                                        <td><?= htmlspecialchars($order['groom_name']) ?> & <?= htmlspecialchars($order['bride_name']) ?></td>
                                        <td><?= isset($order['order_date']) ? date('d/m/Y', strtotime($order['order_date'])) : '-' ?></td>
                                        <td><?= date('d/m/Y', strtotime($order['wedding_date'])) ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'badge-primary';
                                            $statusIcon = 'hourglass-split';
                                            switch ($order['status'] ?? 'pending') {
                                                case 'pending':
                                                    $statusClass = 'badge-primary';
                                                    $statusIcon = 'hourglass-split';
                                                    $statusText = 'Menunggu';
                                                    break;
                                                case 'processing':
                                                    $statusClass = 'badge-info';
                                                    $statusIcon = 'gear-fill';
                                                    $statusText = 'Diproses';
                                                    break;
                                                case 'completed':
                                                    $statusClass = 'badge-success';
                                                    $statusIcon = 'check-circle';
                                                    $statusText = 'Selesai';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'badge-danger';
                                                    $statusIcon = 'x-circle';
                                                    $statusText = 'Dibatalkan';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?= $statusClass ?>">
                                                <i class="bi bi-<?= $statusIcon ?> me-1"></i><?= $statusText ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="#" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#orderDetailModal<?= $id ?>">
                                                <i class="bi bi-eye me-1"></i>Detail
                                            </a>
                                            <a href="#" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?= $id ?>">
                                                <i class="bi bi-pencil me-1"></i>Status
                                            </a>
                                            <a href="#" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteOrderModal<?= $id ?>">
        <i class="bi bi-trash me-1"></i>Hapus
    </a>
                                        </td>
                                    </tr>
                                    
                                    <!-- Order Detail Modal -->
                                    <div class="modal fade" id="orderDetailModal<?= $id ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detail Pesanan #<?= substr($id, 0, 8) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row mb-4">
                                                        <div class="col-md-6">
                                                            <h6>Informasi Pesanan</h6>
                                                            <p class="mb-1"><strong>ID Pesanan:</strong> #<?= substr($id, 0, 8) ?></p>
                                                            <p class="mb-1"><strong>Tanggal Pesan:</strong> <?= isset($order['order_date']) ? date('d/m/Y H:i', strtotime($order['order_date'])) : '-' ?></p>
                                                            <p class="mb-1"><strong>Tema:</strong> <?= htmlspecialchars($order['theme_name']) ?></p>
                                                            <p class="mb-1"><strong>Status:</strong> <span class="<?= $statusClass ?>"><?= $statusText ?></span></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Informasi Kontak</h6>
                                                            <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                                                            <p class="mb-1"><strong>Telepon:</strong> <?= htmlspecialchars($order['phone_number']) ?></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row mb-4">
                                                        <div class="col-12">
                                                            <h6>Detail Pernikahan</h6>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><strong>Mempelai Pria:</strong> <?= htmlspecialchars($order['groom_name']) ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><strong>Mempelai Wanita:</strong> <?= htmlspecialchars($order['bride_name']) ?></p>
                                                                </div>
                                                            </div>
                                                            <p class="mb-1"><strong>Tanggal Pernikahan:</strong> <?= date('d/m/Y', strtotime($order['wedding_date'])) ?></p>
                                                            <p class="mb-1"><strong>Waktu:</strong> <?= htmlspecialchars($order['wedding_time']) ?></p>
                                                            <p class="mb-1"><strong>Tempat:</strong> <?= htmlspecialchars($order['wedding_venue']) ?></p>
                                                            <p class="mb-1"><strong>Alamat:</strong> <?= htmlspecialchars($order['venue_address']) ?></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if (!empty($order['additional_info'])): ?>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <h6>Catatan Tambahan</h6>
                                                            <p><?= nl2br(htmlspecialchars($order['additional_info'])) ?></p>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?= $id ?>" data-bs-dismiss="modal">Update Status</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Update Status Modal -->
                                    <div class="modal fade" id="updateStatusModal<?= $id ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Status Pesanan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="orders.php">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="order_id" value="<?= $id ?>">
                                                        <div class="mb-3">
                                                            <label for="status" class="form-label">Status Pesanan</label>
                                                            <select class="form-select" name="status" required>
                                                                <option value="pending" <?= (($order['status'] ?? 'pending') == 'pending') ? 'selected' : '' ?>>Menunggu</option>
                                                                <option value="processing" <?= (($order['status'] ?? '') == 'processing') ? 'selected' : '' ?>>Diproses</option>
                                                                <option value="completed" <?= (($order['status'] ?? '') == 'completed') ? 'selected' : '' ?>>Selesai</option>
                                                                <option value="cancelled" <?= (($order['status'] ?? '') == 'cancelled') ? 'selected' : '' ?>>Dibatalkan</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="update_status" class="btn btn-primary">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Inside your foreach loop, after the update status modal -->
<!-- Delete Order Modal -->
<div class="modal fade" id="deleteOrderModal<?= $id ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="orders.php">
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $id ?>">
                    <p>Apakah Anda yakin ingin menghapus pesanan <strong>#<?= substr($id, 0, 8) ?></strong> untuk <strong><?= htmlspecialchars($order['groom_name']) ?> & <?= htmlspecialchars($order['bride_name']) ?></strong>?</p>
                    <p class="text-danger"><strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="delete_order" class="btn btn-danger">Hapus Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Data Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" action="export_orders.php">
                        <div class="mb-3">
                            <label for="exportFormat" class="form-label">Format</label>
                            <select class="form-select" id="exportFormat" name="format">
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="dateFrom" class="form-label">Dari Tanggal</label>
                            <input type="date" class="form-control" id="dateFrom" name="date_from">
                        </div>
                        <div class="mb-3">
                            <label for="dateTo" class="form-label">Sampai Tanggal</label>
                            <input type="date" class="form-control" id="dateTo" name="date_to">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Export</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.querySelector('.mobile-toggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
            
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        });
    </script>
</body>
</html>