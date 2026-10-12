<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="row justify-content-center">
  <div class="col-md-5 col-lg-4">
    <div class="card shadow-sm border-0 rounded-4">
      <div class="card-body p-4">
        <h4 class="fw-bold text-center mb-4"><i class="bi bi-flower1 text-danger"></i> Đăng nhập</h4>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/auth/login">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" required
                   value="<?= e($old['email'] ?? '') ?>" placeholder="you@example.com">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Mật khẩu</label>
            <input type="password" name="password" class="form-control" required placeholder="••••••••">
          </div>

          <button type="submit" class="btn btn-danger w-100 fw-bold">Đăng nhập</button>
        </form>

        <hr>
        <p class="text-center small mb-0">Chưa có tài khoản?
          <a href="<?= BASE_URL ?>/auth/register" class="text-danger fw-semibold">Đăng ký ngay</a>
        </p>
      </div>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
