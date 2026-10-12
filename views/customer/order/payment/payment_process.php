<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="row justify-content-center py-3">
  <div class="col-md-6 col-lg-5">
    <div class="card border-0 shadow rounded-4 p-4 text-center">
      <h5 class="fw-bold mb-1"><i class="bi bi-qr-code text-danger"></i> Thanh toán chuyển khoản</h5>
      <p class="text-muted small mb-3">Quét mã QR hoặc chuyển khoản theo thông tin bên dưới</p>

      <?php
        $bankId    = "MB";
        $accountNo = "0343368304";
        $amount    = (int)$order['total_amount'];
        $content   = "DH" . $order['id'];
        $qrUrl     = "https://img.vietqr.io/image/{$bankId}-{$accountNo}-qr_only.jpg"
                   . "?amount={$amount}&addInfo=" . urlencode($content);
      ?>

      <img src="<?= $qrUrl ?>" class="img-fluid rounded-3 mb-3 border"
           style="max-width:240px;margin:auto;display:block"
           alt="QR MB Bank đơn #<?= $order['id'] ?>">

      <div class="alert alert-light border text-start small mb-3">
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Ngân hàng</span>
          <strong>MB Bank</strong>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Số tài khoản</span>
          <strong><?= $accountNo ?></strong>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Chủ tài khoản</span>
          <strong>PHẠM QUANG VĂN</strong>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Số tiền</span>
          <strong class="text-danger"><?= formatMoney($amount) ?></strong>
        </div>
        <div class="d-flex justify-content-between">
          <span class="text-muted">Nội dung CK</span>
          <strong class="text-primary"><?= $content ?></strong>
        </div>
      </div>

      <div class="alert alert-warning text-start small py-2">
        <i class="bi bi-exclamation-triangle"></i>
        Vui lòng <strong>ghi đúng nội dung "<?= $content ?>"</strong> để đơn hàng được xác nhận tự động.
        Sau khi chuyển khoản, admin sẽ xác nhận trong vòng 15–30 phút.
      </div>

      <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/orders/<?= $order['id'] ?>"
           class="btn btn-danger flex-fill fw-bold">
          <i class="bi bi-check-circle"></i> Đã chuyển khoản
        </a>
        <a href="<?= BASE_URL ?>/orders" class="btn btn-outline-secondary flex-fill">
          Xem đơn hàng
        </a>
      </div>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
