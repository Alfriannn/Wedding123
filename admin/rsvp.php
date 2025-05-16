<?php
session_start();
require '../firebase/firebase.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit();
}

// Get RSVP data from Firebase
$rsvpRef = $firebase->getReference('rsvp');
$rsvpData = $rsvpRef->getValue();

// Handle search functionality
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = strtolower($_GET['search']);
    $rsvpData = array_filter($rsvpData, function($rsvp) use ($searchTerm) {
        return strpos(strtolower($rsvp['name']), $searchTerm) !== false;
    });
}

// Handle filter by attendance status
if (isset($_GET['filter']) && !empty($_GET['filter'])) {
    $filterStatus = $_GET['filter'];
    if ($filterStatus !== 'all') {
        $rsvpData = array_filter($rsvpData, function($rsvp) use ($filterStatus) {
            return $rsvp['attendance'] === $filterStatus;
        });
    }
}

// Count attendance statistics
$totalInvitations = 0;
$totalAttending = 0;
$totalNotAttending = 0;
$totalGuests = 0;

if ($rsvpData) {
    foreach ($rsvpData as $rsvp) {
        $totalInvitations++;
        if ($rsvp['attendance'] === 'Hadir') {
            $totalAttending++;
            $totalGuests += isset($rsvp['number_of_guests']) ? $rsvp['number_of_guests'] : 1;
        } else if ($rsvp['attendance'] === 'Tidak Hadir') {
            $totalNotAttending++;
        }
    }
}

// Sort by timestamp (most recent first)
if ($rsvpData) {
    uasort($rsvpData, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSVP Dashboard - Wedding Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Inter', sans-serif;
        }
        .dashboard-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        .table thead {
            background-color: #007bff;
            color: white;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
        }
        .stats-card {
            transition: all 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-envelope-check-fill me-2 text-primary"></i>RSVP Management</h1>
                <div>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white stats-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Responses</h5>
                            <p class="card-text display-6"><?= $totalInvitations ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white stats-card">
                        <div class="card-body">
                            <h5 class="card-title">Attending</h5>
                            <p class="card-text display-6"><?= $totalAttending ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white stats-card">
                        <div class="card-body">
                            <h5 class="card-title">Not Attending</h5>
                            <p class="card-text display-6"><?= $totalNotAttending ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white stats-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Guests</h5>
                            <p class="card-text display-6"><?= $totalGuests ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Controls -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search by name..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex justify-content-end">
                        <select name="filter" class="form-select" style="max-width: 200px;" onchange="this.form.submit()">
                            <option value="all" <?= (!isset($_GET['filter']) || $_GET['filter'] === 'all') ? 'selected' : '' ?>>All Responses</option>
                            <option value="Hadir" <?= (isset($_GET['filter']) && $_GET['filter'] === 'Hadir') ? 'selected' : '' ?>>Attending</option>
                            <option value="Tidak Hadir" <?= (isset($_GET['filter']) && $_GET['filter'] === 'Tidak Hadir') ? 'selected' : '' ?>>Not Attending</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- RSVP List Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Number of Guests</th>
                            <th>Status</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rsvpData): ?>
                            <?php foreach ($rsvpData as $id => $rsvp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($rsvp['name']) ?></td>
                                    <td><?= isset($rsvp['number_of_guests']) ? htmlspecialchars($rsvp['number_of_guests']) : '1' ?></td>
                                    <td>
                                        <?php if ($rsvp['attendance'] === 'Hadir'): ?>
                                            <span class="badge bg-success status-badge">
                                                <i class="bi bi-check-circle me-1"></i>Attending
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger status-badge">
                                                <i class="bi bi-x-circle me-1"></i>Not Attending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d M Y, H:i', strtotime($rsvp['timestamp'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle me-2"></i>No RSVP responses found.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Export Button -->
            <div class="mt-3 text-end">
                <button class="btn btn-success" onclick="exportToCSV()">
                    <i class="bi bi-download me-1"></i>Export to CSV
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToCSV() {
            // Simple CSV export function
            let csv = 'Name,Number of Guests,Status,Timestamp\n';
            
            <?php if ($rsvpData): ?>
                <?php foreach ($rsvpData as $rsvp): ?>
                    csv += '<?= addslashes($rsvp['name']) ?>,';
                    csv += '<?= isset($rsvp['number_of_guests']) ? $rsvp['number_of_guests'] : '1' ?>,';
                    csv += '<?= $rsvp['attendance'] ?>,';
                    csv += '<?= date('Y-m-d H:i:s', strtotime($rsvp['timestamp'])) ?>\n';
                <?php endforeach; ?>
            <?php endif; ?>
            
            // Create download link
            const hiddenElement = document.createElement('a');
            hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csv);
            hiddenElement.target = '_blank';
            hiddenElement.download = 'rsvp_data_<?= date('Y-m-d') ?>.csv';
            hiddenElement.click();
        }
    </script>
</body>
</html>