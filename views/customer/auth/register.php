<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm border-0 rounded-4">
      <div class="card-body p-4">
        <h4 class="fw-bold text-center mb-4"><i class="bi bi-person-plus text-danger"></i> Đăng ký tài khoản</h4>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger py-2">
            <?php foreach ($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/auth/register">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div class="mb-3">
            <label class="form-label fw-semibold">Họ và tên</label>
            <input type="text" name="full_name" class="form-control" required
                   value="<?= e($old['fullName'] ?? '') ?>" placeholder="Nguyễn Văn A">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" required
                   value="<?= e($old['email'] ?? '') ?>" placeholder="you@example.com">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Số điện thoại</label>
            <input type="tel" name="phone" class="form-control"
                   value="<?= e($old['phone'] ?? '') ?>" placeholder="0912 345 678">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Mật khẩu</label>
            <input type="password" name="password" class="form-control" required
                   placeholder="Tối thiểu 8 ký tự">
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Xác nhận mật khẩu</label>
            <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••">
          </div>

          <button type="submit" class="btn btn-danger w-100 fw-bold">Tạo tài khoản</button>
        </form>

        <hr>
        <p class="text-center small mb-0">Đã có tài khoản?
          <a href="<?= BASE_URL ?>/auth/login" class="text-danger fw-semibold">Đăng nhập</a>
        </p>
      </div>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
