<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="row justify-content-center">
<div class="col-md-7">
  <div class="card border-0 shadow-sm rounded-4 p-4">
    <h4 class="fw-bold mb-4"><i class="bi bi-person-circle text-danger"></i> Hồ sơ của tôi</h4>

    <form method="POST" action="<?= BASE_URL ?>/profile">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="mb-3">
        <label class="form-label fw-semibold">Họ và tên</label>
        <input type="text" name="full_name" class="form-control" required minlength="2"
               value="<?= e($user['full_name']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" disabled>
        <small class="text-muted">Email không thể thay đổi.</small>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Số điện thoại</label>
        <input type="tel" name="phone" class="form-control"
               pattern="(0|\+84)[0-9]{8,10}" placeholder="0912 345 678"
               value="<?= e($user['phone'] ?? '') ?>">
      </div>
      <hr>
      <p class="text-muted small mb-2"><i class="bi bi-shield-lock"></i> Đổi mật khẩu (để trống nếu không muốn đổi)</p>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Mật khẩu mới</label>
          <input type="password" name="password" class="form-control" minlength="8" placeholder="Tối thiểu 8 ký tự">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Xác nhận mật khẩu</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu mới">
        </div>
      </div>

      <button type="submit" class="btn btn-danger w-100 fw-bold">
        <i class="bi bi-save"></i> Lưu thay đổi
      </button>
    </form>
  </div>

  <!-- Đơn hàng gần đây -->
  <div class="card border-0 shadow-sm rounded-4 p-4 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="fw-bold mb-0"><i class="bi bi-bag text-danger"></i> Đơn hàng của tôi</h6>
      <a href="<?= BASE_URL ?>/orders" class="btn btn-sm btn-outline-danger">Xem tất cả</a>
    </div>
    <p class="text-muted small mb-0">Quản lý và theo dõi các đơn hàng đã đặt.</p>
  </div>
</div>
</div>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
