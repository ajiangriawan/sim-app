<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT Balink Sakti Synergy - Solusi Angkutan Terpercaya</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .logo>img {
            max-width: 40px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
            font-weight: 500;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        .btn-login {
            background: white;
            color: #1e3c72;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
        }

        .hero {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 120px 2rem 80px;
            text-align: center;
            margin-top: 70px;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: fadeInUp 1s;
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: fadeInUp 1s 0.2s backwards;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            animation: fadeInUp 1s 0.4s backwards;
        }

        .btn-primary, .btn-secondary {
            padding: 1rem 2rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: #ff6b35;
            color: white;
        }

        .btn-primary:hover {
            background: #ff5722;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 107, 53, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: white;
            color: #1e3c72;
        }

        .features {
            padding: 80px 2rem;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #1e3c72;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            color: #1e3c72;
            margin-bottom: 1rem;
        }

        .services {
            padding: 80px 2rem;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .service-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            transition: transform 0.3s;
        }

        .service-item:hover {
            transform: scale(1.05);
        }

        .service-item h3 {
            margin-bottom: 1rem;
        }

        .stats {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 60px 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item h2 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .contact {
            padding: 80px 2rem;
            background: #f8f9fa;
        }

        .contact-content {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }

        .contact-item {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .footer {
            background: #1a1a2e;
            color: white;
            padding: 2rem;
            text-align: center;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .nav-links {
                display: none;
            }

            .cta-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <img src="{{ asset('logo.png') }}" alt="bss logo">
                PT Balink Sakti Synergy
            </div>
            <ul class="nav-links">
                <li><a href="#beranda">Beranda</a></li>
                <li><a href="#layanan">Layanan</a></li>
                <!-- <li><a href="#tentang">Tentang</a></li> -->
                <li><a href="#kontak">Kontak</a></li>
            </ul>
            <a href="/admin" class="btn-login">Dashboard</a>
        </nav>
    </header>

    <section class="hero" id="beranda">
        <div class="hero-content">
            <h1>Solusi Angkutan Terpercaya untuk Bisnis Anda</h1>
            <p>PT Balink Sakti Synergy menyediakan layanan jasa angkutan profesional dengan armada modern dan tim berpengalaman untuk memenuhi kebutuhan logistik Anda</p>
            <div class="cta-buttons">
                <a href="#kontak" class="btn-primary">Hubungi Kami</a>
                <a href="#layanan" class="btn-secondary">Lihat Layanan</a>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2 class="section-title">Mengapa Memilih Kami?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Pengiriman Cepat</h3>
                    <p>Sistem logistik yang efisien memastikan barang Anda tiba tepat waktu</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h3>Aman & Terpercaya</h3>
                    <p>Armada terawat dengan asuransi penuh untuk keamanan maksimal</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📍</div>
                    <h3>Tracking Real-time</h3>
                    <p>Pantau posisi kiriman Anda kapan saja dengan sistem tracking canggih</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>Harga Kompetitif</h3>
                    <p>Tarif yang terjangkau dengan kualitas layanan terbaik</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>Tim Profesional</h3>
                    <p>Driver berpengalaman dan customer service yang responsif</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🌐</div>
                    <h3>Jangkauan Luas</h3>
                    <p>Melayani pengiriman ke seluruh Indonesia</p>
                </div>
            </div>
        </div>
    </section>

    <section class="services" id="layanan">
        <div class="container">
            <h2 class="section-title">Layanan Kami</h2>
            <div class="services-grid">
                <div class="service-item">
                    <h3>🚚 Cargo Darat</h3>
                    <p>Pengiriman barang via jalur darat dengan armada truk berbagai kapasitas</p>
                </div>
                <div class="service-item">
                    <h3>📦 Paket Ekspres</h3>
                    <p>Layanan pengiriman kilat untuk kebutuhan mendesak</p>
                </div>
                <div class="service-item">
                    <h3>🏢 Corporate Solution</h3>
                    <p>Solusi logistik terintegrasi untuk kebutuhan perusahaan</p>
                </div>
                <div class="service-item">
                    <h3>🚛 Sewa Kendaraan</h3>
                    <p>Rental armada harian, mingguan, atau bulanan</p>
                </div>
            </div>
        </div>
    </section>

    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h2>1000+</h2>
                    <p>Klien Puas</p>
                </div>
                <div class="stat-item">
                    <h2>50+</h2>
                    <p>Armada Kendaraan</p>
                </div>
                <div class="stat-item">
                    <h2>15+</h2>
                    <p>Tahun Pengalaman</p>
                </div>
                <div class="stat-item">
                    <h2>100%</h2>
                    <p>Komitmen Kualitas</p>
                </div>
            </div>
        </div>
    </section>

    <section class="contact" id="kontak">
        <div class="container">
            <div class="contact-content">
                <h2 class="section-title">Hubungi Kami</h2>
                <p>Siap melayani kebutuhan angkutan Anda. Hubungi kami sekarang untuk konsultasi gratis!</p>
                <div class="contact-info">
                    <div class="contact-item">
                        <span style="font-size: 2rem;">📞</span>
                        <div>
                            <strong>Telepon</strong>
                            <p>+62 821-7630-0057</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span style="font-size: 2rem;">📧</span>
                        <div>
                            <strong>Email</strong>
                            <p>info@balinksakti.co.id</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span style="font-size: 2rem;">📍</span>
                        <div>
                            <strong>Alamat</strong>
                            <p>Palembang, Indonesia</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <p>&copy; 2025 PT Balink Sakti Synergy. All rights reserved.</p>
        <p>Solusi Angkutan Terpercaya Indonesia</p>
    </footer>
</body>
</html>