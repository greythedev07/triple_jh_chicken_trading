<?php
session_start();
require_once('config.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: useraccounts/login.php");
    exit;
}

// Fetch cart count
$cartCount = 0;
try {
    $stmt = $db->prepare("SELECT SUM(quantity) AS count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    // Silently fail on db error
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About Us | Triple JH Chicken Trading</title>
    <link rel="icon" href="img/logo.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/footer_header.css">
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
            --text-muted: rgba(109, 50, 9, 0.7);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            color: var(--accent-dark);
            background: var(--buttered-sand);
            line-height: 1.6;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            color: var(--accent-dark);
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: inset 0 0 40px rgba(255, 255, 255, 0.4);
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-block: 2rem 1rem;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            font-weight: 400;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto 2rem;
        }

        /* Content Sections */
        .section {
            padding: 5rem 0;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--secondary);
        }

        /* Mission Cards */
        .mission-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            height: 100%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .mission-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Mission/About/Vision Cards */
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 10px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
        }

        .card .fa-3x {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .mission-icon {
            font-size: 2.5rem;
            color: var(--rich-amber);
            margin-bottom: 1.5rem;
        }

        .mission-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--deep-chestnut);
        }

        .mission-card p {
            color: var(--accent-dark);
            margin-bottom: 0;
        }

        /* Team Section */
        .team-section {
            background-color: #f8f9fa;
        }

        .team-member {
            text-align: center;
            margin-bottom: 2rem;
        }

        .team-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1.5rem;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .team-member h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .team-member p {
            color: var(--gray);
            margin-bottom: 0;
        }

        /* Contact Section */
        .contact-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            height: 100%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .contact-icon {
            font-size: 2rem;
            color: var(--rich-amber);
            margin-bottom: 1.5rem;
        }

        .contact-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .contact-card p {
            color: var(--gray);
            margin-bottom: 0;
        }

        .btn-amber-primary,
        .btn-amber-secondary {
            border: none;
            border-radius: 30px;
            padding: 0.65rem 1.5rem;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-amber-primary {
            background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            color: var(--accent-dark);
            box-shadow: 0 8px 20px rgba(255, 153, 0, 0.35);
        }

        .btn-amber-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 30px rgba(255, 153, 0, 0.45);
        }


        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }

            .section {
                padding: 3rem 0;
            }

            .mission-card,
            .contact-card {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="img/logo.jpg" alt="Triple JH Chicken Trading"
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Shop
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">
                            <i class="fas fa-info-circle"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders/orders.php">
                            <i class="fas fa-shopping-bag"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link position-relative" href="carts/cart.php">
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
                        <a class="nav-link" href="useraccounts/settings.php">
                            <i class="fas fa-user"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Logo Upload Notice (can be removed after uploading the logo) -->
    <?php if (!file_exists('img/logo.jpg')): ?>
        <div class="alert alert-warning mb-0 rounded-0 text-center py-2" role="alert" style="margin-top: 56px;">
            <i class="fas fa-info-circle me-2"></i> Please upload your logo as 'logo.jpg' in the 'img' folder.
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section py-5" style="padding-top: 76px !important;">
        <div class="container text-center">
            <h1 class="hero-title">About Us</h1>
            <p class="hero-subtitle">We are committed to providing the highest quality chicken products with exceptional
                service and care.</p>
        </div>
    </section>


    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-amber-primary">Shop Now</a>
    </div>

    <!-- Mission, About, Vision Cards -->
    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <!-- Mission Card -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-bullseye fa-3x text-primary"></i>
                            </div>
                            <h3 class="h4 mb-3">Mission</h3>
                            <p class="text-muted mb-0">To consistently provide our customers with the freshest,
                                highest-quality chicken products, sourced responsibly and delivered with exceptional
                                service. We aim to be a cornerstone of every family's table, ensuring healthy and
                                delicious meals through our commitment to quality and food safety.</p>
                        </div>
                    </div>
                </div>

                <!-- About Card -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-info-circle fa-3x text-primary"></i>
                            </div>
                            <h3 class="h4 mb-3">About</h3>
                            <p class="text-muted mb-0">Triple JH Chicken Trading is a trusted name in the poultry
                                industry, dedicated to delivering premium quality chicken products. With years of
                                experience, we've built our reputation on freshness, quality, and exceptional customer
                                service. Our team is committed to maintaining the highest standards in every aspect of
                                our business.</p>
                        </div>
                    </div>
                </div>

                <!-- Vision Card -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-eye fa-3x text-primary"></i>
                            </div>
                            <h3 class="h4 mb-3">Vision</h3>
                            <p class="text-muted mb-0">To become the leading and most trusted online supplier of poultry
                                products in the region, known for our unwavering commitment to quality, customer
                                satisfaction, and innovation in food delivery. We envision a future where everyone has
                                convenient access to fresh, farm-quality chicken, right at their doorstep.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Team Section -->
    <section class="section team-section">
        <div class="container">
            <h2 class="section-title">Our Team</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="team-member">
                        <img src="img/blank_pfp.jpg" alt="Team Member" class="team-img">
                        <h4>John Smith</h4>
                        <p>Founder & CEO</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-member">
                        <img src="img/blank_pfp.jpg" alt="Team Member" class="team-img">
                        <h4>Sarah Johnson</h4>
                        <p>Operations Manager</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-member">
                        <img src="img/blank_pfp.jpg" alt="Team Member" class="team-img">
                        <h4>Michael Chen</h4>
                        <p>Head of Quality</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="section-title">Get In Touch</h2>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="contact-card">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <h3>Visit Us</h3>
                                <p>123 Poultry Street<br>Chickentown, CT 12345</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="contact-card">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <h3>Call Us</h3>
                                <p>+1 (555) 123-4567<br>Mon-Fri, 9am-6pm</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <div class="footer-links">
                <a href="dashboard.php">Shop</a>
                <a href="about.php">About</a>
                <a href="#">Terms</a>
                <a href="#">Privacy</a>
            </div>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
            <p class="copyright">&copy; <?= date('Y') ?> Triple JH Chicken Trading. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
