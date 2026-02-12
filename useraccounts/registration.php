<?php
require_once('../config.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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

    body {
      min-height: 100vh;
      margin: 0;
      font-family: "Inter", "Segoe UI", sans-serif;
      background: radial-gradient(circle at top left, #fff7e3 0, #ffe4c1 40%, #fbd0a1 70%, #ffb347 100%);
      color: var(--accent-dark);
    }

    .card {
      background: var(--cream-panel);
      border-radius: 18px;
      border: 1px solid rgba(241, 143, 1, 0.3);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    }

    .card h4 {
      color: var(--accent-dark);
    }

    .form-control,
    .form-select {
      border-radius: 10px;
      border-color: rgba(109, 50, 9, 0.25);
    }

    .form-control:focus,
    .form-select:focus {
      border-color: var(--rich-amber);
      box-shadow: 0 0 0 0.15rem rgba(241, 143, 1, 0.35);
    }

    .btn-primary {
      background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
      border: none;
      border-radius: 999px;
      font-weight: 600;
      color: var(--accent-dark);
      box-shadow: 0 12px 30px rgba(241, 143, 1, 0.45);
      margin-bottom: 1rem;
    }

    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 16px 40px rgba(241, 143, 1, 0.55);
    }

    a.fw-semibold {
      color: var(--deep-chestnut);
    }

    a.fw-semibold:hover {
      color: var(--rich-amber);
    }
  </style>
</head>

<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">
        <div class="card shadow-sm border-0 rounded-3 p-4">
          <div class="card-body">
            <h4 class="mb-1 fw-semibold">Create an account</h4>
            <hr>

            <form action="registration.php" method="post" id="registerForm">

              <!-- Customer Account Section -->
              <h6 class="text-secondary mb-3">Customer Account</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <input type="text" class="form-control" name="firstname" id="firstname" placeholder="First Name" required>
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" name="lastname" id="lastname" placeholder="Last Name" required>
                </div>
                <div class="col-12">
                  <input type="email" class="form-control" name="email" id="email" placeholder="Email" required>
                </div>
                <div class="col-12">
                  <input type="text" class="form-control" name="phonenumber" id="phonenumber" placeholder="Mobile Number" required>
                </div>
                <div class="col-12">
                  <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                </div>
                <div class="col-12">
                  <input type="password" class="form-control" name="confirmpassword" id="confirmpassword" placeholder="Confirm Password" required>
                </div>
              </div>

              <!-- Address Section -->
              <h6 class="text-secondary mt-4 mb-3">Address</h6>
              <div class="row g-3">
                <div class="col-12">
                  <input type="text" class="form-control" name="address" id="address" placeholder="Street Address / House No." required>
                </div>
                <div class="col-md-6">
                  <select class="form-select" name="barangay" id="barangay" required>
                    <option value="">Barangay</option>
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
                  <select class="form-select" name="city" id="city" required>
                    <option value="">City / Municipality</option>
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
                  <input type="text" class="form-control" name="zipcode" id="zipcode" placeholder="ZIP Code" required>
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" name="landmark" id="landmark" placeholder="Landmark">
                </div>
              </div>

              <!-- Terms and Submit -->
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="terms" required>
                <label class="form-check-label" for="terms">
                  I agree to the Terms and Conditions
                </label>
              </div>

              <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg" id="register">Create Account</button>
              </div>

              <p class="text-center text-secondary mb-0">
                Or login to an existing <a href="login.php" class="text-decoration-none fw-semibold">account</a>
              </p>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    $(function() {
      // Add password validation pattern
      const passwordInput = document.getElementById('password');
      const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$/;

      passwordInput.setAttribute('pattern', passwordPattern.toString().replace(/^\/|\/[gimuy]*$/g, ''));
      passwordInput.setAttribute('title', 'Password must be 8-16 characters long and include at least one letter, one number, and one special character (@$!%*#?&)');

      $('#register').click(function(e) {
        e.preventDefault();

        var form = document.getElementById('registerForm');

        // Check if password meets requirements
        const password = $('#password').val();
        if (!passwordPattern.test(password)) {
          Swal.fire('Error', 'Password must be 8-16 characters long and include at least one letter, one number, and one special character (@$!%*#?&)', 'error');
          return;
        }

        if (!form.checkValidity()) {
          form.reportValidity();
          return;
        }

        $.ajax({
          type: 'POST',
          url: 'register_process.php',
          data: $('#registerForm').serialize(),
          dataType: 'json',
          success: function(response) {
            if (response.status === 'success') {
              Swal.fire('Success', response.message, 'success').then(() => {
                window.location.href = "login.php";
              });
            } else {
              Swal.fire('Error', response.message, 'error');
            }
          },
          error: function() {
            Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
          }
        });
      });
    });
  </script>
</body>

</html>
