<?php
session_start();
require '../firebase/firebase.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit();
}

// Get wedding data from Firebase
$weddingDataRef = $firebase->getReference('wedding-data');
$data = $weddingDataRef->getValue();

// Get active invitation
$activeInvitationRef = $firebase->getReference('wedding-data');
$activeInvitation = $activeInvitationRef->getValue();

// Handle search by couple names
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = strtolower($_GET['search']);
    $data = array_filter($data, function($wedding) use ($searchTerm) {
        return strpos(strtolower($wedding['couple']['groom']['name']), $searchTerm) !== false ||
               strpos(strtolower($wedding['couple']['bride']['name']), $searchTerm) !== false;
    });
}

// Pagination
$itemsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;
$dataSlice = array_slice($data, $offset, $itemsPerPage);
$totalItems = count($data);
$totalPages = ceil($totalItems / $itemsPerPage);

// Handle active wedding setting
if (isset($_GET['set_active']) && !empty($_GET['set_active'])) {
    $id = $_GET['set_active'];
    if (isset($data[$id])) {
        try {
            $activeInvitationRef->set($data[$id]);
            header('Location: index.php?status=active_set');
            exit();
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Admin | Modern Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
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
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-person-circle me-2 text-white"></i>
            <h3>Wedding Admin</h3>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="index.php" class="active">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="rsvp.php">
                    <i class="bi bi-envelope-fill"></i>
                    <span>Kehadiran Tamu</span>
                </a>
            </li>
            <li>
        <a href="orders.php">
            <i class="bi bi-cart-fill"></i>
            <span>Pesanan</span>
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
                <h1>Dashboard</h1>
            </div>
        </div>
        
        <?php if (isset($_GET['status'])): ?>
            <div class="custom-alert">
                <i class="bi bi-check-circle-fill"></i>
                <div>
                    <?php
                    switch($_GET['status']) {
                        case 'deleted':
                            echo 'Wedding data successfully deleted.';
                            break;
                        case 'active_set':
                            echo 'Active wedding successfully set.';
                            break;
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Theme Card -->
        <div class="theme-card">
            <div class="card-body">
                <div class="icon-box">
                    <i class="bi bi-palette"></i>
                </div>
                <div class="theme-content">
                    <h2 class="text-capitalize"><?= htmlspecialchars($data['theme']['current']) ?></h2>
                    <p>Current Wedding Theme</p>
                </div>
            </div>
            <i class="bi bi-palette overlay-icon"></i>
        </div>
        
        <!-- Wedding Data Card -->
        <div class="data-card">
            <div class="card-header">
                <div class="title-wrapper">
                    <div class="header-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h4>Wedding Management</h4>
                </div>
            </div>
            
            <div class="card-body">
                
                <!-- Wedding Table -->
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Groom</th>
                                <th>Bride</th>
                                <th>Event Date</th>
                                <th>Location</th>
                                <th>Theme</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($data['couple'])): ?>
                                <tr>
                                    <td><?= htmlspecialchars($data['couple']['groom']['name']) ?></td>
                                    <td><?= htmlspecialchars($data['couple']['bride']['name']) ?></td>
                                    <td><?= htmlspecialchars($data['event']['date']) ?></td>
                                    <td><?= htmlspecialchars($data['event']['location']['name']) ?></td>
                                    <td><span class="badge-primary"><?= htmlspecialchars($data['theme']['current']) ?></span></td>
                                    <td><span class="badge-success"><i class="bi bi-check-circle me-1"></i>Active</span></td>
                                    <td>
                                        <a href="edit.php?id=<?= isset($data['id']) ? $data['id'] : '' ?>" class="btn btn-secondary btn-sm">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </a>
                                        <a href="rsvp.php" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-envelope me-1"></i>Kehadiran
                                        </a>
                                        <a href="guestbook.php" class="btn btn-primary btn-sm">
                                            <i class="bi bi-book me-1"></i>Ucapan
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
            
            // Make search form work
            const searchInput = document.querySelector('.search-input');
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    window.location.href = 'index.php?search=' + encodeURIComponent(this.value);
                }
            });
            
            // Initialize any tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        });
    </script>
</body>
</html>