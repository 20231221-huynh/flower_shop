<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<h4 class="fw-bold mb-4"><i class="bi bi-bag-check text-danger"></i> Đặt hàng</h4>

<div class="row g-4">
  <!-- Form thông tin giao hàng -->
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm rounded-4 p-4">
      <h5 class="fw-bold mb-3">Thông tin giao hàng</h5>
      <form method="POST" action="<?= BASE_URL ?>/checkout" id="checkoutForm">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="mb-3">
          <label class="form-label fw-semibold">Họ tên người nhận <span class="text-danger">*</span></label>
          <input type="text" name="receiver_name" class="form-control" required minlength="2"
                 value="<?= e($_SESSION['full_name'] ?? '') ?>" placeholder="Nguyễn Văn A">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
          <input type="tel" name="receiver_phone" class="form-control" required
                 pattern="(0|\+84)[0-9]{8,10}" placeholder="0912 345 678"
                 value="<?= e($_SESSION['phone'] ?? '') ?>">
          <small class="text-muted">Định dạng: 0912345678</small>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Địa chỉ giao hàng <span class="text-danger">*</span></label>
          <textarea name="delivery_address" class="form-control" rows="2" required
                    placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành"></textarea>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold">Ghi chú</label>
          <textarea name="note" class="form-control" rows="2"
                    placeholder="Ghi chú cho người giao hàng (tùy chọn)"></textarea>
        </div>

        <h5 class="fw-bold mb-3">Phương thức thanh toán</h5>
        <div class="d-flex flex-column gap-2 mb-4">
          <label class="border rounded-3 p-3 d-flex align-items-center gap-3 pay-option" style="cursor:pointer">
            <input type="radio" name="pay_method" value="cod" checked class="form-check-input mt-0">
            <div>
              <div class="fw-semibold">💵 Thanh toán khi nhận hàng (COD)</div>
              <small class="text-muted">Thanh toán tiền mặt khi shipper giao hàng tận nơi</small>
            </div>
          </label>

          <label class="border rounded-3 p-3 d-flex align-items-center gap-3 pay-option" style="cursor:pointer">
            <input type="radio" name="pay_method" value="vnpay" class="form-check-input mt-0">
            <div>
              <div class="fw-semibold">🏦 Thanh toán VNPay</div>
              <small class="text-muted">ATM, Visa/Mastercard, QR Code – Xác nhận ngay</small>
            </div>
          </label>

          <label class="border rounded-3 p-3 d-flex align-items-center gap-3 pay-option" style="cursor:pointer">
            <input type="radio" name="pay_method" value="momo" class="form-check-input mt-0">
            <div>
              <div class="fw-semibold">💜 Chuyển khoản MB Bank</div>
              <small class="text-muted">Quét mã QR – Admin xác nhận sau 15–30 phút</small>
            </div>
          </label>
        </div>

        <button type="submit" class="btn btn-danger w-100 fw-bold py-2" id="btnPlaceOrder">
          <i class="bi bi-check-circle"></i> Xác nhận đặt hàng
        </button>
      </form>
    </div>
  </div>

  <!-- Tóm tắt đơn hàng -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top:80px">
      <h5 class="fw-bold mb-3">Đơn hàng của bạn (<?= count($items) ?> sản phẩm)</h5>
      <?php foreach ($items as $item):
        $price = $item['sale_price'] ?: $item['price'];
      ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="d-flex align-items-center gap-2">
            <img src="<?= e($item['image'] ?? BASE_URL . '/img/no-image.png') ?>"
                 style="width:44px;height:44px;object-fit:cover;border-radius:6px">
            <div>
              <div class="small fw-semibold"><?= e($item['name']) ?></div>
              <div class="small text-muted">x<?= $item['quantity'] ?></div>
            </div>
          </div>
          <span class="small fw-semibold"><?= formatMoney((float)$price * $item['quantity']) ?></span>
        </div>
      <?php endforeach; ?>
      <hr>
      <div class="d-flex justify-content-between mb-1">
        <span class="text-muted">Tạm tính</span>
        <span><?= formatMoney($subtotal) ?></span>
      </div>
      <?php if ($discount > 0): ?>
      <div class="d-flex justify-content-between mb-1">
        <span class="text-muted">Giảm giá <?= $coupon ? '(' . e($coupon['code']) . ')' : '' ?></span>
        <span class="text-success fw-semibold">-<?= formatMoney($discount) ?></span>
      </div>
      <?php endif; ?>
      <div class="d-flex justify-content-between mb-1">
        <span class="text-muted">Phí vận chuyển</span>
        <span class="text-success fw-semibold">Miễn phí</span>
      </div>
      <hr>
      <div class="d-flex justify-content-between fw-bold fs-5">
        <span>Tổng cộng</span>
        <span class="text-danger"><?= formatMoney($total) ?></span>
      </div>
    </div>
  </div>
</div>

<script>
// Prevent double-submit
document.getElementById('checkoutForm').addEventListener('submit', function() {
  document.getElementById('btnPlaceOrder').disabled = true;
  document.getElementById('btnPlaceOrder').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
});

// Highlight selected payment method
document.querySelectorAll('.pay-option').forEach(function(label) {
  label.querySelector('input').addEventListener('change', function() {
    document.querySelectorAll('.pay-option').forEach(l => l.classList.remove('border-danger','bg-light'));
    label.classList.add('border-danger', 'bg-light');
  });
});
document.querySelector('.pay-option input:checked').dispatchEvent(new Event('change'));
</script>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
