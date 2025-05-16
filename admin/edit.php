<?php
session_start();
require '../firebase/firebase.php';  // Pastikan file ini menginisialisasi $firebase dengan benar

// Check admin login
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit();
}

// Check for ID parameter
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Get wedding data by ID
        $wedding_data = $firebase->getReference("wedding-data")->getValue();

        if (!$wedding_data) {
            echo "<div class='alert alert-danger'>Data tidak ditemukan.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Terjadi kesalahan: " . $e->getMessage() . "</div>";
    }
} else {
    die("ID tidak ditemukan.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate date inputs
    $eventDate = $_POST['event_date'];
    if (!DateTime::createFromFormat('Y-m-d', $eventDate)) {
        die("Format tanggal tidak valid.");
    }

    // Prepare data structure
    $data = [
        "couple" => [
            "groom" => [
                "name" => $_POST['groom_name'],
                "description" => $_POST['groom_description'],
                "father" => $_POST['groom_father'],
                "mother" => $_POST['groom_mother']
            ],
            "bride" => [
                "name" => $_POST['bride_name'],
                "description" => $_POST['bride_description'],
                "father" => $_POST['bride_father'],
                "mother" => $_POST['bride_mother']
            ]
        ],
        "event" => [
            "date" => $eventDate,
            "location" => [
                "name" => $_POST['venue_name'],
                "address" => $_POST['venue_address'],
                "maps_url" => $_POST['maps_url']
            ],
            "ceremony" => [
                "time" => $_POST['ceremony_time'],
                "description" => $_POST['ceremony_description']
            ],
            "reception" => [
                "time" => $_POST['reception_time'],
                "description" => $_POST['reception_description']
            ]
        ],
        "theme" => [
            "current" => $_POST['theme'],
            "countdown" => $_POST['countdown_theme']
        ],
        "countdown" => [
            "year" => (int)$_POST['countdown_year'],
            "month" => (int)$_POST['countdown_month'],
            "day" => (int)$_POST['countdown_day'],
            "hour" => (int)$_POST['countdown_hour']
        ]
    ];

    try {
        // Update data in Firebase
        $firebase->getReference("wedding-data")->update($data);
        
        // Jika diperlukan update terpisah untuk tema
        $firebase->getReference('wedding-data/theme')->update([
            'current' => $_POST['theme'],
            'countdown' => $_POST['countdown_theme']
        ]);
        
        header("Location: index.php?status=updated");
        exit();
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Terjadi kesalahan: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <title>Edit Undangan Pernikahan</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #4f46e5;
            --primary-light: rgba(79, 70, 229, 0.1);
            --secondary: #64748b;
            --dark: #1f2937;
            --light: #f8fafc;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.05);
            --success: #10b981;
            --danger: #ef4444;
        }
        
        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .edit-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px var(--shadow);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 900px;
        }
        
        .page-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        
        .card {
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 2px 6px var(--shadow);
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 0.625rem 0.75rem;
            font-size: 0.875rem;
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .input-group-text {
            background-color: #f8fafc;
            border: 1px solid var(--border);
            color: var(--secondary);
        }
        
        h6 {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        
        .section-divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
        }
        
        .divider-line {
            flex-grow: 1;
            height: 1px;
            background-color: var(--border);
        }
        
        .divider-icon {
            padding: 0 1rem;
            color: var(--secondary);
            font-size: 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border: none;
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: #4338ca;
            box-shadow: 0 4px 8px rgba(79, 70, 229, 0.3);
        }
        
        .form-text {
            color: var(--secondary);
            font-size: 0.75rem;
        }
        
        /* Couple info layout */
        .couple-info {
            position: relative;
        }
        
        .couple-info::after {
            content: "♥";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: var(--primary);
            font-size: 1.5rem;
            z-index: 1;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            border-radius: 50%;
            box-shadow: 0 0 0 8px white;
        }
        
        .countdown-container {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 1.25rem;
            margin-top: 0.5rem;
            border: 1px solid var(--border);
        }
        
        .countdown-label {
            font-size: 0.75rem;
            text-align: center;
            color: var(--secondary);
            margin-top: 4px;
        }
        
        .alert {
            border-radius: 6px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }
        
        .card-icon {
            color: var(--primary);
            margin-right: 0.5rem;
        }
        
        .save-button {
            padding: 0.75rem 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="edit-container">
            <h2 class="page-title">
                <i class="bi bi-pencil-square me-2"></i>Edit Undangan Pernikahan
            </h2>

            <form method="POST">
                <!-- Couple Information -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-people card-icon"></i>Informasi Pengantin
                    </div>
                    <div class="card-body">
                        <div class="row g-4 couple-info">
                            <!-- Groom -->
                            <div class="col-md-6">
                                <h6><i class="bi bi-person me-2"></i>Pengantin Pria</h6>
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" name="groom_name" 
                                        value="<?= htmlspecialchars($wedding_data['couple']['groom']['name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea class="form-control" name="groom_description" rows="3" placeholder="Ceritakan sedikit tentang pengantin pria..."><?= htmlspecialchars($wedding_data['couple']['groom']['description']) ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Ayah</label>
                                    <input type="text" class="form-control" name="groom_father" 
                                        value="<?= htmlspecialchars($wedding_data['couple']['groom']['father']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Ibu</label>
                                    <input type="text" class="form-control" name="groom_mother" 
                                        value="<?= htmlspecialchars($wedding_data['couple']['groom']['mother']) ?>">
                                </div>
                            </div>

                            <!-- Bride -->
                            <div class="col-md-6">
                                <h6><i class="bi bi-person-heart me-2"></i>Pengantin Wanita</h6>
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" name="bride_name" 
                                        value="<?= htmlspecialchars($wedding_data['couple']['bride']['name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea class="form-control" name="bride_description" rows="3" placeholder="Ceritakan sedikit tentang pengantin wanita..."><?= htmlspecialchars($wedding_data['couple']['bride']['description']) ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Ayah</label>
                                    <input type="text" class="form-control" name="bride_father" 
                                        value="<?= htmlspecialchars($wedding_data['couple']['bride']['father']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Ibu</label>
                                    <input type="text" class="form-control" name="bride_mother" 
                                        value="<?= htmlspecialchars($wedding_data['couple']['bride']['mother']) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-divider">
                    <div class="divider-line"></div>
                    <div class="divider-icon"><i class="bi bi-calendar-event"></i></div>
                    <div class="divider-line"></div>
                </div>

                <!-- Event Details -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-geo-alt card-icon"></i>Detail Acara
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Acara</label>
                                <input type="date" class="form-control" name="event_date" 
                                    value="<?= htmlspecialchars($wedding_data['event']['date']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Tempat</label>
                                <input type="text" class="form-control" name="venue_name" 
                                    value="<?= htmlspecialchars($wedding_data['event']['location']['name']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea class="form-control" name="venue_address" rows="2"><?= htmlspecialchars($wedding_data['event']['location']['address']) ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Link Google Maps</label>
                                <input type="text" class="form-control" name="maps_url" 
                                    value="<?= htmlspecialchars($wedding_data['event']['location']['maps_url']) ?>">
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>Gunakan format URL embed (https://www.google.com/maps/embed?...)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-clock card-icon"></i>Jadwal Acara
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6><i class="bi bi-ring me-2"></i>Akad Nikah</h6>
                                <div class="mb-3">
                                    <label class="form-label">Waktu</label>
                                    <input type="text" class="form-control" name="ceremony_time" placeholder="Contoh: 08:00 - 10:00 WIB"
                                        value="<?= htmlspecialchars($wedding_data['event']['ceremony']['time']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea class="form-control" name="ceremony_description" rows="2" placeholder="Informasi tambahan tentang akad..."><?= htmlspecialchars($wedding_data['event']['ceremony']['description']) ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="bi bi-cup-hot me-2"></i>Resepsi</h6>
                                <div class="mb-3">
                                    <label class="form-label">Waktu</label>
                                    <input type="text" class="form-control" name="reception_time" placeholder="Contoh: 11:00 - 14:00 WIB"
                                        value="<?= htmlspecialchars($wedding_data['event']['reception']['time']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea class="form-control" name="reception_description" rows="2" placeholder="Informasi tambahan tentang resepsi..."><?= htmlspecialchars($wedding_data['event']['reception']['description']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Theme and Countdown -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-palette card-icon"></i>Pengaturan Tema & Countdown
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tema Undangan</label>
                                <select class="form-select" name="theme">
                                    <option value="tema1" <?= $wedding_data['theme']['current'] === 'tema1' ? 'selected' : '' ?>>Tema 1 - Elegant</option>
                                    <option value="tema2" <?= $wedding_data['theme']['current'] === 'tema2' ? 'selected' : '' ?>>Tema 2 - Romantic</option>
                                    <option value="tema3" <?= $wedding_data['theme']['current'] === 'tema3' ? 'selected' : '' ?>>Tema 3 - Modern</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tema Countdown</label>
                                <select class="form-select" name="countdown_theme">
                                    <option value="tema1" <?= isset($wedding_data['theme']['countdown']) && $wedding_data['theme']['countdown'] === 'tema1' ? 'selected' : '' ?>>Tema 1 - Classic</option>
                                    <option value="tema2" <?= isset($wedding_data['theme']['countdown']) && $wedding_data['theme']['countdown'] === 'tema2' ? 'selected' : '' ?>>Tema 2 - Minimalist</option>
                                    <option value="tema3" <?= isset($wedding_data['theme']['countdown']) && $wedding_data['theme']['countdown'] === 'tema3' ? 'selected' : '' ?>>Tema 3 - Colorful</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mt-3">
                                <label class="form-label">Pengaturan Countdown</label>
                                <div class="countdown-container">
                                    <p class="mb-3 small"><i class="bi bi-info-circle me-2"></i>Atur waktu mulai hitungan mundur untuk hari spesial Anda</p>
                                    <div class="row g-3">
                                        <div class="col-md-3 col-6">
                                            <input type="number" class="form-control" name="countdown_year" placeholder="Tahun" 
                                                value="<?= htmlspecialchars($wedding_data['countdown']['year']) ?>" required>
                                            <div class="countdown-label">Tahun</div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <input type="number" class="form-control" name="countdown_month" placeholder="Bulan" min="1" max="12"
                                                value="<?= htmlspecialchars($wedding_data['countdown']['month']) ?>" required>
                                            <div class="countdown-label">Bulan</div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <input type="number" class="form-control" name="countdown_day" placeholder="Tanggal" min="1" max="31"
                                                value="<?= htmlspecialchars($wedding_data['countdown']['day']) ?>" required>
                                            <div class="countdown-label">Tanggal</div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <input type="number" class="form-control" name="countdown_hour" placeholder="Jam" min="0" max="23"
                                                value="<?= htmlspecialchars($wedding_data['countdown']['hour']) ?>" required>
                                            <div class="countdown-label">Jam</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary save-button">
                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Subtle fade-in animation for cards
            const cards = document.querySelectorAll('.card');
            let delay = 100;
            
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(10px)';
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, delay);
                
                delay += 100;
            });
        });
    </script>
</body>
</html>