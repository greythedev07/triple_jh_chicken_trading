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
    $cartCount = (int)$cartCountStmt->fetchColumn();
} catch (PDOException $e) {
    $cartCount = 0;
}

$stmt = $db->prepare("SELECT firstname, lastname, email, phonenumber, address, barangay, city, zipcode, landmark FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$cartStmt = $db->prepare(" SELECT p.name, p.price, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? ");
$cartStmt->execute([$user_id]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f6f8;
            font-family: 'Inter', sans-serif;
            color: #222;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .navbar {
            background: #000;
        }

        .navbar a {
            color: #fff !important;
        }

        .cart-link {
            position: relative;
        }

        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ff3b30;
            color: #fff;
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
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .form-control,
        .form-select {
            border-radius: 8px;
        }

        .checkout-btn {
            background: #000;
            color: #fff;
            border: none;
            width: 100%;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 600;
        }

        .checkout-btn:hover {
            background: #111;
        }

        footer {
            background: #000;
            color: #fff;
            padding: 1.5rem 0;
            text-align: center;
            margin-top: auto;
            flex-shrink: 0;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container"> <a class="navbar-brand fw-bold" href="../dashboard.php">Triple JH</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../dashboard.php">Shop</a></li>
                    <li class="nav-item"><a class="nav-link cart-link active" href="../carts/cart.php">Cart <span id="cartBadge" class="cart-badge" style="<?= $cartCount > 0 ? '' : 'display:none' ?>"><?= $cartCount > 0 ? $cartCount : '' ?></span></a></li>
                    <li class="nav-item"><a class="nav-link" href="../orders/orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="../useraccounts/settings.php">Settings</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="checkout-container">
        <h2 class="fw-bold mb-4">Checkout</h2>
        <form method="POST" action="checkout_process.php">
            <div class="row g-4"> <!-- Delivery Info -->
                <div class="col-lg-7">
                    <div class="card p-4">
                        <h5 class="fw-bold mb-3">Delivery Information</h5>
                        <div class="row g-3">
                            <div class="col-md-6"> <label class="form-label">First Name</label> <input type="text" class="form-control" name="firstname" value="<?= htmlspecialchars($user['firstname']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Last Name</label> <input type="text" class="form-control" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Phone Number</label> <input type="text" class="form-control" name="phonenumber" value="<?= htmlspecialchars($user['phonenumber']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Email</label> <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly> </div>
                            <div class="col-12"> <label class="form-label">Street Address</label> <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($user['address']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Barangay</label> <select class="form-select" name="barangay" required>
                                    <option value="<?= htmlspecialchars($user['barangay']) ?>" selected><?= htmlspecialchars($user['barangay']) ?></option>
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
                            <div class="col-md-6"> <label class="form-label">City / Municipality</label> <select class="form-select" name="city" required>
                                    <option value="<?= htmlspecialchars($user['city']) ?>" selected><?= htmlspecialchars($user['city']) ?></option>
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
                            <div class="col-md-6"> <label class="form-label">Zip Code</label> <input type="text" class="form-control" name="zipcode" value="<?= htmlspecialchars($user['zipcode']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Landmark</label> <input type="text" class="form-control" name="landmark" value="<?= htmlspecialchars($user['landmark']) ?>"> </div>
                        </div>
                    </div>
                    <div class="card p-4 mt-4">
                        <h5 class="fw-bold mb-3">Payment Method</h5>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" id="cod" value="Cash on Delivery" checked>
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
                                    <li>Enter the exact amount: <strong>₱<span id="gcash-amount"><?= number_format($total, 2) ?></span></strong></li>
                                    <li>Complete the payment in your GCash app</li>
                                    <li>Copy the reference number from your GCash transaction</li>
                                    <li>Paste the reference number in the field below</li>
                                </ol>

                                <!-- QR Code Display -->
                                <div class="text-center mb-3">
                                    <img src="../uploads/qr_codes/gcash_qr_sample.png" alt="GCash QR Code" class="img-fluid" style="max-width: 200px; border: 1px solid #ddd; border-radius: 8px;">
                                    <p class="small text-muted mt-2">Scan this QR code with your GCash app</p>
                                </div>

                                <!-- Reference Number Input -->
                                <div class="mb-3">
                                    <label for="gcash_reference" class="form-label fw-bold">GCash Reference Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="gcash_reference" name="gcash_reference" placeholder="Enter your GCash reference number">
                                    <div class="form-text">You can find this in your GCash app after completing the payment</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- Order Summary -->
                <div class="col-lg-5">
                    <div class="card p-4">
                        <h5 class="fw-bold mb-3">Order Summary</h5> <?php foreach ($cartItems as $item): ?> <div class="d-flex justify-content-between small mb-2"> <span><?= htmlspecialchars($item['name']) ?> × <?= (int)$item['quantity'] ?></span> <span>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></span> </div> <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold"> <span>Total</span> <span>₱<?= number_format($total, 2) ?></span> </div> <input type="hidden" name="total" value="<?= $total ?>"> <button type="submit" class="checkout-btn mt-3">Place Order</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <footer>
        <div class="container"> <small>© <?= date('Y') ?> Triple JH Chicken Trading — All rights reserved.</small> </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle payment method toggle
        document.addEventListener('DOMContentLoaded', function() {
            const codRadio = document.getElementById('cod');
            const gcashRadio = document.getElementById('gcash');
            const gcashSection = document.getElementById('gcash-payment-section');
            const gcashReferenceInput = document.getElementById('gcash_reference');
            const gcashAmountSpan = document.getElementById('gcash-amount');
            const totalAmount = <?= $total ?>;

            // Update GCash amount display
            gcashAmountSpan.textContent = totalAmount.toFixed(2);

            function togglePaymentMethod() {
                if (gcashRadio.checked) {
                    gcashSection.style.display = 'block';
                    gcashReferenceInput.required = true;
                } else {
                    gcashSection.style.display = 'none';
                    gcashReferenceInput.required = false;
                    gcashReferenceInput.value = '';
                }
            }

            codRadio.addEventListener('change', togglePaymentMethod);
            gcashRadio.addEventListener('change', togglePaymentMethod);

            // Form validation for GCash
            document.querySelector('form').addEventListener('submit', function(e) {
                if (gcashRadio.checked) {
                    const reference = gcashReferenceInput.value.trim();
                    if (!reference) {
                        e.preventDefault();
                        alert('Please enter your GCash reference number.');
                        gcashReferenceInput.focus();
                        return false;
                    }

                    // Basic validation for reference number format
                    if (reference.length < 8) {
                        e.preventDefault();
                        alert('Please enter a valid GCash reference number.');
                        gcashReferenceInput.focus();
                        return false;
                    }
                }
            });
        });
    </script>
</body>

</html>