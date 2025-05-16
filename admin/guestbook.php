<?php
require '../firebase/firebase.php';

// Initialize Firebase reference
$guestbookRef = $firebase->getReference("guest_book");

// Handle deletion
if (isset($_POST['delete']) && !empty($_POST['delete'])) {
    $messageId = $_POST['delete'];
    $guestbookRef->getChild($messageId)->remove();
    
    // Set success message in session
    session_start();
    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => 'Ucapan berhasil dihapus!'
    ];
    
    header("Location: guestbook.php");
    exit();
}

// Handle reply submission
if (isset($_POST['reply']) && !empty($_POST['reply']) && isset($_POST['reply_text']) && !empty($_POST['reply_text'])) {
    $messageId = $_POST['reply'];
    $replyText = $_POST['reply_text'];
    
    // Update the message with admin reply
    $guestbookRef->getChild($messageId)->update([
        'admin_reply' => $replyText,
        'reply_timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Set success message in session
    session_start();
    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => 'Balasan berhasil ditambahkan!'
    ];
    
    header("Location: guestbook.php");
    exit();
}

// Handle reply deletion
if (isset($_POST['delete_reply']) && !empty($_POST['delete_reply'])) {
    $messageId = $_POST['delete_reply'];
    
    // Remove the admin reply
    $guestbookRef->getChild($messageId)->update([
        'admin_reply' => null,
        'reply_timestamp' => null
    ]);
    
    // Set success message in session
    session_start();
    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => 'Balasan berhasil dihapus!'
    ];
    
    header("Location: guestbook.php");
    exit();
}

// Get all guestbook entries and sort by timestamp (newest first)
$guestbookList = $guestbookRef->getValue();

// Sort by timestamp if entries exist
if ($guestbookList) {
    uasort($guestbookList, function($a, $b) {
        return strtotime($b['timestamp'] ?? '') - strtotime($a['timestamp'] ?? '');
    });
}

