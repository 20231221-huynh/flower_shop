<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrfToken() ?>">
    <title><?= e($title ?? 'Admin') ?> – Flower Shop Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #f4f6f9; }
        .sidebar { min-height: 100vh; background: #1a1a2e; width: 240px; position: fixed; top: 0; left: 0; z-index: 100; overflow-y: auto; }
        .sidebar .brand { padding: 20px 16px; border-bottom: 1px solid #2d2d4e; }
        .sidebar .nav-link { color: #adb5bd; padding: 10px 16px; border-radius: 6px; margin: 2px 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #e63946; color: #fff; }
        .sidebar .nav-link i { width: 20px; }
        .main-content { margin-left: 240px; padding: 24px; min-height: 100vh; }
        .topbar { background: #fff; border-bottom: 1px solid #dee2e6; padding: 12px 24px; margin: -24px -24px 24px; }
        .stat-card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    </style>
    <!-- FIX: Load jQuery 1 lần ở đây, không load lại ở footer -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Chart.js load sớm ở header để các view inline script dùng được -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>window.BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand">
        <a href="<?= BASE_URL ?>/admin" class="text-decoration-none">
            <span class="text-white fw-bold fs-5"><i class="bi bi-flower1 text-danger"></i> Flower Admin</span>
        </a>
    </div>
    <nav class="nav flex-column mt-3">
        <?php
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $path = '/' . ltrim(substr($uri, strlen($base)), '/');

        $navItems = [
            '/admin'            => ['bi-speedometer2', 'Dashboard'],
            '/admin/products'   => ['bi-flower2',      'Sản phẩm'],
            '/admin/categories' => ['bi-tag',           'Danh mục'],
            '/admin/orders'     => ['bi-bag-check',     'Đơn hàng'],
            '/admin/users'      => ['bi-people',        'Người dùng'],
            '/admin/coupons'    => ['bi-percent',        'Mã giảm giá'],
            '/admin/reports'    => ['bi-bar-chart-line', 'Báo cáo'],
        ];
        foreach ($navItems as $href => $item):
            // FIX: Active chính xác – dashboard chỉ active khi đúng /admin
            $isActive = ($href === '/admin')
                ? ($path === '/admin' || $path === '/admin/')
                : str_starts_with($path, $href);
        ?>
        <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="<?= BASE_URL . $href ?>">
            <i class="bi <?= $item[0] ?>"></i> <?= $item[1] ?>
        </a>
        <?php endforeach; ?>
        <hr class="border-secondary mx-3">
        <a class="nav-link" href="<?= BASE_URL ?>/" target="_blank">
            <i class="bi bi-house"></i> Xem Website
        </a>
        <a class="nav-link text-danger" href="<?= BASE_URL ?>/auth/logout">
            <i class="bi bi-box-arrow-right"></i> Đăng xuất
        </a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="topbar d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold"><?= e($title ?? '') ?></h5>
        <div class="text-muted small"><i class="bi bi-person-circle"></i> <?= e($_SESSION['full_name'] ?? 'Admin') ?></div>
    </div>

    <?php
    foreach (['success' => 'alert-success', 'error' => 'alert-danger', 'info' => 'alert-info', 'warning' => 'alert-warning'] as $type => $cls):
        if ($flash = ($_SESSION['flash'][$type] ?? null)):
            unset($_SESSION['flash'][$type]);
    ?>
      <div class="alert <?= $cls ?> alert-dismissible fade show mt-3">
        <?= $flash ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; endforeach; ?>
