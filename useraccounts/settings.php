<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch cart count
try {
    $cartStmt = $db->prepare("SELECT SUM(quantity) AS count FROM cart WHERE user_id = ?");
    $cartStmt->execute([$user_id]);
    $cartCount = (int) $cartStmt->fetchColumn();
} catch (PDOException $e) {
    $cartCount = 0;
}

// Fetch user data
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: ../index.php");
        exit;
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                try {
                    $firstname = trim($_POST['firstname']);
                    $lastname = trim($_POST['lastname']);
                    $email = trim($_POST['email']);
                    $phonenumber = trim($_POST['phonenumber']);
                    $address = trim($_POST['address']);
                    $barangay = trim($_POST['barangay']);
                    $city = trim($_POST['city']);
                    $zipcode = trim($_POST['zipcode']);
                    $landmark = trim($_POST['landmark']);

                    // Check if email is already taken by another user
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $user_id]);
                    if ($stmt->fetch()) {
                        $error_message = "Email is already taken by another account.";
                        break;
                    }

                    $stmt = $db->prepare("
                        UPDATE users SET
                        firstname = ?, lastname = ?, email = ?, phonenumber = ?,
                        address = ?, barangay = ?, city = ?, zipcode = ?, landmark = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $firstname,
                        $lastname,
                        $email,
                        $phonenumber,
                        $address,
                        $barangay,
                        $city,
                        $zipcode,
                        $landmark,
                        $user_id
                    ]);

                    $success_message = "Profile updated successfully!";

                    // Refresh user data
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $error_message = "Error updating profile: " . $e->getMessage();
                }
                break;

            case 'change_password':
                try {
                    $current_password = $_POST['current_password'];
                    $new_password = $_POST['new_password'];
                    $confirm_password = $_POST['confirm_password'];

                    // Verify current password
                    if (!password_verify($current_password, $user['password'])) {
                        $error_message = "Current password is incorrect.";
                        break;
                    }

                    if ($new_password !== $confirm_password) {
                        $error_message = "New passwords do not match.";
                        break;
                    }

                    if (strlen($new_password) < 6) {
                        $error_message = "New password must be at least 6 characters long.";
                        break;
                    }

                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);

                    $success_message = "Password changed successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error changing password: " . $e->getMessage();
                }
                break;

            case 'delete_account':
                try {
                    $delete_password = $_POST['delete_password'];

                    // Verify password
                    if (!password_verify($delete_password, $user['password'])) {
                        $error_message = "Password is incorrect.";
                        break;
                    }

                    // Start transaction for data cleanup
                    $db->beginTransaction();

                    // Delete cart items
                    $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
                    $stmt->execute([$user_id]);

                    // Delete pending delivery items and pending deliveries
                    $stmt = $db->prepare("DELETE FROM pending_delivery_items WHERE pending_delivery_id IN (SELECT id FROM pending_delivery WHERE user_id = ?)");
                    $stmt->execute([$user_id]);

                    $stmt = $db->prepare("DELETE FROM pending_delivery WHERE user_id = ?");
                    $stmt->execute([$user_id]);

                    // Delete history delivery items and history deliveries
                    $stmt = $db->prepare("DELETE FROM history_of_delivery_items WHERE history_id IN (SELECT id FROM history_of_delivery WHERE user_id = ?)");
                    $stmt->execute([$user_id]);

                    $stmt = $db->prepare("DELETE FROM history_of_delivery WHERE user_id = ?");
                    $stmt->execute([$user_id]);

                    // Finally delete the user account
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);

                    // Commit transaction
                    $db->commit();

                    // Destroy session and redirect
                    session_destroy();
                    header("Location: ../index.php?account_deleted=1");
                    exit;
                } catch (PDOException $e) {
                    $db->rollBack();
                    $error_message = "Error deleting account: " . $e->getMessage();
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | Triple JH Chicken Trading</title>
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

        .settings-container {
            max-width: 1000px;
            margin: 60px auto;
            flex: 1;
        }

        .card {
            border-radius: 18px;
            border: 1px solid rgba(241, 143, 1, 0.35);
            background: var(--cream-panel);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.12);
            margin-bottom: 2rem;
        }

        .card-header {
            background: rgba(255, 247, 227, 0.95);
            border-bottom: 1px solid rgba(241, 143, 1, 0.35);
            border-radius: 18px 18px 0 0 !important;
            padding: 1.5rem;
        }

        .card-body {
            padding: 2rem;
        }

        .btn-primary {
            background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 999px;
            font-weight: 600;
            color: var(--accent-dark);
            box-shadow: 0 10px 26px rgba(241, 143, 1, 0.45);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 34px rgba(241, 143, 1, 0.55);
        }

        .btn-danger {
            background: #dc3545;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 999px;
            font-weight: 600;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .form-label {
            font-weight: 500;
            color: var(--accent-dark);
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            border: 1px solid rgba(109, 50, 9, 0.25);
            border-radius: 10px;
            padding: 0.75rem;
            font-size: 0.95rem;
            background-color: #fff;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--rich-amber);
            box-shadow: 0 0 0 0.15rem rgba(241, 143, 1, 0.35);
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .alert-warning {
            background-color: #fff7e3;
            border-color: #ffe4c1;
            color: #7a3a12;
        }

        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
            transition: all 0.2s ease;
            border-radius: 999px;
        }

        .btn-outline-danger:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #fff;
        }

        .btn-outline-danger:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .form-check.border:hover {
            border-color: #dc3545 !important;
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
                        <a class="nav-link" href="../about.php">
                            <i class="fas fa-info-circle"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../orders/orders.php">
                            <i class="fas fa-shopping-bag"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link position-relative" href="../carts/cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
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

    <div class="settings-container">
        <h2 class="fw-bold mb-4">Account Settings</h2>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="fw-bold mb-0">Profile Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="firstname"
                                value="<?= htmlspecialchars($user['firstname']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="lastname"
                                value="<?= htmlspecialchars($user['lastname']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email"
                                value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phonenumber"
                                value="<?= htmlspecialchars($user['phonenumber']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Street Address</label>
                            <input type="text" class="form-control" name="address"
                                value="<?= htmlspecialchars($user['address']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barangay</label>
                            <select class="form-select" name="barangay" required>
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
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City / Municipality</label>
                            <select class="form-select" name="city" required>
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
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ZIP Code</label>
                            <input type="text" class="form-control" name="zipcode"
                                value="<?= htmlspecialchars($user['zipcode']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Landmark</label>
                            <input type="text" class="form-control" name="landmark"
                                value="<?= htmlspecialchars($user['landmark']) ?>">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header">
                <h5 class="fw-bold mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" minlength="6" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-danger">Change Password</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Account -->
        <div class="card">
            <div class="card-header">
                <h5 class="fw-bold mb-0 text-danger">⚠️ Delete Account</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning border-warning" role="alert">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <svg width="24" height="24" fill="currentColor" class="text-warning" viewBox="0 0 16 16">
                                <path
                                    d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                            </svg>
                        </div>
                        <div>
                            <h6 class="alert-heading fw-bold mb-2">Permanent Account Deletion</h6>
                            <p class="mb-2">This action cannot be undone. Deleting your account will permanently remove:
                            </p>
                            <ul class="mb-0 small">
                                <li>Your profile and personal information</li>
                                <li>All order history and delivery records</li>
                                <li>Items in your shopping cart</li>
                                <li>All associated account data</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <form method="POST" action="" id="deleteAccountForm">
                    <input type="hidden" name="action" value="delete_account">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Confirm Password</label>
                            <input type="password" class="form-control" name="delete_password"
                                placeholder="Enter your current password" required>
                            <div class="form-text">You must enter your current password to confirm account deletion.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check p-3 border rounded">
                                <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                                <label class="form-check-label fw-semibold" for="confirmDelete">
                                    I understand that this action is permanent and cannot be undone
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-outline-danger w-100" id="deleteAccountBtn" disabled>
                            <svg width="16" height="16" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path
                                    d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5ZM11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H2.506a.58.58 0 0 0-.01 0H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1h-.995a.59.59 0 0 0-.01 0H11Zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5h9.916Zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5Z" />
                            </svg>
                            Delete My Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Enable/disable delete button based on checkbox
        document.getElementById('confirmDelete').addEventListener('change', function () {
            const deleteBtn = document.getElementById('deleteAccountBtn');
            deleteBtn.disabled = !this.checked;
        });

        // Handle account deletion with confirmation
        document.getElementById('deleteAccountForm').addEventListener('submit', function (e) {
            e.preventDefault();

            Swal.fire({
                title: 'Are you absolutely sure?',
                text: "This action cannot be undone! Your account and all data will be permanently deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete my account',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting account...',
                        text: 'Please wait while we delete your account and all associated data.',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Submit the form
                    this.submit();
                }
            });
        });
    </script>
</body>

</html>
