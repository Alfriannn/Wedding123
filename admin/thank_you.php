<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terima Kasih | Undangan Digital Elegance</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;500;600&display=swap');
        
        :root {
            --primary-color: #d4af37;
            --secondary-color: #a67c00;
            --dark-color: #333333;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
        }
        
        .thank-you-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
        }
        
        .thank-you-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .thank-you-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        h1 {
            font-family: 'Playfair Display', serif;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        
        .subtitle {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            margin-bottom: 2rem;
            color: var(--secondary-color);
        }
        
        .order-details {
            background-color: rgba(212, 175, 55, 0.1);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            padding-bottom: 0.75rem;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: rgba(212, 175, 55, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .icon-circle i {
            font-size: 2.5rem;
            color: var(--primary-color);
        }
        
        .footer {
            margin-top: 2rem;
            color: #777;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php
    require '../firebase/firebase.php';
    
    // Check if order_id is provided
    if(empty($_GET['order_id'])) {
        header('Location: ../index.php');
        exit();
    }
    
    $orderId = $_GET['order_id'];
    $orderDetails = null;
    
    try {
        // Get order details from Firebase
        $orderRef = $firebase->getReference('orders/' . $orderId);
        $snapshot = $orderRef->getSnapshot();
        
        if($snapshot->exists()) {
            $orderDetails = $snapshot->getValue();
        } else {
            throw new Exception("Order not found");
        }
    } catch (Exception $e) {
        header('Location: ../index.php?error=order_not_found');
        exit();
    }
    ?>

    <div class="container thank-you-container">
        <div class="thank-you-card">
            <div class="icon-circle">
                <i class="fas fa-heart"></i>
            </div>
            <h1>Terima Kasih!</h1>
            <p class="subtitle">Pesanan undangan digital Anda telah kami terima</p>
            
            <p class="lead mb-4">
                Kami akan segera memproses pesanan Anda dan menghubungi Anda melalui email atau telepon untuk langkah selanjutnya.
            </p>
            
            <div class="order-details">
                <h4 class="mb-3">Detail Pesanan</h4>
                
                <div class="detail-row">
                    <span class="detail-label">ID Pesanan:</span>
                    <span><?php echo htmlspecialchars($orderId); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Tema:</span>
                    <span><?php echo htmlspecialchars($orderDetails['theme_name'] ?? ''); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Nama Pengantin:</span>
                    <span><?php echo htmlspecialchars($orderDetails['groom_name'] ?? '') . ' & ' . htmlspecialchars($orderDetails['bride_name'] ?? ''); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Tanggal Pernikahan:</span>
                    <span><?php echo htmlspecialchars($orderDetails['wedding_date'] ?? ''); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="badge bg-warning text-dark">
                        <?php echo ucfirst(htmlspecialchars($orderDetails['status'] ?? 'pending')); ?>
                    </span>
                </div>
            </div>
            
            <p class="mb-4">
                Jika Anda memiliki pertanyaan, silakan hubungi tim kami melalui WhatsApp:
            </p>
            
            <a href="https://wa.me/628" class="btn btn-primary mb-4">
                <i class="fab fa-whatsapp me-2"></i> Hubungi Kami
            </a>
            <div style="margin-top: 20px; text-align: center;">
            <a href="../index.html" class="text-decoration-none">
    <i class="fas fa-arrow-left me-2"></i> Kembali ke Beranda
</a>
</div>
            
            <div class="footer">
                <p>© <?php echo date('Y'); ?> Undangan Digital Elegance. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>