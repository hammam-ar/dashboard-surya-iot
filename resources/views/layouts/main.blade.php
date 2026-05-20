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
        body { background-color: #f8f9fa; }
        .sidebar { min-width: 250px; max-width: 250px; background: #2c3e50; min-height: 100vh; color: white; transition: all 0.3s; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar a:hover { background: #34495e; color: white; }
        .sidebar a.active { background: #3498db; color: white; }
        .content { width: 100%; padding: 20px; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<div class="d-flex">
    <nav class="sidebar">
        <div class="p-4 text-center">
            <h4>IoT PLTS</h4>
            <hr>
        </div>
        <a href="{{ url('/') }}" class="{{ Request::is('/') ? 'active' : '' }}">
            <i class="fa fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a href="{{ url('/history') }}" class="{{ Request::is('history') ? 'active' : '' }}">
            <i class="fa fa-history me-2"></i> History Data
        </a>

        <form method="POST" action="{{ route('logout') }}" class="mt-auto pt-3">
            @csrf
            <a href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-danger">
                <i class="fa fa-sign-out-alt me-2"></i> Keluar
            </a>
        </form>
    </nav>

    <div class="content">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
