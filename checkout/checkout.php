<?php session_start();
require_once('../config.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Fetch cart count
try {
    $cartCountStmt = $db->prepare("SELECT SUM(quantity) AS count FROM cart WHERE user_id = ?");
    $cartCountStmt->execute([$user_id]);
    $cartCount = (int) $cartCountStmt->fetchColumn();
} catch (PDOException $e) {
    $cartCount = 0;
}

$stmt = $db->prepare("SELECT firstname, lastname, email, phonenumber, address, barangay, city, zipcode, landmark FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get cart items with parent product information
$cartStmt = $db->prepare("
    SELECT
        c.id as cart_id,
        c.quantity,
        p.id as product_id,
        p.name as variant_name,
        p.price,
        p.stock,
        pp.id as parent_id,
        pp.name as parent_name,
        pp.image as parent_image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN parent_products pp ON p.parent_id = pp.id
    WHERE c.user_id = ?
");
$cartStmt->execute([$user_id]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Checkout | Triple JH Chicken Trading</title>
    <link rel="icon" href="../img/logo.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/footer_header.css">
    <style>
        :root {
            --sunset-gradient-start: #ffb347;
            --sunset-gradient-end: #ff6b26;
            --rich-amber: #f18f01;
            --buttered-sand: #ffe4c1;
            --deep-chestnut: #7a3a12;
            --spark-gold: #f9a219;
            --cream-panel: #fff5e2;
            --accent-light: #fff7e3;
            --accent-dark: #6d3209;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            font-family: "Inter", "Segoe UI", sans-serif;
            background: var(--buttered-sand);
            color: var(--accent-dark);
            display: flex;
            flex-direction: column;
        }

        body {
            padding-top: 50px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            padding-bottom: 80px;
        }

        .cart-link {
            position: relative;
        }

        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--rich-amber);
            color: var(--accent-light);
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 999px;
            min-width: 18px;
            text-align: center;
            line-height: 1.2;
        }

        .checkout-container {
            max-width: 1000px;
            margin: 60px auto;
            flex: 1;
        }

        .card {
            border: none;
            border-radius: 14px;
            background: var(--cream-panel);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.12);
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border-color: rgba(109, 50, 9, 0.2);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--rich-amber);
            box-shadow: 0 0 0 0.15rem rgba(241, 143, 1, 0.35);
        }

        .checkout-btn {
            background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            color: var(--accent-dark);
            border: none;
            width: 100%;
            padding: 0.8rem;
            border-radius: 999px;
            font-weight: 600;
            box-shadow: 0 10px 24px rgba(241, 143, 1, 0.35);
        }

        .checkout-btn:hover {
            box-shadow: 0 14px 32px rgba(241, 143, 1, 0.45);
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../dashboard.php">
                <img src="../img/logo.jpg" alt="Triple JH Chicken Trading"
                    style="height: 40px; width: auto; margin-right: 10px;">
                <span class="d-none d-md-inline">Triple JH Chicken Trading</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="fas fa-home"></i> Shop
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../orders/orders.php">
                            <i class="fas fa-shopping-bag"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../about.php">
                            <i class="fas fa-info-circle"></i> About
                        </a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link position-relative" href="../carts/cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cartCount > 0): ?>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $cartCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../useraccounts/settings.php">
                            <i class="fas fa-user"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="checkout-container">
        <h2 class="fw-bold mb-4">Checkout</h2>
        <form method="POST" action="checkout_process.php" enctype="multipart/form-data">
            <div class="row g-4"> <!-- Delivery Info -->
                <div class="col-lg-7">
                    <div class="card p-4">
                        <h5 class="fw-bold mb-3">Delivery Information</h5>
                        <div class="row g-3">
                            <div class="col-md-6"> <label class="form-label">First Name</label> <input type="text"
                                    class="form-control" name="firstname"
                                    value="<?= htmlspecialchars($user['firstname']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Last Name</label> <input type="text"
                                    class="form-control" name="lastname"
                                    value="<?= htmlspecialchars($user['lastname']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Phone Number</label> <input type="text"
                                    class="form-control" name="phonenumber"
                                    value="<?= htmlspecialchars($user['phonenumber']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Email</label> <input type="email"
                                    class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                                    readonly> </div>
                            <div class="col-12"> <label class="form-label">Street Address</label> <input type="text"
                                    class="form-control" name="address"
                                    value="<?= htmlspecialchars($user['address']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Barangay</label> <select
                                    class="form-select" name="barangay" required>
                                    <option value="<?= htmlspecialchars($user['barangay']) ?>" selected>
                                        <?= htmlspecialchars($user['barangay']) ?>
                                    </option>
                                    <option value="">Select Barangay</option>
                                    <option>Banaban</option>
                                    <option>Baybay</option>
                                    <option>Binagbag</option>
                                    <option>Donacion</option>
                                    <option>Encanto</option>
                                    <option>Laog</option>
                                    <option>Marungko</option>
                                    <option>Niugan</option>
                                    <option>Paltok</option>
                                    <option>Pulong Yantok</option>
                                    <option>San Roque</option>
                                    <option>San Vicente</option>
                                    <option>Santa Cruz</option>
                                    <option>Santo Niño</option>
                                    <option>Sulucan</option>
                                    <option>Taboc</option>
                                    <option>Talimundoc</option>
                                    <option>Tukod</option>
                                    <option>Borol 1st</option>
                                    <option>Borol 2nd</option>
                                    <option>Dalig</option>
                                    <option>Longos</option>
                                    <option>Panginay</option>
                                    <option>Poblacion</option>
                                    <option>San Juan</option>
                                    <option>San Nicolas</option>
                                    <option>Santa Clara</option>
                                    <option>Santol</option>
                                    <option>Wawa</option>
                                    <option>Bagong Barrio</option>
                                    <option>Bagong Nayon</option>
                                    <option>Barangca</option>
                                    <option>Calantipay</option>
                                    <option>Catulinan</option>
                                    <option>Concepcion</option>
                                    <option>Hinukay</option>
                                    <option>Makinabang</option>
                                    <option>Matangtubig</option>
                                    <option>Pagala</option>
                                    <option>Paitan</option>
                                    <option>Piel</option>
                                    <option>Pinagbarilan</option>
                                    <option>Poblacion</option>
                                    <option>San Jose</option>
                                    <option>San Rafael</option>
                                    <option>San Roque</option>
                                    <option>Santa Barbara</option>
                                    <option>Santa Rita</option>
                                    <option>Sto. Cristo</option>
                                    <option>Sto. Niño</option>
                                    <option>Subic</option>
                                    <option>Sulivan</option>
                                    <option>Tangos</option>
                                    <option>Tarcan</option>
                                    <option>Tibag</option>
                                    <option>Tilapayong</option>
                                    <option>Tiaong</option>
                                    <option>Virgen Delas Flores</option>
                                    <option>Antipona</option>
                                    <option>Bagumbayan</option>
                                    <option>Bambang</option>
                                    <option>Batia</option>
                                    <option>Binang 1st</option>
                                    <option>Binang 2nd</option>
                                    <option>Bolacan</option>
                                    <option>Bunlo</option>
                                    <option>Caingin</option>
                                    <option>Duhat</option>
                                    <option>Igulot</option>
                                    <option>Lolomboy</option>
                                    <option>Sulucan</option>
                                    <option>Tambobong</option>
                                    <option>Turo</option>
                                    <option>Wakas</option>
                                    <option>Bagumbayan</option>
                                    <option>Bambang</option>
                                    <option>Matungao</option>
                                    <option>Maysantol</option>
                                    <option>Pariancillo</option>
                                    <option>Perez</option>
                                    <option>Pitpitan</option>
                                    <option>San Francisco</option>
                                    <option>San Jose</option>
                                    <option>San Nicolas</option>
                                    <option>Santa Ana</option>
                                    <option>Santa Ines</option>
                                    <option>Santo Cristo</option>
                                    <option>Taliptip</option>
                                    <option>Tibig</option>
                                    <option>Bonga Mayor</option>
                                    <option>Bonga Menor</option>
                                    <option>Camachilihan</option>
                                    <option>Cambaog</option>
                                    <option>Catacte</option>
                                    <option>Liciada</option>
                                    <option>Malamig</option>
                                    <option>Malamig Partida</option>
                                    <option>Poblacion</option>
                                    <option>San Pedro</option>
                                    <option>Tibagan</option>
                                    <option>Balite</option>
                                    <option>Balungao</option>
                                    <option>Buguion</option>
                                    <option>Calizon</option>
                                    <option>Corazon</option>
                                    <option>Frances</option>
                                    <option>Gatbuca</option>
                                    <option>Gugo</option>
                                    <option>Iba Este</option>
                                    <option>Iba O Este</option>
                                    <option>Longos</option>
                                    <option>Maria Cristina</option>
                                    <option>Meyto</option>
                                    <option>Palimbang</option>
                                    <option>Panducot</option>
                                    <option>Pio Cruzcosa</option>
                                    <option>Poblacion</option>
                                    <option>San Jose</option>
                                    <option>San Marcos</option>
                                    <option>Sapang Bayan</option>
                                    <option>Sibul</option>
                                    <option>Sukol</option>
                                    <option>Bayabas</option>
                                    <option>Camachin</option>
                                    <option>Kalawakan</option>
                                    <option>Pulong Sampalok</option>
                                    <option>Sapang Bulak</option>
                                    <option>Talbak</option>
                                    <option>Camachile</option>
                                    <option>Cutcut</option>
                                    <option>Daungan</option>
                                    <option>Ilang-Ilang</option>
                                    <option>Malis</option>
                                    <option>Poblacion</option>
                                    <option>Pritil</option>
                                    <option>Pulong Gubat</option>
                                    <option>Santa Cruz</option>
                                    <option>Santa Rita</option>
                                    <option>Tabang</option>
                                    <option>Tiaong</option>
                                    <option>Tuktukan</option>
                                    <option>Abulalas</option>
                                    <option>Carillo</option>
                                    <option>Iba</option>
                                    <option>Iba-Ibayo</option>
                                    <option>Ilog Malino</option>
                                    <option>Mercado</option>
                                    <option>Palapat</option>
                                    <option>Panginay</option>
                                    <option>Pugad</option>
                                    <option>San Agustin</option>
                                    <option>San Isidro</option>
                                    <option>San Jose</option>
                                    <option>San Miguel</option>
                                    <option>San Nicolas</option>
                                    <option>San Pablo</option>
                                    <option>San Pascual</option>
                                    <option>San Pedro</option>
                                    <option>San Roque</option>
                                    <option>San Sebastian</option>
                                    <option>San Vicente</option>
                                    <option>Santa Monica</option>
                                    <option>Santo Niño</option>
                                    <option>Santo Rosario</option>
                                    <option>Sagrada Familia</option>
                                    <option>Sto. Tomas</option>
                                    <option>Tibaguin</option>
                                    <option>Anilao</option>
                                    <option>Atlag</option>
                                    <option>Babatnin</option>
                                    <option>Bagna</option>
                                    <option>Bagong Bayan</option>
                                    <option>Balayong</option>
                                    <option>Balite</option>
                                    <option>Bangkal</option>
                                    <option>Barihan</option>
                                    <option>Bulihan</option>
                                    <option>Bungahan</option>
                                    <option>Caingin</option>
                                    <option>Calero</option>
                                    <option>Canalate</option>
                                    <option>Caniogan</option>
                                    <option>Catmon</option>
                                    <option>Cofradia</option>
                                    <option>Dakila</option>
                                    <option>Guinhawa</option>
                                    <option>Kaliligawan</option>
                                    <option>Liang</option>
                                    <option>Ligas</option>
                                    <option>Longos</option>
                                    <option>Look 1st</option>
                                    <option>Look 2nd</option>
                                    <option>Lugam</option>
                                    <option>Mabolo</option>
                                    <option>Mambog</option>
                                    <option>Masile</option>
                                    <option>Matimbo</option>
                                    <option>Mojon</option>
                                    <option>Namayan</option>
                                    <option>Niugan</option>
                                    <option>Pamarawan</option>
                                    <option>Panasahan</option>
                                    <option>Pinagbakahan</option>
                                    <option>San Agustin</option>
                                    <option>San Gabriel</option>
                                    <option>San Juan</option>
                                    <option>San Pablo</option>
                                    <option>San Vicente</option>
                                    <option>Santiago</option>
                                    <option>Santisima Trinidad</option>
                                    <option>Santo Cristo</option>
                                    <option>Santo Niño</option>
                                    <option>Santo Rosario</option>
                                    <option>Santor</option>
                                    <option>Sumapang Bata</option>
                                    <option>Sumapang Matanda</option>
                                    <option>Taal</option>
                                    <option>Tikey</option>
                                    <option>Abangan Norte</option>
                                    <option>Abangan Sur</option>
                                    <option>Ibayo</option>
                                    <option>Lias</option>
                                    <option>Loma de Gato</option>
                                    <option>Lambakin</option>
                                    <option>Nagbalon</option>
                                    <option>Patubig</option>
                                    <option>Prenza I</option>
                                    <option>Prenza II</option>
                                    <option>Poblacion I</option>
                                    <option>Poblacion II</option>
                                    <option>Poblacion III</option>
                                    <option>Poblacion IV</option>
                                    <option>Poblacion V</option>
                                    <option>Saog</option>
                                    <option>Sta. Rosa I</option>
                                    <option>Sta. Rosa II</option>
                                    <option>Tabing Ilog</option>
                                    <option>Bahay Pare</option>
                                    <option>Bancal</option>
                                    <option>Bañga</option>
                                    <option>Barangca</option>
                                    <option>Bayugo</option>
                                    <option>Calvario</option>
                                    <option>Camalig</option>
                                    <option>Hulo</option>
                                    <option>Iba</option>
                                    <option>Langka</option>
                                    <option>Lawa</option>
                                    <option>Libtong</option>
                                    <option>Liputan</option>
                                    <option>Longos</option>
                                    <option>Malhacan</option>
                                    <option>Pajo</option>
                                    <option>Pandayan</option>
                                    <option>Pantoc</option>
                                    <option>Perez</option>
                                    <option>Poblacion</option>
                                    <option>Saluysoy</option>
                                    <option>Tugatog</option>
                                    <option>Ubihan</option>
                                    <option>Zamora</option>
                                    <option>Bigte</option>
                                    <option>Bitungol</option>
                                    <option>Friendship Village Resources (FVR)</option>
                                    <option>Matictic</option>
                                    <option>Minuyan</option>
                                    <option>Partida</option>
                                    <option>Pinagkurusan</option>
                                    <option>Poblacion</option>
                                    <option>San Lorenzo</option>
                                    <option>San Mateo</option>
                                    <option>Tigbe</option>
                                    <option>Hulo</option>
                                    <option>Lawa</option>
                                    <option>Paco</option>
                                    <option>Paliwas</option>
                                    <option>Panghulo</option>
                                    <option>San Pascual</option>
                                    <option>Salambao</option>
                                    <option>Tawiran</option>
                                    <option>Bagbaguin</option>
                                    <option>Bagong Barrio</option>
                                    <option>Baka-Bakahan</option>
                                    <option>Bunsuran I</option>
                                    <option>Bunsuran II</option>
                                    <option>Bunsuran III</option>
                                    <option>Cacarong Bata</option>
                                    <option>Cacarong Matanda</option>
                                    <option>Cupang</option>
                                    <option>Malibong Bata</option>
                                    <option>Manatal</option>
                                    <option>Mapulang Lupa</option>
                                    <option>Masuso</option>
                                    <option>Mojon</option>
                                    <option>Poblacion</option>
                                    <option>Real de Cacarong</option>
                                    <option>San Roque</option>
                                    <option>Siling Bata</option>
                                    <option>Siling Matanda</option>
                                    <option>Bagong Pook</option>
                                    <option>Agnaya</option>
                                    <option>Banga I</option>
                                    <option>Banga II</option>
                                    <option>Bintog</option>
                                    <option>Bulihan</option>
                                    <option>Culianin</option>
                                    <option>Lalangan</option>
                                    <option>Lumang Bayan</option>
                                    <option>Parulan</option>
                                    <option>Poblacion</option>
                                    <option>Rueda</option>
                                    <option>San Jose</option>
                                    <option>Sipat</option>
                                    <option>Sta. Ines</option>
                                    <option>Sto. Niño</option>
                                    <option>Tabang</option>
                                    <option>Tumana</option>
                                    <option>Balatong A</option>
                                    <option>Balatong B</option>
                                    <option>Cutcot</option>
                                    <option>Dampol I</option>
                                    <option>Dampol II</option>
                                    <option>Longos</option>
                                    <option>Paltao</option>
                                    <option>Peñabatan</option>
                                    <option>Poblacion</option>
                                    <option>Santa Peregrina</option>
                                    <option>Sto. Cristo</option>
                                    <option>Taal</option>
                                    <option>Tibag</option>
                                    <option>Tinejero</option>
                                    <option>Aklet</option>
                                    <option>Alagao</option>
                                    <option>Anyatam</option>
                                    <option>Bagong Barrio</option>
                                    <option>Bubulong Munti</option>
                                    <option>Buhol na Mangga</option>
                                    <option>Calasag</option>
                                    <option>Garlang</option>
                                    <option>Lapnit</option>
                                    <option>Mataas na Parang</option>
                                    <option>Paliwas</option>
                                    <option>Pasong Bangkal</option>
                                    <option>Pinaod</option>
                                    <option>Poblacion</option>
                                    <option>Pinaod</option>
                                    <option>Poblacion</option>
                                    <option>Sapang Putol</option>
                                    <option>Sapang Bulac</option>
                                    <option>Santo Niño</option>
                                    <option>Sumandig</option>
                                    <option>Telapatio</option>
                                    <option>Ulingao</option>
                                    <option>Assumption</option>
                                    <option>Bagong Buhay I</option>
                                    <option>Bagong Buhay II</option>
                                    <option>Bagong Buhay III</option>
                                    <option>Citrus</option>
                                    <option>Ciudad Real</option>
                                    <option>Dulong Bayan</option>
                                    <option>Fatima I</option>
                                    <option>Fatima II</option>
                                    <option>Fatima III</option>
                                    <option>Fatima IV</option>
                                    <option>Fatima V</option>
                                    <option>Francisco Homes-Guijo</option>
                                    <option>Francisco Homes-Mulawin</option>
                                    <option>Francisco Homes-Narra</option>
                                    <option>Francisco Homes-Yakal</option>
                                    <option>Graceville</option>
                                    <option>Gumaoc Central</option>
                                    <option>Gumaoc East</option>
                                    <option>Gumaoc West</option>
                                    <option>Kaybanban</option>
                                    <option>Kaypian</option>
                                    <option>Lawang Pari</option>
                                    <option>Maharlika</option>
                                    <option>Minuyan I</option>
                                    <option>Minuyan II</option>
                                    <option>Minuyan III</option>
                                    <option>Minuyan IV</option>
                                    <option>Minuyan Proper</option>
                                    <option>Muzon</option>
                                    <option>Paradise III</option>
                                    <option>Poblacion I–VI</option>
                                    <option>San Isidro</option>
                                    <option>San Manuel</option>
                                    <option>San Martin I–V</option>
                                    <option>San Pedro</option>
                                    <option>San Rafael I–V</option>
                                    <option>Santo Cristo</option>
                                    <option>Sapang Palay Proper</option>
                                    <option>Tungkong Mangga</option>
                                    <option>Gaya-Gaya</option>
                                    <option>Sta. Cruz</option>
                                    <option>Sto. Niño 1</option>
                                    <option>Sto. Niño 2</option>
                                    <option>Bantog</option>
                                    <option>Baritan</option>
                                    <option>Batasan Bata</option>
                                    <option>Batasan Matanda</option>
                                    <option>Bugo</option>
                                    <option>Camias</option>
                                    <option>Ilog Bulo</option>
                                    <option>Malibay</option>
                                    <option>Mandile</option>
                                    <option>Pacalag</option>
                                    <option>Poblacion</option>
                                    <option>Pulong Bayabas</option>
                                    <option>San Jose</option>
                                    <option>San Juan</option>
                                    <option>San Vicente</option>
                                    <option>Santa Ines</option>
                                    <option>Sapang Putol</option>
                                    <option>Sibul</option>
                                    <option>Tartaro</option>
                                    <option>Tigpalas</option>
                                    <option>Tukod</option>
                                    <option>BMA-Balagtas</option>
                                    <option>Caingin</option>
                                    <option>Capihan</option>
                                    <option>Cruz na Daan</option>
                                    <option>Diliman I</option>
                                    <option>Diliman II</option>
                                    <option>Libis</option>
                                    <option>Lico</option>
                                    <option>Marong</option>
                                    <option>Poblacion</option>
                                    <option>Pulong Bayabas</option>
                                    <option>Sampaloc</option>
                                    <option>San Agustin</option>
                                    <option>San Roque</option>
                                    <option>Sapang Putol</option>
                                    <option>Talacsan</option>
                                    <option>Tambubong</option>
                                    <option>Ulingao</option>
                                    <option>Bagbaguin</option>
                                    <option>Balasing</option>
                                    <option>Buenavista</option>
                                    <option>Bulac</option>
                                    <option>Camangyanan</option>
                                    <option>Catmon</option>
                                    <option>Caysio</option>
                                    <option>Dulong Bayan</option>
                                    <option>Guyong</option>
                                    <option>Lalakhan</option>
                                    <option>Mahabang Parang</option>
                                    <option>Mag-asawang Sapa</option>
                                    <option>Manggahan</option>
                                    <option>Parada</option>
                                    <option>Poblacion</option>
                                    <option>Pulong Buhangin</option>
                                    <option>San Gabriel</option>
                                    <option>San Jose Patag</option>
                                    <option>San Vicente</option>
                                    <option>Silangan</option>
                                    <option>Sta. Clara</option>
                                    <option>Sta. Cruz</option>
                                    <option>Sta. Maria</option>
                                    <option>Sto. Tomas</option>
                                    <option>Tumana</option>
                                </select> </div>
                            <div class="col-md-6"> <label class="form-label">City / Municipality</label> <select
                                    class="form-select" name="city" required>
                                    <option value="<?= htmlspecialchars($user['city']) ?>" selected>
                                        <?= htmlspecialchars($user['city']) ?>
                                    </option>
                                    <option value="">Select City / Municipality</option>
                                    <option>Angat</option>
                                    <option>Balagtas</option>
                                    <option>Baliwag</option>
                                    <option>Bocaue</option>
                                    <option>Bulakan</option>
                                    <option>Bustos</option>
                                    <option>Calumpit</option>
                                    <option>Doña Remedios Trinidad</option>
                                    <option>Guiguinto</option>
                                    <option>Hagonoy</option>
                                    <option>Malolos City</option>
                                    <option>Marilao</option>
                                    <option>Meycauayan City</option>
                                    <option>Norzagaray</option>
                                    <option>Obando</option>
                                    <option>Pandi</option>
                                    <option>Plaridel</option>
                                    <option>Pulilan</option>
                                    <option>San Ildefonso</option>
                                    <option>San Jose del Monte City</option>
                                    <option>San Miguel</option>
                                    <option>San Rafael</option>
                                    <option>Santa Maria</option>
                                </select> </div>
                            <div class="col-md-6"> <label class="form-label">Zip Code</label> <input type="text"
                                    class="form-control" name="zipcode"
                                    value="<?= htmlspecialchars($user['zipcode']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Landmark</label> <input type="text"
                                    class="form-control" name="landmark"
                                    value="<?= htmlspecialchars($user['landmark']) ?>"> </div>
                        </div>
                    </div>
                    <div class="card p-4 mt-4">
                        <h5 class="fw-bold mb-3">Payment Method</h5>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" id="cod"
                                value="Cash on Delivery" checked>
                            <label class="form-check-label" for="cod">Cash on Delivery</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="gcash" value="GCash">
                            <label class="form-check-label" for="gcash">GCash (Pay via QR Code)</label>
                        </div>

                        <!-- GCash Payment Section (Hidden by default) -->
                        <div id="gcash-payment-section" class="mt-4" style="display: none;">
                            <div class="alert alert-info">
                                <h6 class="fw-bold mb-2">GCash Payment Instructions:</h6>
                                <ol class="mb-3">
                                    <li>Scan the QR code below with your GCash app</li>
                                    <li>Enter the exact amount: <strong>₱<span
                                                id="gcash-amount"><?= number_format($total, 2) ?></span></strong></li>
                                    <li>Complete the payment in your GCash app</li>
                                    <li>Take a screenshot of your payment confirmation/receipt</li>
                                    <li>Upload the screenshot below for verification</li>
                                </ol>

                                <!-- QR Code Display -->
                                <div class="text-center mb-3">
                                    <img src="../img/gcash_qr_sample.png" alt="GCash QR Code"
                                        class="img-fluid"
                                        style="max-width: 200px; border: 1px solid #ddd; border-radius: 8px;">
                                    <p class="small text-muted mt-2">Scan this QR code with your GCash app</p>
                                </div>

                                <!-- Payment Screenshot Upload -->
                                <div class="mb-3">
                                    <label for="gcash_screenshot" class="form-label fw-bold">Payment Screenshot <span
                                            class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="gcash_screenshot" name="gcash_screenshot"
                                        accept="image/*" required>
                                    <div class="form-text">Upload a clear screenshot of your GCash payment confirmation
                                        (JPG, PNG, or WebP format, max 5MB)</div>

                                    <!-- Image Preview -->
                                    <div id="screenshot-preview" class="mt-3" style="display: none;">
                                        <label class="form-label">Preview:</label>
                                        <img id="preview-image" src="" alt="Payment screenshot preview"
                                            class="img-fluid" style="max-width: 300px; border: 1px solid #ddd; border-radius: 8px;">
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="remove-screenshot">
                                                <i class="fas fa-trash"></i> Remove Image
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- Order Summary -->
                <div class="col-lg-5">
                    <div class="card p-4">
                        <h5 class="fw-bold mb-3">Order Summary</h5> <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex justify-content-between small mb-2">
                                <div>
                                    <div class="fw-medium"><?= htmlspecialchars($item['parent_name']) ?></div>
                                    <?php if (!empty($item['variant_name']) && $item['variant_name'] !== $item['parent_name']): ?>
                                        <div class="text-muted small"><?= htmlspecialchars($item['variant_name']) ?></div>
                                    <?php endif; ?>
                                    <div class="text-muted small">x<?= $item['quantity'] ?></div>
                                </div>
                                <span class="text-end">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold"> <span>Total</span>
                            <span>₱<?= number_format($total, 2) ?></span>
                        </div> <input type="hidden" name="total" value="<?= $total ?>"> <button type="submit"
                            class="checkout-btn mt-3">Place Order</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <footer>
        <div class="container text-center">
            <div class="footer-links">
                <a href="../dashboard.php">Shop</a>
                <a href="../about.php">About</a>
                <a href="../about.php">Terms</a>
                <a href="../about.php">Privacy</a>
            </div>
            <p class="copyright">&copy; <?= date('Y') ?> Triple JH Chicken Trading. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle payment method toggle
        document.addEventListener('DOMContentLoaded', function () {
            const codRadio = document.getElementById('cod');
            const gcashRadio = document.getElementById('gcash');
            const gcashSection = document.getElementById('gcash-payment-section');
            const gcashScreenshotInput = document.getElementById('gcash_screenshot');
            const gcashAmountSpan = document.getElementById('gcash-amount');
            const screenshotPreview = document.getElementById('screenshot-preview');
            const previewImage = document.getElementById('preview-image');
            const removeScreenshotBtn = document.getElementById('remove-screenshot');
            const totalAmount = <?= $total ?>;

            // Update GCash amount display
            gcashAmountSpan.textContent = totalAmount.toFixed(2);

            function togglePaymentMethod() {
                if (gcashRadio.checked) {
                    gcashSection.style.display = 'block';
                    gcashScreenshotInput.required = true;
                } else {
                    gcashSection.style.display = 'none';
                    gcashScreenshotInput.required = false;
                    gcashScreenshotInput.value = '';
                    screenshotPreview.style.display = 'none';
                }
            }

            // Initialize state on first load so COD can submit without GCash screenshot
            togglePaymentMethod();

            codRadio.addEventListener('change', togglePaymentMethod);
            gcashRadio.addEventListener('change', togglePaymentMethod);

            // Handle screenshot preview
            gcashScreenshotInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file size (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size must be less than 5MB.');
                        gcashScreenshotInput.value = '';
                        screenshotPreview.style.display = 'none';
                        return;
                    }

                    // Validate file type
                    if (!file.type.startsWith('image/')) {
                        alert('Please upload an image file.');
                        gcashScreenshotInput.value = '';
                        screenshotPreview.style.display = 'none';
                        return;
                    }

                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewImage.src = e.target.result;
                        screenshotPreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    screenshotPreview.style.display = 'none';
                }
            });

            // Handle remove screenshot
            removeScreenshotBtn.addEventListener('click', function () {
                gcashScreenshotInput.value = '';
                screenshotPreview.style.display = 'none';
            });

            // Form validation for GCash
            document.querySelector('form').addEventListener('submit', function (e) {
                if (gcashRadio.checked) {
                    const screenshot = gcashScreenshotInput.files[0];
                    if (!screenshot) {
                        e.preventDefault();
                        alert('Please upload a payment screenshot.');
                        gcashScreenshotInput.focus();
                        return false;
                    }

                    // Validate file size again
                    if (screenshot.size > 5 * 1024 * 1024) {
                        e.preventDefault();
                        alert('File size must be less than 5MB.');
                        gcashScreenshotInput.focus();
                        return false;
                    }

                    // Validate file type again
                    if (!screenshot.type.startsWith('image/')) {
                        e.preventDefault();
                        alert('Please upload an image file.');
                        gcashScreenshotInput.focus();
                        return false;
                    }
                }
            });
        });
    </script>
</body>

</html>
