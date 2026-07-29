<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Monitoring Panel Surya</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Membaca memori lokal sebelum elemen HTML dirender agar tidak berkedip silau
        if (localStorage.getItem('tema') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>

    <style>
        /* =========================================
           1. PENGATURAN DARK MODE GLOBAL
           ========================================= */
        html.dark-mode body {
            background-color: #0f172a !important;
            color: #f8fafc;
        }

        /* Mematikan paksa warna putih dari kelas bawaan Bootstrap .bg-light */
        html.dark-mode .bg-light {
            background-color: #0f172a !important;
            color: #f8fafc;
        }

        /* Warna Kartu */
        html.dark-mode .card {
            background-color: #1e293b !important;
            border-top: 1px solid #334155 !important;
            border-right: 1px solid #334155 !important;
            border-bottom: 1px solid #334155 !important;
            box-shadow: 0 4px 6px rgba(0,0,0,0.4) !important;
        }

        /* Teks dan Konten Pendukung */
        html.dark-mode span,
        html.dark-mode label,
        html.dark-mode .text-muted { color: #94a3b8 !important; }

        html.dark-mode h2,
        html.dark-mode h3,
        html.dark-mode h5,
        html.dark-mode h6,
        html.dark-mode b {
            color: #f8fafc !important;
        }

        /* Modifikasi Navigasi Sidebar & Navbar */
        html.dark-mode .navbar,
        html.dark-mode #sidebar {
            background-color: #1e293b !important;
            border-bottom: 1px solid #334155 !important;
        }
        html.dark-mode #sidebar { border-right: 1px solid #334155 !important; }

        /* Penyesuaian Komponen di Dashboard (Pill & Switch) */
        html.dark-mode .bg-opacity-10 {
            background-color: rgba(255, 255, 255, 0.05) !important;
            border-color: #334155 !important;
        }
        html.dark-mode .bg-white {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
        }
        html.dark-mode .form-switch .form-check-input {
            background-color: #334155;
            border-color: #475569;
        }

        /* =========================================
           2. PENGATURAN TATA LETAK & SIDEBAR (LIGHT/DEFAULT)
           ========================================= */
        body {
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        #sidebar {
            background: #2c3e50;
            color: white;
            z-index: 1040;
        }
        #sidebar a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        #sidebar a:hover { background: #34495e; color: white; }
        #sidebar a.active { background: #3498db; color: white; }

        .sidebar-transition {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar-open { width: 250px; }
        .sidebar-closed {
            width: 0;
            padding-left: 0 !important;
            padding-right: 0 !important;
            border: none;
        }

        @media (max-width: 768px) {
            #sidebar {
                position: fixed;
                height: calc(100vh - 56px);
                box-shadow: 2px 0 5px rgba(0,0,0,0.5);
            }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-dark bg-dark px-3 shadow-sm w-100 z-3 d-flex justify-content-between align-items-center" style="height: 56px;">
    <div class="d-flex align-items-center">
        <button class="btn btn-outline-light border-0 me-3" id="sidebarToggle">
            <i class="fa fa-bars"></i>
        </button>
        <span class="navbar-brand mb-0 h4 text-white d-none d-sm-inline">IoT PLTS Dashboard</span>
        <span class="navbar-brand mb-0 h4 text-white d-inline d-sm-none">IoT PLTS</span>
    </div>

    <div class="d-flex align-items-center dropdown">
        <button id="btn-tema" class="btn btn-outline-light border-0 me-3 rounded-circle" onclick="toggleTema()" title="Ubah Tema">
            <i class="fa fa-moon" id="icon-tema"></i>
        </button>
        <a href="#" class="text-white text-decoration-none dropdown-toggle d-flex align-items-center" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-user-circle fa-xl"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow mt-2 border-0" aria-labelledby="userDropdown">
            <li><span class="dropdown-item-text text-muted small mb-0 pb-0">Masuk sebagai:</span></li>
            <li>
                    <span class="dropdown-item-text fw-bold text-dark fs-6 pt-1">
                        {{ Auth::check() ? Auth::user()->name : 'Tamu' }}
                    </span>
            </li>
        </ul>
    </div>
</nav>

<div class="d-flex flex-grow-1 position-relative">

    <div id="sidebar" class="d-flex flex-column p-3 sidebar-transition sidebar-open">
        <a href="{{ url('/') }}" class="nav-link text-white mb-2 p-2 {{ Request::is('/') ? 'bg-primary rounded' : '' }}">
            <i class="fa fa-tachometer-alt me-2"></i> <span class="menu-text">Dashboard</span>
        </a>
        <a href="{{ url('/history') }}" class="nav-link text-white mb-auto p-2 {{ Request::is('history') ? 'bg-primary rounded' : '' }}">
            <i class="fa fa-history me-2"></i> <span class="menu-text">History Data</span>
        </a>

        <hr class="text-secondary">

        @auth
            <!-- 🔒 Jika pengguna sudah login, tampilkan tombol Keluar asli -->
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();" class="nav-link text-danger p-2">
                    <i class="fa fa-sign-out-alt me-2"></i> <span class="menu-text">Keluar</span>
                </a>
            </form>
        @else
            <!-- 🔓 Jika bertindak sebagai TAMU, tampilkan tombol Login berwarna biru -->
            <a href="{{ route('login') }}" class="nav-link text-primary fw-bold p-2">
                <i class="fa fa-sign-in-alt me-2"></i> <span class="menu-text">Login</span>
            </a>
        @endauth
    </div>

    <div class="content flex-grow-1 p-3 p-md-4 bg-light w-100" style="overflow-y: auto; height: calc(100vh - 56px);">
        @yield('content')
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- Logika Sidebar ---
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');

        toggleBtn.addEventListener('click', function() {
            if (sidebar.classList.contains('sidebar-open')) {
                sidebar.classList.remove('sidebar-open');
                sidebar.classList.add('sidebar-closed');
            } else {
                sidebar.classList.remove('sidebar-closed');
                sidebar.classList.add('sidebar-open');
            }
        });

        if (window.innerWidth <= 768) {
            sidebar.classList.remove('sidebar-open');
            sidebar.classList.add('sidebar-closed');
        }

        // --- Logika Ikon Tema (Menyesuaikan saat halaman dimuat) ---
        const iconTema = document.getElementById('icon-tema');
        if (localStorage.getItem('tema') === 'dark') {
            iconTema.classList.remove('fa-moon');
            iconTema.classList.add('fa-sun');
        }
    });

    // --- Fungsi Toggle Tema Global ---
    function toggleTema() {
        const root = document.documentElement;
        const iconTema = document.getElementById('icon-tema');

        root.classList.toggle('dark-mode');

        if (root.classList.contains('dark-mode')) {
            iconTema.classList.remove('fa-moon');
            iconTema.classList.add('fa-sun');
            localStorage.setItem('tema', 'dark');
        } else {
            iconTema.classList.remove('fa-sun');
            iconTema.classList.add('fa-moon');
            localStorage.setItem('tema', 'light');
        }
    }
</script>
</body>
</html>