// Initialize session for alerts
session_start();
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Buku Tamu Pernikahan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6c5ce7;
            --accent-color: #fd79a8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 2rem 0;
            border-radius: 0 0 1rem 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .message-card {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        
        .message-card:hover {
            background-color: #f8f9fa;
            border-left: 4px solid var(--accent-color);
        }
        
        .btn-action {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .btn-delete {
            color: white;
            background-color: #ff7675;
        }
        
        .btn-delete:hover {
            background-color: #d63031;
            transform: scale(1.05);
        }
        
        .btn-reply {
            color: white;
            background-color: #74b9ff;
        }
        
        .btn-reply:hover {
            background-color: #0984e3;
            transform: scale(1.05);
        }
        
        .timestamp {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .empty-state {
            padding: 3rem;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #dfe6e9;
            margin-bottom: 1rem;
        }
        
        .footer {
            margin-top: 2rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .tooltip-inner {
            background-color: var(--dark-color);
        }
        
        .search-box {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .search-box .form-control {
            padding-left: 2.5rem;
            border-radius: 2rem;
            border: 1px solid #dfe6e9;
        }
        
        .search-box .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(108, 92, 231, 0.25);
            border-color: var(--primary-color);
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #b2bec3;
        }
        
        .admin-reply {
            background-color: #f0f7ff;
            border-left: 3px solid #74b9ff;
            padding: 10px 15px;
            margin-top: 10px;
            border-radius: 0.5rem;
        }
        
        .admin-badge {
            background-color: #0984e3;
            color: white;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
            margin-right: 5px;
        }
        
        .reply-form {
            display: none;
            margin-top: 10px;
        }
        
        .reply-actions {
            text-align: right;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold"><i class="fas fa-book me-2"></i> Buku Tamu Pernikahan</h1>
                    <p class="mb-0">Kelola ucapan dan doa dari para tamu undangan</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="index.php" class="btn btn-light px-4 rounded-pill">
                        <i class="fas fa-home me-2"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Alert Message -->
        <?php if ($alert): ?>
        <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show" role="alert">
            <?= $alert['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h1 class="display-4 fw-bold text-primary"><?= count($guestbookList ?? []) ?></h1>
                        <p class="text-muted mb-0">Total Ucapan</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h1 class="display-4 fw-bold text-success">
                            <?php
                            $today = date('Y-m-d');
                            $todayCount = 0;
                            if ($guestbookList) {
                                foreach ($guestbookList as $entry) {
                                    if (isset($entry['timestamp']) && substr($entry['timestamp'], 0, 10) == $today) {
                                        $todayCount++;
                                    }
                                }
                            }
                            echo $todayCount;
                            ?>
                        </h1>
                        <p class="text-muted mb-0">Ucapan Hari Ini</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h1 class="display-4 fw-bold text-warning">
                            <?php
                            $repliesCount = 0;
                            if ($guestbookList) {
                                foreach ($guestbookList as $entry) {
                                    if (isset($entry['admin_reply']) && !empty($entry['admin_reply'])) {
                                        $repliesCount++;
                                    }
                                }
                            }
                            echo $repliesCount;
                            ?>
                        </h1>
                        <p class="text-muted mb-0">Balasan Admin</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Box -->
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="form-control" placeholder="Cari nama atau ucapan...">
        </div>

        <!-- Messages List -->
        <div class="card">
            <div class="card-header py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="fw-bold mb-0">Daftar Ucapan</h5>
                    </div>
                </div>
            </div>
            
            <?php if ($guestbookList && count($guestbookList) > 0): ?>
                <div class="list-group list-group-flush" id="messagesList">
                    <?php foreach ($guestbookList as $id => $guest): ?>
                        <div class="list-group-item message-card p-3" data-search="<?= strtolower(htmlspecialchars($guest['name'] ?? '') . ' ' . htmlspecialchars($guest['message'] ?? '')) ?>">
                            <div class="d-flex justify-content-between">
                                <div class="w-100">
                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($guest['name'] ?? 'Tamu') ?></h6>
                                    <p class="mb-1"><?= nl2br(htmlspecialchars($guest['message'] ?? '')) ?></p>
                                    <div class="timestamp">
                                        <i class="far fa-clock me-1"></i>
                                        <?php
                                        if (isset($guest['timestamp'])) {
                                            $date = new DateTime($guest['timestamp']);
                                            echo $date->format('d M Y, H:i');
                                        }
                                        ?>
                                    </div>
                                    
                                    <!-- Admin Reply Section -->
                                    <?php if (isset($guest['admin_reply']) && !empty($guest['admin_reply'])): ?>
                                        <div class="admin-reply mt-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <span class="admin-badge">Admin</span>
                                                    <span class="fw-bold">Balasan:</span>
                                                </div>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus balasan ini?');">
                                                    <input type="hidden" name="delete_reply" value="<?= $id ?>">
                                                    <button type="submit" class="btn btn-sm text-danger border-0 p-0">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <p class="mb-1 mt-1"><?= nl2br(htmlspecialchars($guest['admin_reply'])) ?></p>
                                            <?php if (isset($guest['reply_timestamp'])): ?>
                                                <div class="timestamp">
                                                    <i class="far fa-clock me-1"></i>
                                                    <?php
                                                    $replyDate = new DateTime($guest['reply_timestamp']);
                                                    echo $replyDate->format('d M Y, H:i');
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <!-- Reply Form (initially hidden) -->
                                        <div class="reply-form" id="replyForm-<?= $id ?>">
                                            <form method="POST">
                                                <input type="hidden" name="reply" value="<?= $id ?>">
                                                <div class="form-group">
                                                    <textarea name="reply_text" class="form-control" rows="2" placeholder="Tulis balasan Anda..." required></textarea>
                                                </div>
                                                <div class="reply-actions mt-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary cancel-reply" data-id="<?= $id ?>">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-primary">Kirim Balasan</button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ms-3 d-flex flex-column">
                                    <?php if (!isset($guest['admin_reply']) || empty($guest['admin_reply'])): ?>
                                        <button class="btn btn-action btn-reply mb-2 show-reply-form" data-id="<?= $id ?>" data-bs-toggle="tooltip" title="Balas Ucapan">
                                            <i class="fas fa-reply"></i>
                                        </button>
                                    <?php endif; ?>
                                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ucapan ini?');">
                                        <input type="hidden" name="delete" value="<?= $id ?>">
                                        <button type="submit" class="btn btn-action btn-delete" data-bs-toggle="tooltip" title="Hapus Ucapan">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comment-slash"></i>
                    <h5>Belum Ada Ucapan</h5>
                    <p class="text-muted">Ucapan dari tamu undangan akan tampil di sini.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="footer text-center py-3">
            <p>&copy; <?= date('Y') ?> Admin Buku Tamu | Wedding</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const messageItems = document.querySelectorAll('#messagesList .message-card');
            
            messageItems.forEach(item => {
                const searchText = item.getAttribute('data-search');
                if (searchText.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Reply form toggle
        document.querySelectorAll('.show-reply-form').forEach(button => {
            button.addEventListener('click', function() {
                const messageId = this.getAttribute('data-id');
                document.getElementById('replyForm-' + messageId).style.display = 'block';
                this.style.display = 'none';
            });
        });
        
        // Cancel reply
        document.querySelectorAll('.cancel-reply').forEach(button => {
            button.addEventListener('click', function() {
                const messageId = this.getAttribute('data-id');
                document.getElementById('replyForm-' + messageId).style.display = 'none';
                document.querySelector('.show-reply-form[data-id="' + messageId + '"]').style.display = 'inline-flex';
            });
        });
    </script>
</body>
</html>