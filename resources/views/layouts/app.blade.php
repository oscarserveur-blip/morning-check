<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        
        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('btbs-logo.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('btbs-logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <!-- Custom CSS -->
        <style>
            :root {
                --brand-primary: #003DA5; /* Bleu Bouygues Telecom */
                --brand-primary-dark: #002A73; /* Bleu Bouygues foncé */
                --brand-accent: #E60012; /* Rouge Bouygues */
                --bg-muted: #f5f5f5;
            }
            .sidebar {
                min-height: 100vh;
                background: #003DA5; /* Bleu Bouygues Telecom */
                color: white;
            }
            .sidebar .nav-link {
                color: rgba(255,255,255,.8);
                padding: 1rem;
                margin: 0.2rem 0;
                border-radius: 0.5rem;
            }
            .sidebar .nav-link:hover {
                color: white;
                background: rgba(255,255,255,.1);
            }
            .sidebar .nav-link.active {
                background: #E60012; /* Rouge Bouygues */
                color: white;
            }
            .sidebar .nav-link i {
                margin-right: 0.5rem;
            }
            .main-content { background: var(--bg-muted); }
            .card {
                border: none;
                border-radius: 0.75rem;
                box-shadow: 0 10px 20px rgba(2, 8, 20, 0.06), 0 2px 6px rgba(2, 8, 20, 0.06);
                overflow: hidden;
            }
            .card-header {
                background: linear-gradient(180deg, rgba(0,61,165,0.12), rgba(0,61,165,0.06));
                border-bottom: 1px solid rgba(0,61,165,.25);
                font-weight: 600;
                color: #0f172a;
            }
            .navbar {
                background: white;
                box-shadow: 0 2px 4px rgba(0,0,0,.08);
            }
            .navbar-brand {
                font-size: 1.25rem;
                font-weight: 600;
            }
            /* Buttons - add peps */
            .btn-primary {
                background-color: var(--brand-primary);
                border-color: var(--brand-primary);
                box-shadow: 0 6px 14px rgba(0,61,165,0.25);
            }
            .btn-primary:hover,
            .btn-primary:focus {
                background-color: var(--brand-primary-dark);
                border-color: var(--brand-primary-dark);
            }
            /* Tables */
            .table thead th {
                background: var(--brand-primary);
                color: #fff;
                border-color: var(--brand-primary-dark);
            }
            .table-striped>tbody>tr:nth-of-type(odd)>* { background-color: rgba(0,61,165,0.04); }
            .table-hover tbody tr:hover { background-color: rgba(0,61,165,0.12); }
            .table td, .table th { vertical-align: middle; }
            /* Chips / badges accent */
            .badge.bg-success { background-color: var(--brand-accent) !important; }
            /* Spacing tightening */
            main { padding-top: 1rem !important; }
            .card-body { padding: 1rem 1.25rem; }
            .user-dropdown .dropdown-toggle::after {
                display: none;
            }
            .user-dropdown .dropdown-menu {
                margin-top: 0.5rem;
            }
            .user-dropdown .dropdown-item {
                padding: 0.5rem 1rem;
            }
            .user-dropdown .dropdown-item i {
                margin-right: 0.5rem;
            }
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2 px-0 sidebar">
                    <div class="d-flex flex-column p-3">
                        <a href="{{ route('dashboard') }}" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                            <img src="{{ asset('btbs-logo.png') }}" alt="Bouygues Telecom Business Solutions" class="me-2" style="height: 60px; width: auto; max-width: 200px;">
                            <span class="fs-4 d-none d-md-inline">Check du Matin</span>
                        </a>
                        <hr>
                        <ul class="nav nav-pills flex-column mb-auto">
                            <li class="nav-item">
                                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                    <i class="bi bi-speedometer2"></i>
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                                    <i class="bi bi-people"></i>
                                    Clients
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('templates.index') }}" class="nav-link {{ request()->routeIs('templates.*') ? 'active' : '' }}">
                                    <i class="bi bi-layout-text-window-reverse"></i>
                                    Templates
                                </a>
                            </li>
                            @if(Auth::user() && Auth::user()->isAdmin())
                            <li>
                                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                    <i class="bi bi-person-gear"></i>
                                    Utilisateurs
                                </a>
                            </li> 
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Main content -->
                <div class="col-md-9 ms-sm-auto col-lg-10 px-0">
                    <!-- Header -->
                    <nav class="navbar navbar-expand-lg navbar-light px-4 py-2">
                        <div class="container-fluid">
                            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                            
                            <div class="collapse navbar-collapse" id="navbarNav">
                                <ul class="navbar-nav me-auto">
                                    @if (isset($header))
                                        <li class="nav-item">
                                            <span class="nav-link">{{ $header }}</span>
                                        </li>
                                    @endif
                                </ul>
                                
                                <!-- User Dropdown -->
                                <div class="dropdown user-dropdown">
                                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle {{ request()->routeIs('profile.*') ? 'active' : '' }}" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-person-circle fs-4 me-2"></i>
                                        <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                                                <i class="bi bi-person"></i>
                                                Profile
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-box-arrow-right"></i>
                                                    Déconnexion
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </nav>

                    <!-- Content -->
                    <main class="px-4 py-3">
                        @yield('content')
                    </main>
                </div>
            </div>
        </div>

        <!-- Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <!-- Custom Scripts -->
        @stack('scripts')
    </body>
</html>
