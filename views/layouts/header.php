<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrfToken() ?>">
    <title><?= e($title ?? 'Flower Shop') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
    </script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-danger sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold fs-4" href="<?= BASE_URL ?>/">
      <i class="bi bi-flower1"></i> Flower Shop
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/">Trang chủ</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/products">Sản phẩm</a></li>
      </ul>
      <form class="d-flex me-3" action="<?= BASE_URL ?>/products" method="GET">
        <input class="form-control form-control-sm me-2" type="search" name="q"
               placeholder="Tìm hoa..." value="<?= e($_GET['q'] ?? '') ?>">
        <button class="btn btn-outline-light btn-sm" type="submit"><i class="bi bi-search"></i></button>
      </form>
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link position-relative" href="<?= BASE_URL ?>/cart">
            <i class="bi bi-cart3 fs-5"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark"
                  id="cart-badge" style="display:none">0</span>
          </a>
        </li>
        <?php if (isLoggedIn()): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle"></i> <?= e($_SESSION['full_name']) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/profile"><i class="bi bi-person"></i> Hồ sơ</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/orders"><i class="bi bi-bag"></i> Đơn hàng</a></li>
              <?php if (isAdmin()): ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/admin"><i class="bi bi-shield"></i> Admin</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/auth/login">Đăng nhập</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/auth/register">Đăng ký</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="py-4">
  <div class="container">
    <?php
    // FIX: Xử lý flash messages đầy đủ các loại
    foreach (['success' => 'alert-success', 'error' => 'alert-danger', 'info' => 'alert-info', 'warning' => 'alert-warning'] as $type => $cls):
        if ($flash = ($_SESSION['flash'][$type] ?? null)):
            unset($_SESSION['flash'][$type]);
    ?>
      <div class="alert <?= $cls ?> alert-dismissible fade show">
        <?= $flash ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; endforeach; ?>
