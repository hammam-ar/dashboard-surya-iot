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

    <style>
        body {
            background-color: #f8f9fa;
            overflow-x: hidden; /* Mencegah layar bisa digeser ke samping */
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        /* Desain Sidebar */
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

        /* Animasi pergerakan sidebar */
        .sidebar-transition {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            white-space: nowrap;
            overflow: hidden;
        }

        /* Kondisi Terbuka (Default Laptop) */
        .sidebar-open {
            width: 250px;
        }

        /* Kondisi Tertutup */
        .sidebar-closed {
            width: 0;
            padding-left: 0 !important;
            padding-right: 0 !important;
            border: none;
        }

        /* Logika Khusus Layar HP */
        @media (max-width: 768px) {
            #sidebar {
                position: fixed; /* Berubah jadi fixed agar melayang di atas konten */
                height: calc(100vh - 56px); /* Tinggi layar dikurangi tinggi navbar */
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

    <div class="dropdown">
        <a href="#" class="text-white text-decoration-none dropdown-toggle d-flex align-items-center" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-user-circle fa-xl"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow mt-2 border-0" aria-labelledby="userDropdown">
            <li>
                <span class="dropdown-item-text text-muted small mb-0 pb-0">Masuk sebagai:</span>
            </li>
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
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();" class="nav-link text-danger p-2">
                <i class="fa fa-sign-out-alt me-2"></i> <span class="menu-text">Keluar</span>
            </a>
        </form>
    </div>

    <div class="content flex-grow-1 p-3 p-md-4 bg-light w-100" style="overflow-y: auto; height: calc(100vh - 56px);">
        @yield('content')
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');

        // Fungsi klik hamburger
        toggleBtn.addEventListener('click', function() {
            if (sidebar.classList.contains('sidebar-open')) {
                sidebar.classList.remove('sidebar-open');
                sidebar.classList.add('sidebar-closed');
            } else {
                sidebar.classList.remove('sidebar-closed');
                sidebar.classList.add('sidebar-open');
            }
        });

        // Auto-tutup sidebar jika diakses dari HP saat pertama kali dimuat
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('sidebar-open');
            sidebar.classList.add('sidebar-closed');
        }
    });
</script>
</body>
</html>
